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

        return view('salesman-settlements.index', compact('settlements'));
    }

    public function create(Request $request): View
    {
        $salesmen = Salesman::query()->where('is_active', true)->orderBy('name')->get();
        $bankAccounts = BankAccount::orderBy('name')->get();
        $selectedSalesmanId = $request->query('salesman_id');

        return view('salesman-settlements.create', compact('salesmen', 'bankAccounts', 'selectedSalesmanId'));
    }

    public function invoices(Request $request): JsonResponse
    {
        $data = $request->validate([
            'salesman_id' => ['required', 'integer', 'exists:salesmen,id'],
        ]);

        $salesman = Salesman::query()->findOrFail($data['salesman_id']);
        $invoices = $this->settlements->unpaidInvoices($salesman)->map(function ($invoice) {
            return [
                'id' => $invoice->id,
                'invoice_no' => $invoice->invoice_no,
                'invoice_date' => ams_date($invoice->invoice_date),
                'customer' => $invoice->customer?->displayName() ?: '—',
                'city' => $invoice->customer?->city ?: '',
                'total' => round((float) $invoice->total, 2),
                'due' => $invoice->balanceDue(),
            ];
        })->values();

        return response()->json([
            'salesman' => $salesman->name,
            'opening_advance' => $this->settlements->currentAdvance($salesman),
            'invoices' => $invoices,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'salesman_id' => ['required', 'integer', 'exists:salesmen,id'],
            'settlement_date' => ['required', 'date'],
            'cash_received' => ['required', 'numeric', 'gte:0'],
            'payment_method' => ['required', 'in:cash,bank'],
            'bank_account_id' => ['nullable', 'exists:bank_accounts,id'],
            'notes' => ['nullable', 'string'],
            'allocations' => ['nullable', 'array'],
            'allocations.*.invoice_id' => ['required', 'integer', 'exists:sales_invoices,id'],
            'allocations.*.amount' => ['required', 'numeric', 'gt:0'],
        ]);

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
}
