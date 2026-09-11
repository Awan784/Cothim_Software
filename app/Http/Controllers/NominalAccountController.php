<?php

namespace App\Http\Controllers;

use App\Models\NominalAccount;
use Illuminate\Http\Request;

class NominalAccountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $nominalAccounts = NominalAccount::with('parent')->orderBy('type')->orderBy('name')->get();

        return view('nominal-accounts.index', compact('nominalAccounts'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $nominalAccounts = NominalAccount::orderBy('type')->orderBy('name')->get();
        $types = ['asset', 'liability', 'equity', 'income', 'expense'];

        return view('nominal-accounts.create', compact('nominalAccounts', 'types'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'parent_id' => ['nullable', 'exists:nominal_accounts,id'],
            'code' => ['nullable', 'string', 'max:255', 'unique:nominal_accounts,code'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:asset,liability,equity,income,expense'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = (bool) ($data['is_active'] ?? true);

        NominalAccount::create($data);

        return redirect()->route('nominal-accounts.index')->with('success', 'Nominal account created.');
    }

    /**
     * Display the specified resource.
     */
    public function show(NominalAccount $nominalAccount)
    {
        return redirect()->route('nominal-accounts.edit', $nominalAccount);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(NominalAccount $nominalAccount)
    {
        $nominalAccounts = NominalAccount::whereKeyNot($nominalAccount->id)->orderBy('type')->orderBy('name')->get();
        $types = ['asset', 'liability', 'equity', 'income', 'expense'];

        return view('nominal-accounts.edit', compact('nominalAccount', 'nominalAccounts', 'types'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, NominalAccount $nominalAccount)
    {
        $data = $request->validate([
            'parent_id' => ['nullable', 'exists:nominal_accounts,id'],
            'code' => ['nullable', 'string', 'max:255', 'unique:nominal_accounts,code,'.$nominalAccount->id],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:asset,liability,equity,income,expense'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        $nominalAccount->update($data);

        return redirect()->route('nominal-accounts.index')->with('success', 'Nominal account updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(NominalAccount $nominalAccount)
    {
        $nominalAccount->delete();

        return back()->with('success', 'Nominal account deleted.');
    }
}
