<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Services\PartyLedgerService;
use App\Services\SettingsService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(): View
    {
        $customers = Customer::orderByRaw('COALESCE(NULLIF(company_name, ""), name)')->orderBy('name')->get();

        return view('customers.index', compact('customers'));
    }

    public function create(): View
    {
        return view('customers.create', [
            'customer' => new Customer(['is_active' => true]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['is_active'] = $request->boolean('is_active');

        Customer::create($data);

        return redirect()->route('customers.index')->with('success', 'Customer created.');
    }

    public function show(Customer $customer, PartyLedgerService $ledger): View
    {
        $from = Carbon::parse('2000-01-01')->startOfDay();
        $to = now()->endOfDay();
        $result = $ledger->ledger('customer', (int) $customer->id, $from, $to);

        return view('customers.show', [
            'customer' => $customer,
            'settings' => app(SettingsService::class),
            'entries' => $result['entries'],
            'openingBalance' => $result['openingBalance'],
            'closingBalance' => $result['closingBalance'],
            'totalDebit' => $result['totalDebit'],
            'totalCredit' => $result['totalCredit'],
            'printedAt' => now(),
        ]);
    }

    public function edit(Customer $customer): View
    {
        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $data = $this->validated($request);
        $data['is_active'] = $request->boolean('is_active');

        $customer->update($data);

        return redirect()->route('customers.index')->with('success', 'Customer updated.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $customer->delete();

        return back()->with('success', 'Customer deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'proprietor_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'mobile' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'ntn' => ['nullable', 'string', 'max:50'],
            'strn' => ['nullable', 'string', 'max:50'],
            'license_no' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'area' => ['nullable', 'string', 'max:150'],
            'address' => ['nullable', 'string'],
            'opening_balance' => ['nullable', 'numeric'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['opening_balance'] = (float) ($data['opening_balance'] ?? 0);
        $data['vat_number'] = $data['strn'] ?? null;

        foreach (['company_name', 'proprietor_name', 'phone', 'mobile', 'email', 'ntn', 'strn', 'license_no', 'city', 'area', 'address'] as $field) {
            if (($data[$field] ?? '') === '') {
                $data[$field] = null;
            }
        }

        return $data;
    }
}
