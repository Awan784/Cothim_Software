<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\PurchaseBill;
use App\Models\Supplier;
use App\Services\PurchaseBillService;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class PurchaseBillController extends Controller
{
    public function __construct(private PurchaseBillService $bills) {}

    public function index(): View
    {
        $bills = PurchaseBill::with('supplier')->orderByDesc('id')->limit(300)->get();

        return view('bills.index', compact('bills'));
    }

    public function create(SettingsService $settings): View
    {
        return view('bills.create', [
            'suppliers' => Supplier::orderBy('name')->get(),
            'vatRate' => $settings->vatRate(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        try {
            $bill = $this->bills->saveDraft(null, $data, $data['lines'], $request->user());
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('bills.show', $bill)->with('success', 'Draft bill saved.');
    }

    public function show(PurchaseBill $bill): View
    {
        $bill->load(['supplier', 'lines']);
        $banks = BankAccount::orderBy('name')->get();

        return view('bills.show', compact('bill', 'banks'));
    }

    public function edit(PurchaseBill $bill, SettingsService $settings): View|RedirectResponse
    {
        if (! $bill->isDraft()) {
            return redirect()->route('bills.show', $bill)->with('error', 'Posted bills cannot be edited.');
        }
        $bill->load('lines');

        return view('bills.edit', [
            'bill' => $bill,
            'suppliers' => Supplier::orderBy('name')->get(),
            'vatRate' => $settings->vatRate(),
        ]);
    }

    public function update(Request $request, PurchaseBill $bill): RedirectResponse
    {
        $data = $this->validated($request);

        try {
            $bill = $this->bills->saveDraft($bill, $data, $data['lines'], $request->user());
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('bills.show', $bill)->with('success', 'Draft bill updated.');
    }

    public function post(PurchaseBill $bill): RedirectResponse
    {
        try {
            $this->bills->post($bill);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('bills.show', $bill)->with('success', 'Bill posted. Supplier payable updated.');
    }

    public function pay(Request $request, PurchaseBill $bill): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_method' => ['required', 'in:cash,bank'],
            'bank_account_id' => ['nullable', 'exists:bank_accounts,id'],
        ]);

        try {
            $this->bills->recordPayment(
                $bill,
                (float) $data['amount'],
                $data['payment_method'],
                isset($data['bank_account_id']) ? (int) $data['bank_account_id'] : null,
            );
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Payment recorded.');
    }

    public function destroy(PurchaseBill $bill): RedirectResponse
    {
        if (! $bill->isDraft()) {
            return back()->with('error', 'Only draft bills can be deleted.');
        }
        $bill->delete();

        return redirect()->route('bills.index')->with('success', 'Draft deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'bill_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.description' => ['required', 'string', 'max:255'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.unit_price' => ['required', 'numeric', 'gte:0'],
            'lines.*.vat_rate' => ['required', 'numeric', 'gte:0', 'lte:100'],
        ]);

        $data['lines'] = array_values($data['lines']);

        return $data;
    }
}
