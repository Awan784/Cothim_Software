<?php

namespace App\Http\Controllers;

use App\Models\CashAccount;
use App\Models\CashVoucher;
use Illuminate\Http\Request;

class CashAccountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $cashAccounts = CashAccount::orderBy('name')->get();

        return view('cash-accounts.index', compact('cashAccounts'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('cash-accounts.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:cash_accounts,name'],
            'opening_balance' => ['nullable', 'numeric'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['opening_balance'] = (float) ($data['opening_balance'] ?? 0);
        $data['current_balance'] = $data['opening_balance'];
        $data['is_active'] = (bool) ($data['is_active'] ?? true);

        CashAccount::create($data);

        return redirect()->route('cash-accounts.index')->with('success', 'Cash account created.');
    }

    /**
     * Display the specified resource.
     */
    public function show(CashAccount $cashAccount)
    {
        $vouchers = CashVoucher::where('cash_account_id', $cashAccount->id)
            ->orderByDesc('voucher_date')
            ->orderByDesc('id')
            ->limit(500)
            ->get();

        return view('cash-accounts.show', compact('cashAccount', 'vouchers'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CashAccount $cashAccount)
    {
        return view('cash-accounts.edit', compact('cashAccount'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CashAccount $cashAccount)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:cash_accounts,name,'.$cashAccount->id],
            'opening_balance' => ['nullable', 'numeric'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['opening_balance'] = (float) ($data['opening_balance'] ?? 0);
        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        $cashAccount->update($data);

        return redirect()->route('cash-accounts.index')->with('success', 'Cash account updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CashAccount $cashAccount)
    {
        $cashAccount->delete();

        return back()->with('success', 'Cash account deleted.');
    }
}
