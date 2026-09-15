<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Services\PartyLedgerService;
use App\Services\SettingsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $suppliers = Supplier::orderBy('name')->get();

        return view('suppliers.index', compact('suppliers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('suppliers.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'vat_number' => ['nullable', 'string', 'max:32'],
            'city' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string'],
            'opening_balance' => ['nullable', 'numeric'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['opening_balance'] = (float) ($data['opening_balance'] ?? 0);
        $data['current_balance'] = $data['opening_balance'];
        $data['is_active'] = (bool) ($data['is_active'] ?? true);
        $data['city'] = ($data['city'] ?? '') === '' ? null : $data['city'];

        Supplier::create($data);

        return redirect()->route('suppliers.index')->with('success', 'Supplier created.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Supplier $supplier, PartyLedgerService $ledger): View
    {
        $from = Carbon::parse('2000-01-01')->startOfDay();
        $to = now()->endOfDay();
        $result = $ledger->ledger('supplier', (int) $supplier->id, $from, $to);

        return view('suppliers.show', [
            'supplier' => $supplier,
            'settings' => app(SettingsService::class),
            'entries' => $result['entries'],
            'openingBalance' => $result['openingBalance'],
            'closingBalance' => $result['closingBalance'],
            'totalDebit' => $result['totalDebit'],
            'totalCredit' => $result['totalCredit'],
            'printedAt' => now(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Supplier $supplier)
    {
        return view('suppliers.edit', compact('supplier'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Supplier $supplier)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'vat_number' => ['nullable', 'string', 'max:32'],
            'city' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string'],
            'opening_balance' => ['nullable', 'numeric'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['opening_balance'] = (float) ($data['opening_balance'] ?? 0);
        $data['is_active'] = (bool) ($data['is_active'] ?? false);
        $data['city'] = ($data['city'] ?? '') === '' ? null : $data['city'];
        $openingDelta = $data['opening_balance'] - (float) $supplier->opening_balance;
        $data['current_balance'] = (float) $supplier->current_balance + $openingDelta;

        $supplier->update($data);

        return redirect()->route('suppliers.index')->with('success', 'Supplier updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Supplier $supplier)
    {
        $supplier->delete();

        return back()->with('success', 'Supplier deleted.');
    }
}
