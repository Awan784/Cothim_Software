<?php

namespace App\Http\Controllers;

use App\Models\CashVoucher;
use App\Models\ExpenseAccount;
use Illuminate\Http\Request;

class ExpenseAccountController extends Controller
{
    public function index()
    {
        ExpenseAccount::syncAllTotalSpent();
        $expenseAccounts = ExpenseAccount::orderBy('name')->get();

        return view('expense-accounts.index', compact('expenseAccounts'));
    }

    public function create()
    {
        return view('expense-accounts.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:expense_categories,name'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = (bool) ($data['is_active'] ?? true);
        $data['total_spent'] = 0;

        ExpenseAccount::create($data);

        return redirect()->route('expense-accounts.index')->with('success', 'Expense account created.');
    }

    public function show(ExpenseAccount $expenseAccount)
    {
        $vouchers = CashVoucher::where('account_type', 'expense')
            ->where('account_id', $expenseAccount->id)
            ->orderByDesc('voucher_date')
            ->orderByDesc('id')
            ->get();

        return view('expense-accounts.show', compact('expenseAccount', 'vouchers'));
    }

    public function edit(ExpenseAccount $expenseAccount)
    {
        return view('expense-accounts.edit', compact('expenseAccount'));
    }

    public function update(Request $request, ExpenseAccount $expenseAccount)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:expense_categories,name,'.$expenseAccount->id],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        $expenseAccount->update($data);

        return redirect()->route('expense-accounts.index')->with('success', 'Expense account updated.');
    }

    public function destroy(ExpenseAccount $expenseAccount)
    {
        $hasVouchers = CashVoucher::where('account_type', 'expense')
            ->where('account_id', $expenseAccount->id)
            ->exists();

        if ($hasVouchers) {
            return back()->with('error', 'Cannot delete: cash vouchers exist for this expense account.');
        }

        $expenseAccount->delete();

        return back()->with('success', 'Expense account deleted.');
    }
}
