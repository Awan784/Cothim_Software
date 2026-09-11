<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExpenseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $expenses = Expense::with(['expenseCategory', 'bankAccount', 'supplier'])
            ->orderByDesc('expense_date')
            ->limit(500)
            ->get();

        return view('expenses.index', compact('expenses'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $expenseCategories = ExpenseCategory::orderBy('name')->get();
        $bankAccounts = BankAccount::orderBy('name')->get();
        $suppliers = Supplier::orderBy('name')->get();

        return view('expenses.create', compact('expenseCategories', 'bankAccounts', 'suppliers'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'expense_category_id' => ['required', 'exists:expense_categories,id'],
            'bank_account_id' => ['nullable', 'exists:bank_accounts,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'expense_date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($data) {
            $expense = Expense::create($data);

            if (! empty($expense->bank_account_id)) {
                BankAccount::whereKey($expense->bank_account_id)->decrement('current_balance', (float) $expense->amount);
            }
        });

        return redirect()->route('expenses.index')->with('success', 'Expense created.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Expense $expense)
    {
        return redirect()->route('expenses.edit', $expense);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Expense $expense)
    {
        $expenseCategories = ExpenseCategory::orderBy('name')->get();
        $bankAccounts = BankAccount::orderBy('name')->get();
        $suppliers = Supplier::orderBy('name')->get();

        return view('expenses.edit', compact('expense', 'expenseCategories', 'bankAccounts', 'suppliers'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Expense $expense)
    {
        $data = $request->validate([
            'expense_category_id' => ['required', 'exists:expense_categories,id'],
            'bank_account_id' => ['nullable', 'exists:bank_accounts,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'expense_date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($expense, $data) {
            $oldBankId = $expense->bank_account_id;
            $oldAmount = (float) $expense->amount;

            $expense->update($data);

            $newBankId = $expense->bank_account_id;
            $newAmount = (float) $expense->amount;

            // Restore old
            if (! empty($oldBankId)) {
                BankAccount::whereKey($oldBankId)->increment('current_balance', $oldAmount);
            }

            // Deduct new
            if (! empty($newBankId)) {
                BankAccount::whereKey($newBankId)->decrement('current_balance', $newAmount);
            }
        });

        return redirect()->route('expenses.index')->with('success', 'Expense updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Expense $expense)
    {
        DB::transaction(function () use ($expense) {
            if (! empty($expense->bank_account_id)) {
                BankAccount::whereKey($expense->bank_account_id)->increment('current_balance', (float) $expense->amount);
            }

            $expense->delete();
        });

        return back()->with('success', 'Expense deleted.');
    }
}
