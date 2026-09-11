<?php

namespace App\Http\Controllers;

use App\Models\CashAccount;
use App\Models\BankAccount;
use App\Models\CashVoucher;
use App\Models\Customer;
use App\Models\ExpenseAccount;
use App\Models\Supplier;
use App\Services\CashVoucherService;
use App\Services\SettingsService;
use App\Support\AmountInWords;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CashVoucherController extends Controller
{
    public function __construct(private CashVoucherService $vouchers) {}

    private function defaultCashAccountId(): int
    {
        return $this->vouchers->defaultCashAccountId();
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $typeFilter = $request->query('type');
        $accountTypeFilter = $request->query('account_type');
        $accountIdFilter = $request->filled('account_id') ? (int) $request->query('account_id') : null;

        $query = CashVoucher::with(['cashAccount', 'bankAccount'])
            ->orderByDesc('voucher_date')
            ->orderByDesc('id');

        if (in_array($typeFilter, ['receive', 'payment'], true)) {
            $query->where('type', $typeFilter);
        } else {
            $typeFilter = null;
        }

        $allowedAccountTypes = ['customer', 'supplier', 'expense', 'other'];
        if (in_array($accountTypeFilter, $allowedAccountTypes, true)) {
            $query->where('account_type', $accountTypeFilter);

            if ($accountTypeFilter !== 'other' && $accountIdFilter) {
                $query->where('account_id', $accountIdFilter);
            }
        } else {
            $accountTypeFilter = null;
            $accountIdFilter = null;
        }

        $cashVouchers = $query->limit(500)->get();

        CashVoucher::loadAccountNames($cashVouchers);

        $customers = Customer::orderBy('name')->get(['id', 'name']);
        $suppliers = Supplier::orderBy('name')->get(['id', 'name']);
        $expenseAccounts = ExpenseAccount::orderBy('name')->get(['id', 'name']);

        return view('cash-vouchers.index', compact(
            'cashVouchers',
            'typeFilter',
            'accountTypeFilter',
            'accountIdFilter',
            'customers',
            'suppliers',
            'expenseAccounts',
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $customers = Customer::orderBy('name')->get();
        $suppliers = Supplier::orderBy('name')->get();
        $expenseAccounts = ExpenseAccount::orderBy('name')->get();
        $bankAccounts = BankAccount::orderBy('name')->get();
        $cashAccountId = $this->defaultCashAccountId();
        $cashBalance = (float) CashAccount::whereKey($cashAccountId)->value('current_balance');

        return view('cash-vouchers.create', compact('customers', 'suppliers', 'expenseAccounts', 'bankAccounts', 'cashAccountId', 'cashBalance'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', 'in:receive,payment'],
            'payment_method' => ['required', 'in:cash,bank'],
            'bank_account_id' => ['nullable', 'exists:bank_accounts,id'],
            'account_type' => ['required', 'in:customer,supplier,expense,other'],
            'account_id' => ['nullable', 'integer'],
            'other_name' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'voucher_date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $data['voucher_date'] = $data['voucher_date'] ?? now()->toDateString();

        try {
            $this->vouchers->create($data);
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('cash-vouchers.index')->with('success', 'Cash voucher created.');
    }

    /**
     * Display the specified resource.
     */
    public function show(CashVoucher $cashVoucher)
    {
        return redirect()->route('cash-vouchers.edit', $cashVoucher);
    }

    public function print(CashVoucher $cashVoucher): View
    {
        $cashVoucher->load(['cashAccount', 'bankAccount']);
        CashVoucher::loadAccountNames(collect([$cashVoucher]));

        $isReceive = $cashVoucher->type === 'receive';

        $partyName = $cashVoucher->account_type === 'other'
            ? ($cashVoucher->other_name ?? '—')
            : ($cashVoucher->account_display_name ?? '—');

        $partyTypeLabel = match ($cashVoucher->account_type) {
            'customer' => 'Customer',
            'supplier' => 'Supplier',
            'investor' => 'Investor',
            'expense' => 'Expense Account',
            'other' => 'Other',
            default => ucfirst((string) $cashVoucher->account_type),
        };

        if (($cashVoucher->payment_method ?? 'cash') === 'bank') {
            $paymentLabel = 'Bank'.($cashVoucher->bankAccount ? ' — '.$cashVoucher->bankAccount->name : '');
        } else {
            $paymentLabel = 'Cash'.($cashVoucher->cashAccount ? ' — '.$cashVoucher->cashAccount->name : '');
        }

        return view('cash-vouchers.print', [
            'voucher' => $cashVoucher,
            'companyName' => app(SettingsService::class)->companyName(),
            'printedAt' => now(),
            'isReceive' => $isReceive,
            'partyName' => $partyName,
            'partyTypeLabel' => $partyTypeLabel,
            'paymentLabel' => $paymentLabel,
            'amountInWords' => AmountInWords::rupees((float) $cashVoucher->amount),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CashVoucher $cashVoucher)
    {
        $customers = Customer::orderBy('name')->get();
        $suppliers = Supplier::orderBy('name')->get();
        $expenseAccounts = ExpenseAccount::orderBy('name')->get();
        $bankAccounts = BankAccount::orderBy('name')->get();
        $cashAccountId = $this->defaultCashAccountId();
        $cashBalance = (float) CashAccount::whereKey($cashAccountId)->value('current_balance');

        return view('cash-vouchers.edit', compact('cashVoucher', 'customers', 'suppliers', 'expenseAccounts', 'bankAccounts', 'cashAccountId', 'cashBalance'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CashVoucher $cashVoucher)
    {
        $data = $request->validate([
            'type' => ['required', 'in:receive,payment'],
            'payment_method' => ['required', 'in:cash,bank'],
            'bank_account_id' => ['nullable', 'exists:bank_accounts,id'],
            'account_type' => ['required', 'in:customer,supplier,expense,other'],
            'account_id' => ['nullable', 'integer'],
            'other_name' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'voucher_date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $data['cash_account_id'] = $this->defaultCashAccountId();

        if ($data['payment_method'] === 'bank' && empty($data['bank_account_id'])) {
            return back()->withInput()->with('error', 'Please select a bank account.');
        }
        if ($data['payment_method'] === 'cash') {
            $data['bank_account_id'] = null;
        }

        if ($data['account_type'] === 'other') {
            if (empty($data['other_name'])) {
                return back()->withInput()->with('error', 'Please enter Other name.');
            }
            $data['account_id'] = null;
        } else {
            if (empty($data['account_id'])) {
                return back()->withInput()->with('error', 'Please select an account.');
            }
            $data['other_name'] = null;
        }

        DB::transaction(function () use ($cashVoucher, $data) {
            $oldAmount = (float) $cashVoucher->amount;
            $oldType = $cashVoucher->type;

            // Restore old source balances
            if (($cashVoucher->payment_method ?? 'cash') === 'cash') {
                $oldCash = CashAccount::whereKey($cashVoucher->cash_account_id)->lockForUpdate()->firstOrFail();
                if ($oldType === 'receive') {
                    $oldCash->decrement('current_balance', $oldAmount);
                } else {
                    $oldCash->increment('current_balance', $oldAmount);
                }
            } else {
                $oldBank = BankAccount::whereKey($cashVoucher->bank_account_id)->lockForUpdate()->firstOrFail();
                if ($oldType === 'receive') {
                    $oldBank->decrement('current_balance', $oldAmount);
                } else {
                    $oldBank->increment('current_balance', $oldAmount);
                }
            }

            $oldExpenseAccountId = $cashVoucher->account_type === 'expense' ? (int) $cashVoucher->account_id : null;

            $this->vouchers->applyPartyBalance($cashVoucher, $oldAmount, $oldType, false);

            // Update voucher
            $cashVoucher->update($data);

            if ($oldExpenseAccountId && ($data['account_type'] !== 'expense' || (int) ($data['account_id'] ?? 0) !== $oldExpenseAccountId)) {
                ExpenseAccount::syncTotalSpentFor($oldExpenseAccountId);
            }

            // Apply new balances
            $newAmount = (float) $cashVoucher->amount;
            $newType = $cashVoucher->type;

            if (($cashVoucher->payment_method ?? 'cash') === 'cash') {
                $newCash = CashAccount::whereKey($cashVoucher->cash_account_id)->lockForUpdate()->firstOrFail();

                if ($newType === 'payment' && (float) $newCash->current_balance < $newAmount) {
                    throw new \RuntimeException('No balance in this cash account.');
                }

                if ($newType === 'receive') {
                    $newCash->increment('current_balance', $newAmount);
                } else {
                    $newCash->decrement('current_balance', $newAmount);
                }
            } else {
                $newBank = BankAccount::whereKey($cashVoucher->bank_account_id)->lockForUpdate()->firstOrFail();

                if ($newType === 'payment' && (float) $newBank->current_balance < $newAmount) {
                    throw new \RuntimeException('No balance in this bank account.');
                }

                if ($newType === 'receive') {
                    $newBank->increment('current_balance', $newAmount);
                } else {
                    $newBank->decrement('current_balance', $newAmount);
                }
            }

            $this->vouchers->applyPartyBalance($cashVoucher, $newAmount, $newType, true);
        });

        return redirect()->route('cash-vouchers.index')->with('success', 'Cash voucher updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CashVoucher $cashVoucher)
    {
        DB::transaction(function () use ($cashVoucher) {
            $amount = (float) $cashVoucher->amount;

            if (($cashVoucher->payment_method ?? 'cash') === 'cash') {
                $cash = CashAccount::whereKey($cashVoucher->cash_account_id)->lockForUpdate()->firstOrFail();
                if ($cashVoucher->type === 'receive') {
                    $cash->decrement('current_balance', $amount);
                } else {
                    $cash->increment('current_balance', $amount);
                }
            } else {
                $bank = BankAccount::whereKey($cashVoucher->bank_account_id)->lockForUpdate()->firstOrFail();
                if ($cashVoucher->type === 'receive') {
                    $bank->decrement('current_balance', $amount);
                } else {
                    $bank->increment('current_balance', $amount);
                }
            }

            $this->vouchers->applyPartyBalance($cashVoucher, $amount, $cashVoucher->type, false);

            $cashVoucher->delete();
        });

        return back()->with('success', 'Cash voucher deleted.');
    }
}
