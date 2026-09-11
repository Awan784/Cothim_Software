<?php

namespace App\Http\Controllers;

use App\Models\ExpenseCategory;
use App\Models\NominalAccount;
use Illuminate\Http\Request;

class ExpenseCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $expenseCategories = ExpenseCategory::with('nominalAccount')->orderBy('name')->get();

        return view('expense-categories.index', compact('expenseCategories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $nominalAccounts = NominalAccount::orderBy('name')->get();

        return view('expense-categories.create', compact('nominalAccounts'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:expense_categories,name'],
            'nominal_account_id' => ['nullable', 'exists:nominal_accounts,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = (bool) ($data['is_active'] ?? true);

        ExpenseCategory::create($data);

        return redirect()->route('expense-categories.index')->with('success', 'Expense category created.');
    }

    /**
     * Display the specified resource.
     */
    public function show(ExpenseCategory $expenseCategory)
    {
        return redirect()->route('expense-categories.edit', $expenseCategory);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ExpenseCategory $expenseCategory)
    {
        $nominalAccounts = NominalAccount::orderBy('name')->get();

        return view('expense-categories.edit', compact('expenseCategory', 'nominalAccounts'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ExpenseCategory $expenseCategory)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:expense_categories,name,'.$expenseCategory->id],
            'nominal_account_id' => ['nullable', 'exists:nominal_accounts,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        $expenseCategory->update($data);

        return redirect()->route('expense-categories.index')->with('success', 'Expense category updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ExpenseCategory $expenseCategory)
    {
        $expenseCategory->delete();

        return back()->with('success', 'Expense category deleted.');
    }
}
