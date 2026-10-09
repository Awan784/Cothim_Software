<?php

namespace App\Http\Controllers;

use App\Models\CashVoucher;
use App\Models\PurchaseOrder;
use App\Models\Vendor;
use App\Services\PartyLedgerService;
use App\Services\SettingsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VendorController extends Controller
{
    public function index()
    {
        $vendors = Vendor::orderBy('name')->get();

        return view('vendors.index', compact('vendors'));
    }

    public function create()
    {
        return view('vendors.create');
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['current_balance'] = $data['opening_balance'];

        Vendor::create($data);

        return redirect()->route('vendors.index')->with('success', 'Vendor created.');
    }

    public function show(Vendor $vendor, PartyLedgerService $ledger): View
    {
        $from = Carbon::parse('2000-01-01')->startOfDay();
        $to = now()->endOfDay();
        $result = $ledger->ledger('vendor', (int) $vendor->id, $from, $to);

        return view('suppliers.show', [
            'party' => $vendor,
            'partyKind' => 'Vendor',
            'listUrl' => route('vendors.index'),
            'editUrl' => route('vendors.edit', $vendor),
            'settings' => app(SettingsService::class),
            'entries' => $result['entries'],
            'openingBalance' => $result['openingBalance'],
            'closingBalance' => $result['closingBalance'],
            'totalDebit' => $result['totalDebit'],
            'totalCredit' => $result['totalCredit'],
            'printedAt' => now(),
        ]);
    }

    public function edit(Vendor $vendor)
    {
        return view('vendors.edit', compact('vendor'));
    }

    public function update(Request $request, Vendor $vendor)
    {
        $data = $this->validated($request, false);
        $openingDelta = $data['opening_balance'] - (float) $vendor->opening_balance;
        $data['current_balance'] = (float) $vendor->current_balance + $openingDelta;

        $vendor->update($data);

        return redirect()->route('vendors.index')->with('success', 'Vendor updated.');
    }

    public function destroy(Vendor $vendor)
    {
        if (PurchaseOrder::query()->where('vendor_id', $vendor->id)->exists()
            || CashVoucher::query()->where('account_type', 'vendor')->where('account_id', $vendor->id)->exists()) {
            return back()->with('error', 'This vendor has purchases or cash vouchers. Remove those first.');
        }

        $vendor->delete();

        return back()->with('success', 'Vendor deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, bool $defaultActive = true): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string'],
            'opening_balance' => ['nullable', 'numeric'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['opening_balance'] = (float) ($data['opening_balance'] ?? 0);
        $data['is_active'] = (bool) ($data['is_active'] ?? $defaultActive);
        $data['city'] = ($data['city'] ?? '') === '' ? null : $data['city'];

        return $data;
    }
};
