<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\SalesInvoice;
use App\Models\Salesman;
use App\Services\SalesmanLedgerService;
use App\Services\SettingsService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SalesmanController extends Controller
{
    public function index(): View
    {
        $salesmen = Salesman::query()->orderBy('name')->get();

        $shopsByCity = Customer::query()
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->selectRaw('city, COUNT(*) as shops')
            ->groupBy('city')
            ->pluck('shops', 'city');

        $monthRange = [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()];

        $monthSalesByCity = SalesInvoice::query()
            ->where('status', '!=', 'draft')
            ->whereBetween('invoice_date', $monthRange)
            ->join('customers', function ($join) {
                $join->on('customers.id', '=', 'sales_invoices.customer_id');
                if ($orgId = organization_id()) {
                    $join->where('customers.organization_id', $orgId);
                }
            })
            ->whereNotNull('customers.city')
            ->where('customers.city', '!=', '')
            ->selectRaw('customers.city, SUM(sales_invoices.total) as month_sales')
            ->groupBy('customers.city')
            ->pluck('month_sales', 'city');

        $monthSalesBySalesman = SalesInvoice::query()
            ->where('status', '!=', 'draft')
            ->whereBetween('invoice_date', $monthRange)
            ->whereNotNull('salesman_id')
            ->selectRaw('salesman_id, SUM(total) as month_sales, SUM(salesman_commission_amount) as month_commission')
            ->groupBy('salesman_id')
            ->get()
            ->keyBy('salesman_id');

        return view('salesmen.index', compact('salesmen', 'shopsByCity', 'monthSalesByCity', 'monthSalesBySalesman'));
    }

    public function create(): View
    {
        return view('salesmen.create', [
            'salesman' => new Salesman(['is_active' => true, 'monthly_target' => 0, 'opening_balance' => 0]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Salesman::create($this->validated($request));

        return redirect()->route('salesmen.index')->with('success', 'Salesman created.');
    }

    public function show(Salesman $salesman, SalesmanLedgerService $ledger, SettingsService $settings): View
    {
        $from = Carbon::parse('2000-01-01')->startOfDay();
        $to = now()->endOfDay();
        $result = $ledger->ledger($salesman, $from, $to);

        return view('reports.party-ledger', [
            'title' => 'Salesman Ledger',
            'settings' => $settings,
            'companyName' => $settings->companyName(),
            'printedAt' => now(),
            'period' => null,
            'fromDate' => $from,
            'toDate' => $to,
            'wide' => true,
            'subtitle' => $salesman->name,
            'partyName' => $salesman->name,
            'partyCode' => (string) (1000 + (int) $salesman->id),
            'accountType' => 'salesman',
            'accountTypeLabel' => 'Salesman',
            'entries' => $result['entries'],
            'openingBalance' => $result['openingBalance'],
            'closingBalance' => $result['closingBalance'],
            'totalDebit' => $result['totalDebit'],
            'totalCredit' => $result['totalCredit'],
            'backUrl' => route('salesmen.index'),
            'backLabel' => 'Back to Salesmen',
        ]);
    }

    public function edit(Salesman $salesman): View
    {
        $cityCustomers = $salesman->customersInCity()->get();

        $monthSales = (float) SalesInvoice::query()
            ->where('status', '!=', 'draft')
            ->whereBetween('invoice_date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
            ->where(function ($query) use ($salesman) {
                $query->where('salesman_id', $salesman->id);
                $cities = $salesman->cityList();
                if ($cities !== []) {
                    $query->orWhere(function ($cityQuery) use ($cities) {
                        $cityQuery->whereNull('salesman_id')
                            ->whereHas('customer', fn ($customer) => $customer->whereIn('city', $cities));
                    });
                }
            })
            ->sum('total');

        return view('salesmen.edit', compact('salesman', 'cityCustomers', 'monthSales'));
    }

    public function update(Request $request, Salesman $salesman): RedirectResponse
    {
        $salesman->update($this->validated($request, $salesman));

        return redirect()->route('salesmen.index')->with('success', 'Salesman updated.');
    }

    public function destroy(Salesman $salesman): RedirectResponse
    {
        $salesman->delete();

        return back()->with('success', 'Salesman deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Salesman $salesman = null): array
    {
        $usernameRule = Rule::unique('salesmen', 'username')
            ->where(fn ($query) => $query->where('organization_id', organization_id()));

        if ($salesman) {
            $usernameRule->ignore($salesman->id);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:100', $usernameRule],
            'password' => [$salesman ? 'nullable' : 'required', 'string', 'min:6', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:50'],
            'mobile' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'cities' => ['nullable', 'array'],
            'cities.*' => ['nullable', 'string', 'max:100'],
            'monthly_target' => ['nullable', 'numeric', 'min:0'],
            'opening_balance' => ['nullable', 'numeric'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['monthly_target'] = (float) ($data['monthly_target'] ?? 0);
        $data['opening_balance'] = (float) ($data['opening_balance'] ?? 0);
        $data['is_active'] = $request->boolean('is_active');

        foreach (['phone', 'mobile', 'email'] as $field) {
            if (($data[$field] ?? '') === '') {
                $data[$field] = null;
            }
        }

        $cities = [];
        foreach ($data['cities'] ?? [] as $city) {
            $city = trim((string) $city);
            if ($city !== '' && ! in_array($city, $cities, true)) {
                $cities[] = $city;
            }
        }
        $data['cities'] = $cities === [] ? null : $cities;
        $data['city'] = $cities[0] ?? null;

        if (filled($data['password'] ?? null)) {
            $data['show_password'] = $data['password'];
        } else {
            unset($data['password']);
        }

        return $data;
    }
}
