<?php

namespace App\Http\Controllers;

use App\Models\CashVoucher;
use App\Models\Investor;
use Illuminate\Http\Request;

class InvestorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $investors = Investor::orderBy('name')->get();

        return view('investors.index', compact('investors'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('investors.create');
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
            'address' => ['nullable', 'string'],
            'opening_investment' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['opening_investment'] = (float) ($data['opening_investment'] ?? 0);
        $data['is_active'] = (bool) ($data['is_active'] ?? true);

        Investor::create($data);

        return redirect()->route('investors.index')->with('success', 'Investor created.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Investor $investor)
    {
        $vouchers = CashVoucher::where('account_type', 'investor')
            ->where('account_id', $investor->id)
            ->orderByDesc('voucher_date')
            ->orderByDesc('id')
            ->limit(500)
            ->get();

        return view('investors.show', compact('investor', 'vouchers'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Investor $investor)
    {
        return view('investors.edit', compact('investor'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Investor $investor)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'opening_investment' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['opening_investment'] = (float) ($data['opening_investment'] ?? 0);
        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        $investor->update($data);

        return redirect()->route('investors.index')->with('success', 'Investor updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Investor $investor)
    {
        $investor->delete();

        return back()->with('success', 'Investor deleted.');
    }
}
