<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\CashVoucher;
use Illuminate\Http\Request;

class BankAccountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        BankAccount::ensureShopCash();

        $bankAccounts = BankAccount::orderByDesc('is_cash')->orderBy('name')->get();

        return view('bank-accounts.index', compact('bankAccounts'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('bank-accounts.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:255'],
            'opening_balance' => ['nullable', 'numeric'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['opening_balance'] = (float) ($data['opening_balance'] ?? 0);
        $data['current_balance'] = $data['opening_balance'];
        $data['is_active'] = (bool) ($data['is_active'] ?? true);
        $data['is_cash'] = false;

        BankAccount::create($data);

        return redirect()->route('bank-accounts.index')->with('success', 'Bank account created.');
    }

    /**
     * Display the specified resource.
     */
    public function show(BankAccount $bankAccount)
    {
        $cashVouchers = CashVoucher::with('cashAccount')
            ->where('payment_method', 'bank')
            ->where('bank_account_id', $bankAccount->id)
            ->orderByDesc('voucher_date')
            ->limit(500)
            ->get();

        CashVoucher::loadAccountNames($cashVouchers);

        return view('bank-accounts.show', compact('bankAccount', 'cashVouchers'));
    }

    public function balance(BankAccount $bankAccount)
    {
        return response()->json([
            'id' => $bankAccount->id,
            'name' => $bankAccount->name,
            'current_balance' => (float) $bankAccount->current_balance,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(BankAccount $bankAccount)
    {
        return view('bank-accounts.edit', compact('bankAccount'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, BankAccount $bankAccount)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:255'],
            'opening_balance' => ['nullable', 'numeric'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['opening_balance'] = (float) ($data['opening_balance'] ?? 0);
        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        $bankAccount->update($data);

        return redirect()->route('bank-accounts.index')->with('success', 'Bank account updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BankAccount $bankAccount)
    {
        if ($bankAccount->is_cash) {
            return back()->with('error', 'The shop cash account cannot be deleted.');
        }

        $bankAccount->delete();

        return back()->with('success', 'Bank account deleted.');
    }
}
