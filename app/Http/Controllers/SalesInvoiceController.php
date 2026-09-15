<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Customer;
use App\Models\SalesInvoice;
use App\Models\StockItem;
use App\Services\SalesInvoiceService;
use App\Services\SettingsService;
use App\Support\AmountInWords;
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

        $invoice->load(['customer', 'lines']);
        $banks = BankAccount::orderBy('name')->get();

        return view('invoices.show', compact('invoice', 'banks', 'settings'));
    }

    public function edit(SalesInvoice $invoice, SettingsService $settings): View|RedirectResponse
    {
        if (! $invoice->isDraft()) {
            return redirect()->route('invoices.show', $invoice)->with('error', 'Issued invoices cannot be edited.');
        }

        $invoice->load('lines');

        return view('invoices.edit', [
            'invoice' => $invoice,
            'customers' => Customer::orderBy('name')->get(),
            'stockItems' => $this->stockItems(),
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

        return redirect()->route('invoices.show', $invoice)->with('success', 'Draft invoice updated.');
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
        $invoice->load(['customer', 'lines.stockItem']);

        return view('invoices.print', [
            'invoice' => $invoice,
            'settings' => $settings,
            'amountInWords' => AmountInWords::rupees((float) $invoice->total),
            'printedAt' => now(),
        ]);
    }

    public function destroy(SalesInvoice $invoice): RedirectResponse
    {
        if (! $invoice->isDraft()) {
            return back()->with('error', 'Only draft invoices can be deleted.');
        }
        $invoice->delete();

        return redirect()->route('invoices.index')->with('success', 'Draft deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'type' => ['required', 'in:simplified,standard'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.stock_item_id' => ['required', 'exists:stock_items,id'],
            'lines.*.description' => ['nullable', 'string', 'max:255'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.unit_price' => ['required', 'numeric', 'gte:0'],
            'lines.*.discount_rate' => ['nullable', 'numeric', 'gte:0', 'lte:100'],
            'lines.*.vat_rate' => ['required', 'numeric', 'gte:0', 'lte:100'],
        ]);

        $data['lines'] = array_values(array_filter(
            $data['lines'],
            fn ($line) => filled($line['description'] ?? null) || filled($line['stock_item_id'] ?? null)
        ));

        foreach ($data['lines'] as &$line) {
            $line['discount_rate'] = $line['discount_rate'] ?? 0;

            if (empty($line['stock_item_id'])) {
                $line['stock_item_id'] = null;
                continue;
            }

            $item = StockItem::whereKey($line['stock_item_id'])->first();
            if ($item && blank($line['description'] ?? null)) {
                $line['description'] = $item->name;
            }
        }
        unset($line);

        return $data;
    }

    /**
     * @return Collection<int, StockItem>
     */
    private function stockItems(): Collection
    {
        return StockItem::query()
            ->where('is_active', true)
            ->with('variants')
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'unit', 'sale_price', 'quantity', 'has_variants']);
    }
}
