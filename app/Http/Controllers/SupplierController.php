<?php

namespace App\Http\Controllers;

use App\Models\CashVoucher;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Http\Request;

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
        $data['is_active'] = (bool) ($data['is_active'] ?? true);
        $data['city'] = ($data['city'] ?? '') === '' ? null : $data['city'];

        Supplier::create($data);

        return redirect()->route('suppliers.index')->with('success', 'Supplier created.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Supplier $supplier)
    {
        $purchaseOrders = PurchaseOrder::where('supplier_id', $supplier->id)
            ->orderByDesc('po_date')
            ->orderByDesc('id')
            ->get();

        $cashVouchers = CashVoucher::where('account_type', 'supplier')
            ->where('account_id', $supplier->id)
            ->orderByDesc('voucher_date')
            ->orderByDesc('id')
            ->get();

        $entries = collect();

        foreach ($purchaseOrders as $po) {
            $entries->push([
                'date' => $po->po_date,
                'ref' => $po->po_no,
                'type' => 'purchase',
                'description' => 'Purchase order',
                'debit' => 0.0,
                'credit' => (float) $po->total_amount,
            ]);
        }

        foreach ($cashVouchers as $v) {
            $entries->push([
                'date' => $v->voucher_date,
                'ref' => $v->voucher_no,
                'type' => 'cash_'.$v->type,
                'description' => 'Cash voucher ('.strtoupper($v->type).')',
                // For supplier payable:
                // - payment reduces payable -> debit
                // - receive increases payable -> credit
                'debit' => $v->type === 'payment' ? (float) $v->amount : 0.0,
                'credit' => $v->type === 'receive' ? (float) $v->amount : 0.0,
            ]);
        }

        $entries = $entries
            ->sortBy([
                ['date', 'asc'],
                ['ref', 'asc'],
            ])
            ->values();

        $running = 0.0;
        $entries = $entries->map(function ($e) use (&$running) {
            $running += ((float) $e['credit']) - ((float) $e['debit']);
            $e['balance'] = $running;

            return $e;
        });

        return view('suppliers.show', compact('supplier', 'entries'));
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
