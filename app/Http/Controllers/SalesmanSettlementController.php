<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Salesman;
use App\Models\SalesmanSettlement;
use App\Services\SalesmanSettlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use RuntimeException;

class SalesmanSettlementController extends Controller
{
    public function __construct(private SalesmanSettlementService $settlements) {}

    public function index(): View
    {
        $settlements = SalesmanSettlement::with('salesman')
            ->orderByDesc('settlement_date')
            ->orderByDesc('id')
            ->limit(500)
            ->get();

        $latestIds = SalesmanSettlement::query()
            ->selectRaw('MAX(id) as id')
            ->groupBy('salesman_id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return view('salesman-settlements.index', compact('settlements', 'latestIds'));
    }

    public function create(Request $request): View
    {
        $salesmen = Salesman::query()->where('is_active', true)->orderBy('name')->get();
        $bankAccounts = BankAccount::orderBy('name')->get();
        $selectedSalesmanId = $request->query('salesman_id');
        $receivables = $salesmen->mapWithKeys(fn (Salesman $salesman) => [
            $salesman->id => $this->settlements->netReceivable($salesman),
        ]);

        return view('salesman-settlements.create', compact('salesmen', 'bankAccounts', 'selectedSalesmanId', 'receivables'));
    }

    public function edit(SalesmanSettlement $salesman_settlement): View|RedirectResponse
    {
        if (! $this->settlements->isLatest($salesman_settlement)) {
            return redirect()
                ->route('salesman-settlements.show', $salesman_settlement)
                ->with('error', 'Edit newer settlements for this salesman first.');
        }

        $salesman_settlement->load(['salesman', 'allocations']);
        $salesman = $salesman_settlement->salesman;
        $bankAccounts = BankAccount::orderBy('name')->get();

        $preselectedAllocations = $salesman_settlement->allocations
            ->map(fn ($allocation) => [
                'invoice_id' => (int) $allocation->sales_invoice_id,
                'amount' => round((float) $allocation->amount, 2),
            ])
            ->values()
            ->all();

        $openingAdvance = round((float) $salesman_settlement->opening_advance, 2);
        $netReceivable = round($this->settlements->netReceivable($salesman) + (float) $salesman_settlement->cash_received, 2);

        return view('salesman-settlements.edit', [
            'settlement' => $salesman_settlement,
            'salesman' => $salesman,
            'bankAccounts' => $bankAccounts,
            'preselectedAllocations' => $preselectedAllocations,
            'openingAdvance' => $openingAdvance,
            'netReceivable' => $netReceivable,
        ]);
    }

    public function invoices(Request $request): JsonResponse
    {
        $data = $request->validate([
            'salesman_id' => ['required', 'integer', 'exists:salesmen,id'],
            'settlement_id' => ['nullable', 'integer', 'exists:salesman_settlements,id'],
        ]);

        $salesman = Salesman::query()->findOrFail($data['salesman_id']);
        $settlement = ! empty($data['settlement_id'])
            ? SalesmanSettlement::query()->with('allocations')->find($data['settlement_id'])
            : null;

        if ($settlement && (int) $settlement->salesman_id !== (int) $salesman->id) {
            return response()->json(['message' => 'Settlement does not belong to this salesman.'], 422);
        }

        $allocatedByInvoice = $settlement
            ? $settlement->allocations->mapWithKeys(
                fn ($allocation) => [(int) $allocation->sales_invoice_id => round((float) $allocation->amount, 2)]
            )->all()
            : [];

        $invoices = $this->settlements->unpaidInvoices($salesman, $settlement)->map(function ($invoice) use ($allocatedByInvoice) {
            $allocated = (float) ($allocatedByInvoice[$invoice->id] ?? 0);
            $due = round($invoice->balanceDue() + $allocated, 2);

            return [
                'id' => $invoice->id,
                'invoice_no' => $invoice->invoice_no,
                'invoice_date' => ams_date($invoice->invoice_date),
                'customer' => $invoice->customer?->displayName() ?: '—',
                'city' => $invoice->customer?->city ?: '',
                'total' => round((float) $invoice->total, 2),
                'due' => $due,
                'allocated' => $allocated,
            ];
        })->values();

        $openingAdvance = $settlement
            ? round((float) $settlement->opening_advance, 2)
            : $this->settlements->currentAdvance($salesman);
        $netReceivable = $settlement
            ? round($this->settlements->netReceivable($salesman) + (float) $settlement->cash_received, 2)
            : $this->settlements->netReceivable($salesman);

        return response()->json([
            'salesman' => $salesman->name,
            'opening_advance' => $openingAdvance,
            'opening_balance' => $settlement
                ? round((float) $settlement->opening_receivable, 2)
                : round((float) $salesman->opening_balance, 2),
            'net_receivable' => $netReceivable,
            'invoices' => $invoices,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedSettlement($request);

        $allocations = [];
        foreach ($data['allocations'] ?? [] as $row) {
            $allocations[] = [
                'invoice_id' => (int) $row['invoice_id'],
                'amount' => (float) $row['amount'],
            ];
        }
        unset($data['allocations']);

        try {
            $settlement = $this->settlements->create($data, $allocations, $request->user()?->id);
        } catch (InvalidArgumentException|RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('salesman-settlements.show', $settlement)
            ->with('success', 'Settlement saved. Cash received once; customer receipts posted for verification.');
    }

    public function update(Request $request, SalesmanSettlement $salesman_settlement): RedirectResponse
    {
        $data = $this->validatedSettlement($request, requireSalesman: false);
        $data['salesman_id'] = (int) $salesman_settlement->salesman_id;

        $allocations = [];
        foreach ($data['allocations'] ?? [] as $row) {
            $allocations[] = [
                'invoice_id' => (int) $row['invoice_id'],
                'amount' => (float) $row['amount'],
            ];
        }
        unset($data['allocations']);

        try {
            $settlement = $this->settlements->update($salesman_settlement, $data, $allocations, $request->user()?->id);
        } catch (InvalidArgumentException|RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('salesman-settlements.show', $settlement)
            ->with('success', 'Settlement updated.');
    }

    public function show(SalesmanSettlement $salesman_settlement): View
    {
        $salesman_settlement->load([
            'salesman',
            'cashVoucher',
            'bankAccount',
            'allocations.invoice',
            'allocations.customer',
            'allocations.cashVoucher',
        ]);

        return view('salesman-settlements.show', [
            'settlement' => $salesman_settlement,
            'canEdit' => $this->settlements->isLatest($salesman_settlement),
        ]);
    }

    public function destroy(SalesmanSettlement $salesman_settlement): RedirectResponse
    {
        try {
            $this->settlements->delete($salesman_settlement);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('salesman-settlements.index')->with('success', 'Settlement deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedSettlement(Request $request, bool $requireSalesman = true): array
    {
        $rules = [
            'settlement_date' => ['required', 'date'],
            'cash_received' => ['required', 'numeric', 'gte:0'],
            'payment_method' => ['required', 'in:cash,bank'],
            'bank_account_id' => ['nullable', 'exists:bank_accounts,id'],
            'notes' => ['nullable', 'string'],
            'allocations' => ['nullable', 'array'],
            'allocations.*.invoice_id' => ['required', 'integer', 'exists:sales_invoices,id'],
            'allocations.*.amount' => ['required', 'numeric', 'gt:0'],
        ];

        if ($requireSalesman) {
            $rules['salesman_id'] = ['required', 'integer', 'exists:salesmen,id'];
        }

        return $request->validate($rules);
    }
}
