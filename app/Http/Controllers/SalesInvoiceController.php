<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\CashVoucher;
use App\Models\Customer;
use App\Models\SalesInvoice;
use App\Models\Salesman;
use App\Models\StockItem;
use App\Services\SalesInvoiceService;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use InvalidArgumentException;

class SalesInvoiceController extends Controller
{
    public function __construct(private SalesInvoiceService $invoices) {}

    public function index(): View
    {
        $invoices = SalesInvoice::with('customer')->orderByDesc('id')->limit(300)->get();

        return view('invoices.index', compact('invoices'));
    }

    public function create(SettingsService $settings): View
    {
        return view('invoices.create', [
            'customers' => Customer::orderBy('name')->get(),
            'salesmen' => $this->salesmen(),
            'stockItems' => $this->stockItems(),
            'vatRate' => $settings->vatRate(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        try {
            $invoice = $this->invoices->generate($data, $data['lines'], $request->user());
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice generated.');
    }

    public function show(SalesInvoice $invoice, SettingsService $settings): View
    {
        if ($invoice->isDraft()) {
            try {
                $invoice = $this->invoices->issue($invoice);
            } catch (InvalidArgumentException $e) {
                session()->now('error', $e->getMessage());
            }
        }

        $invoice->load(['customer', 'lines.stockItem', 'lines.lot', 'salesman']);
        $banks = BankAccount::orderBy('name')->get();
        $receipts = CashVoucher::query()
            ->where('sales_invoice_id', $invoice->id)
            ->orderByDesc('voucher_date')
            ->orderByDesc('id')
            ->get();

        return view('invoices.show', compact('invoice', 'banks', 'settings', 'receipts'));
    }

    public function edit(SalesInvoice $invoice, SettingsService $settings): View
    {
        $invoice->load('lines');

        return view('invoices.edit', [
            'invoice' => $invoice,
            'customers' => Customer::orderBy('name')->get(),
            'salesmen' => $this->salesmen($invoice->salesman_id),
            'stockItems' => $this->stockItems($invoice->lines->pluck('stock_item_id')->filter()->all()),
            'vatRate' => $settings->vatRate(),
        ]);
    }

    public function update(Request $request, SalesInvoice $invoice): RedirectResponse
    {
        $data = $this->validated($request);

        try {
            $invoice = $this->invoices->saveDraft($invoice, $data, $data['lines'], $request->user());
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice updated.');
    }

    public function issue(SalesInvoice $invoice): RedirectResponse
    {
        try {
            $this->invoices->issue($invoice);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice issued with ZATCA Phase 1 QR.');
    }

    public function pay(Request $request, SalesInvoice $invoice): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_method' => ['required', 'in:cash,bank'],
            'bank_account_id' => ['nullable', 'exists:bank_accounts,id'],
        ]);

        try {
            $this->invoices->recordPayment(
                $invoice,
                (float) $data['amount'],
                $data['payment_method'],
                isset($data['bank_account_id']) ? (int) $data['bank_account_id'] : null,
            );
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Payment recorded.');
    }

    public function print(SalesInvoice $invoice, SettingsService $settings): View
    {
        $invoice->load(['customer', 'salesman', 'lines.stockItem', 'lines.lot', 'creator']);

        return view('invoices.print', [
            'invoice' => $invoice,
            'settings' => $settings,
            'printedAt' => now(),
        ]);
    }

    public function destroy(SalesInvoice $invoice): RedirectResponse
    {
        try {
            $this->invoices->softDelete($invoice);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('invoices.index')->with('success', 'Invoice deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'invoice_date' => ['required', 'date'],
            'salesman_id' => ['nullable', 'exists:salesmen,id'],
            'builty_postal' => ['nullable', 'string', 'max:100'],
            'builty_exp' => ['nullable', 'numeric', 'gte:0'],
            'notes' => ['nullable', 'string'],
            'mode' => ['nullable', 'string', 'max:50'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.stock_item_id' => ['required', 'exists:stock_items,id'],
            'lines.*.stock_item_lot_id' => ['nullable', 'exists:stock_item_lots,id'],
            'lines.*.description' => ['nullable', 'string', 'max:255'],
            'lines.*.print_note' => ['nullable', 'string', 'max:100'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.unit_price' => ['required', 'numeric', 'gte:0'],
            'lines.*.discount_rate' => ['nullable', 'numeric', 'gte:0', 'lte:100'],
            'lines.*.commission_amount' => ['nullable', 'numeric', 'gte:0'],
            'lines.*.vat_rate' => ['nullable', 'numeric', 'gte:0', 'lte:100'],
        ]);

        $data['type'] = 'simplified';
        $data['due_date'] = null;
        $data['salesman_id'] = filled($data['salesman_id'] ?? null) ? (int) $data['salesman_id'] : null;
        $postal = trim((string) ($data['builty_postal'] ?? ''));
        $data['builty_postal'] = $postal === '' ? null : $postal;
        $data['builty_exp'] = ($data['builty_exp'] ?? '') === '' || $data['builty_exp'] === null
            ? null
            : round((float) $data['builty_exp'], 2);
        $mode = trim((string) ($data['mode'] ?? ''));
        $data['mode'] = $mode === '' ? null : $mode;

        $data['lines'] = array_values(array_filter(
            $data['lines'],
            fn ($line) => filled($line['description'] ?? null) || filled($line['stock_item_id'] ?? null)
        ));

        foreach ($data['lines'] as &$line) {
            $line['discount_rate'] = $line['discount_rate'] ?? 0;
            $line['commission_amount'] = round((float) ($line['commission_amount'] ?? 0), 2);
            $line['vat_rate'] = 0;

            if (empty($line['stock_item_id'])) {
                $line['stock_item_id'] = null;
                continue;
            }

            $item = StockItem::whereKey($line['stock_item_id'])->first();
            if ($item && blank($line['description'] ?? null)) {
                $line['description'] = $item->name;
            }

            $note = trim((string) ($line['print_note'] ?? ''));
            $line['print_note'] = $note === '' ? null : $note;

            $line = StockItem::applyLotToLine($line);
        }
        unset($line);

        return $data;
    }

    /**
     * @return Collection<int, Salesman>
     */
    private function salesmen(?int $currentId = null): Collection
    {
        return Salesman::query()
            ->where(function ($query) use ($currentId) {
                $query->where('is_active', true);
                if ($currentId) {
                    $query->orWhere('id', $currentId);
                }
            })
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * @param  list<int|string>|null  $currentIds
     * @return Collection<int, StockItem>
     */
    private function stockItems(?array $currentIds = null): Collection
    {
        $currentIds = array_values(array_unique(array_filter(array_map('intval', $currentIds ?? []))));

        return StockItem::query()
            ->where(function ($query) use ($currentIds) {
                $query->where('is_active', true);
                if ($currentIds !== []) {
                    $query->orWhereIn('id', $currentIds);
                }
            })
            ->with([
                'variants',
                'lots' => fn ($query) => $query->orderBy('received_at')->orderBy('id'),
            ])
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'batch_no', 'unit', 'sale_price', 'quantity', 'has_variants']);
    }
}
