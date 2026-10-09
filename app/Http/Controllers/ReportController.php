<?php

namespace App\Http\Controllers;

use App\Models\CashVoucher;
use App\Models\Customer;
use App\Models\ExpenseAccount;
use App\Models\PurchaseOrder;
use App\Models\PurchaseReturn;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceLine;
use App\Models\Salesman;
use App\Models\SalesOrder;
use App\Models\SalesReturn;
use App\Models\StockCategory;
use App\Models\StockItem;
use App\Models\Supplier;
use App\Services\CashRegisterService;
use App\Services\JournalReportService;
use App\Services\PartyLedgerService;
use App\Services\SalesmanSettlementService;
use App\Services\SettingsService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\View\View;

class ReportController extends Controller
{
    public static function catalog(): array
    {
        return [
            ['slug' => 'account-receivables', 'title' => 'A/c Receivables', 'color' => 'orange', 'group' => 'Accounts', 'filter' => 'none'],
            ['slug' => 'party-ledger', 'title' => 'Party Ledger', 'color' => 'teal', 'group' => 'Accounts', 'filter' => 'party-ledger'],
            ['slug' => 'cash-register', 'title' => 'Cash Register', 'color' => 'green', 'group' => 'Accounts', 'filter' => 'cash-register'],
            ['slug' => 'journal-report', 'title' => 'Journal Report', 'color' => 'pink', 'group' => 'Accounts', 'filter' => 'journal-report'],
            ['slug' => 'customers', 'title' => 'Customers', 'color' => 'blue', 'group' => 'Accounts', 'filter' => 'none'],
            ['slug' => 'payables', 'title' => 'Supplier Payables', 'color' => 'navy', 'group' => 'Accounts', 'filter' => 'none'],
            ['slug' => 'stock', 'title' => 'Stock Report', 'color' => 'teal', 'group' => 'Stock', 'filter' => 'none'],
            ['slug' => 'stock-category', 'title' => 'Stock by Category', 'color' => 'blue', 'group' => 'Stock', 'filter' => 'none'],
            ['slug' => 'low-stock', 'title' => 'Low Stock', 'color' => 'pink', 'group' => 'Stock', 'filter' => 'none'],
            ['slug' => 'sales-invoices', 'title' => 'Sales Invoices', 'color' => 'green', 'group' => 'Sales', 'filter' => 'dates'],
            ['slug' => 'sales-orders', 'title' => 'Sales Orders', 'color' => 'teal', 'group' => 'Sales', 'filter' => 'dates'],
            ['slug' => 'sales-returns', 'title' => 'Sales Returns', 'color' => 'pink', 'group' => 'Sales', 'filter' => 'dates'],
            ['slug' => 'salesman-report', 'title' => 'Salesman Report', 'color' => 'orange', 'group' => 'Sales', 'filter' => 'dates_salesman'],
            ['slug' => 'salesman-commission', 'title' => 'Salesman Commission', 'color' => 'orange', 'group' => 'Sales', 'filter' => 'salesman-commission'],
            ['slug' => 'salesman-product', 'title' => 'Salesman Product Sales', 'color' => 'green', 'group' => 'Sales', 'filter' => 'salesman-product'],
            ['slug' => 'purchase-orders', 'title' => 'Purchase Orders', 'color' => 'navy', 'group' => 'Purchases', 'filter' => 'dates'],
            ['slug' => 'purchase-returns', 'title' => 'Purchase Returns', 'color' => 'purple', 'group' => 'Purchases', 'filter' => 'dates'],
            ['slug' => 'expenses', 'title' => 'Expenses', 'color' => 'amber', 'group' => 'Expenses & Period', 'filter' => 'dates'],
            ['slug' => 'monthly-sheet', 'title' => 'Monthly Sheet', 'color' => 'purple', 'group' => 'Expenses & Period', 'filter' => 'month'],
        ];
    }

    public function index(): View
    {
        return view('reports.index', [
            'reports' => collect(self::catalog()),
            'salesmen' => Salesman::query()->orderBy('name')->get(['id', 'name', 'city', 'cities']),
            'stockCategories' => StockCategory::query()->orderBy('name')->get(['id', 'name']),
            'stockItems' => StockItem::query()->orderBy('name')->get(['id', 'name', 'stock_category_id']),
            'salesmanCities' => Salesman::query()->get(['city', 'cities'])
                ->flatMap(fn (Salesman $salesman) => $salesman->cityList())
                ->unique()
                ->sort(fn ($a, $b) => strnatcasecmp((string) $a, (string) $b))
                ->values(),
        ]);
    }

    public function show(string $report, Request $request): View
    {
        $item = collect(self::catalog())->firstWhere('slug', $report);

        abort_unless($item, 404);

        return match ($report) {
            'account-receivables' => $this->accountReceivablesReport($item),
            'customers' => $this->customersReport($item),
            'payables' => $this->payablesReport($item),
            'stock' => $this->stockReport($item),
            'stock-category' => $this->stockByCategoryReport($item),
            'low-stock' => $this->lowStockReport($item),
            'sales-invoices' => $this->salesInvoicesReport($item, $request),
            'sales-orders' => $this->salesOrdersReport($item, $request),
            'sales-returns' => $this->salesReturnsReport($item, $request),
            'salesman-report' => $this->salesmanReport($item, $request),
            'salesman-product' => $this->salesmanProductReport($item, $request),
            'purchase-orders' => $this->purchaseOrdersReport($item, $request),
            'purchase-returns' => $this->purchaseReturnsReport($item, $request),
            'expenses' => $this->expensesReport($item, $request),
            'monthly-sheet' => $this->monthlySheetReport($item, $request),
            default => abort(404),
        };
    }

    public function partyLedgerAccounts(Request $request, PartyLedgerService $ledger): JsonResponse
    {
        $data = $request->validate([
            'account_type' => ['required', 'string', 'in:'.implode(',', array_keys(PartyLedgerService::ACCOUNT_TYPES))],
        ]);

        $accounts = $ledger->accountsForType($data['account_type'])
            ->map(fn ($row) => ['id' => $row->id, 'name' => $row->name]);

        return response()->json($accounts);
    }

    public function partyLedger(Request $request, PartyLedgerService $ledger): View
    {
        $data = $request->validate([
            'account_type' => ['required', 'string', 'in:'.implode(',', array_keys(PartyLedgerService::ACCOUNT_TYPES))],
            'account_id' => ['required', 'integer', 'min:1'],
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
        ]);

        $from = Carbon::parse($data['from_date'])->startOfDay();
        $to = Carbon::parse($data['to_date'])->endOfDay();

        $result = $ledger->ledger($data['account_type'], (int) $data['account_id'], $from, $to);
        $partyName = $ledger->partyName($data['account_type'], (int) $data['account_id']);

        return view('reports.party-ledger', $this->printData(
            ['title' => 'Party Ledger'],
            $from,
            $to,
            [
                'wide' => true,
                'subtitle' => $partyName,
                'partyName' => $partyName,
                'partyCode' => $ledger->partyCode($data['account_type'], (int) $data['account_id']),
                'accountType' => $data['account_type'],
                'accountTypeLabel' => PartyLedgerService::ACCOUNT_TYPES[$data['account_type']],
                'entries' => $result['entries'],
                'openingBalance' => $result['openingBalance'],
                'closingBalance' => $result['closingBalance'],
                'totalDebit' => $result['totalDebit'],
                'totalCredit' => $result['totalCredit'],
                'backUrl' => route('reports.index'),
                'backLabel' => 'Back to Reports',
            ]
        ));
    }

    public function journalReport(Request $request, JournalReportService $journalReport): View
    {
        $data = $request->validate([
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
        ]);

        $from = Carbon::parse($data['from_date'])->startOfDay();
        $to = Carbon::parse($data['to_date'])->endOfDay();

        $result = $journalReport->report($from, $to);

        return view('reports.journal-report', $this->printData(
            ['title' => 'Journal Report'],
            $from,
            $to,
            [
                'vouchers' => $result['vouchers'],
                'grandTotalDebit' => $result['grandTotalDebit'],
                'grandTotalCredit' => $result['grandTotalCredit'],
                'voucherCount' => $result['voucherCount'],
                'lineCount' => $result['lineCount'],
            ]
        ));
    }

    public function cashRegister(Request $request, CashRegisterService $register): View
    {
        $data = $request->validate([
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
        ]);

        $from = Carbon::parse($data['from_date'])->startOfDay();
        $to = Carbon::parse($data['to_date'])->endOfDay();

        $result = $register->register($from, $to);

        return view('reports.cash-register', $this->printData(
            ['title' => 'Cash Register'],
            $from,
            $to,
            [
                'wide' => true,
                'entries' => $result['entries'],
                'closingBalance' => $result['closingBalance'],
                'totalCashIn' => $result['totalCashIn'],
                'totalCashOut' => $result['totalCashOut'],
            ]
        ));
    }

    public function salesmanCommission(Request $request): View
    {
        $data = $request->validate([
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            'salesman_id' => ['required', 'integer', 'exists:salesmen,id'],
            'builty_expenses' => ['nullable', 'numeric', 'gte:0'],
            'previous_advance' => ['nullable', 'numeric', 'gte:0'],
        ]);

        $from = Carbon::parse($data['from_date'])->startOfDay();
        $to = Carbon::parse($data['to_date'])->endOfDay();
        $salesman = Salesman::query()->findOrFail($data['salesman_id']);
        $fromStr = $from->toDateString();
        $toStr = $to->toDateString();

        $inPeriod = function ($query) use ($from, $to, $fromStr, $toStr) {
            $query->where(function ($inner) use ($from, $to, $fromStr, $toStr) {
                $inner->whereBetween('invoice_date', [$fromStr, $toStr])
                    ->orWhereBetween('created_at', [$from, $to])
                    ->orWhereHas('salesOrder', function ($order) use ($from, $to, $fromStr, $toStr) {
                        $order->whereBetween('order_date', [$fromStr, $toStr])
                            ->orWhereBetween('created_at', [$from, $to]);
                    });
            });
        };

        $invoices = SalesInvoice::with(['customer', 'salesman', 'salesOrder'])
            ->where('status', '!=', 'draft')
            ->where(function ($query) use ($salesman) {
                $query->where('salesman_id', $salesman->id)
                    ->orWhereHas('salesOrder', fn ($order) => $order->where('salesman_id', $salesman->id));
            })
            ->where($inPeriod)
            ->orderBy('invoice_date')
            ->orderBy('id')
            ->get();

        $openOrders = SalesOrder::with('customer')
            ->where('salesman_id', $salesman->id)
            ->whereNull('sales_invoice_id')
            ->where('status', '!=', SalesOrder::STATUS_REJECTED)
            ->where(function ($query) use ($from, $to, $fromStr, $toStr) {
                $query->whereBetween('order_date', [$fromStr, $toStr])
                    ->orWhereBetween('created_at', [$from, $to]);
            })
            ->orderBy('order_date')
            ->orderBy('id')
            ->get();

        $rows = $invoices->map(function (SalesInvoice $invoice) {
            $date = $invoice->invoice_date;
            if ($invoice->created_at && $date && abs((int) $date->year - (int) $invoice->created_at->year) > 1) {
                $date = $invoice->created_at;
            }

            return [
                'date' => $date,
                'bill_no' => $invoice->invoice_no,
                'builty_postal' => $invoice->builty_postal ?: $invoice->salesOrder?->builty_postal,
                'builty_exp' => (float) ($invoice->builty_exp ?? $invoice->salesOrder?->builty_exp ?? 0),
                'party' => $invoice->customer?->displayName() ?: '—',
                'city' => $invoice->customer?->city ?: '',
                'amount' => (float) $invoice->total,
                'commission' => (float) $invoice->salesman_commission_amount,
            ];
        })->concat($openOrders->map(function (SalesOrder $order) {
            $date = $order->order_date;
            if ($order->created_at && $date && abs((int) $date->year - (int) $order->created_at->year) > 1) {
                $date = $order->created_at;
            }

            return [
                'date' => $date,
                'bill_no' => $order->order_no,
                'builty_postal' => $order->builty_postal,
                'builty_exp' => (float) ($order->builty_exp ?? 0),
                'party' => $order->customer?->displayName() ?: '—',
                'city' => $order->customer?->city ?: '',
                'amount' => (float) $order->total,
                'commission' => (float) $order->salesman_commission_amount,
            ];
        }))->sortBy(function (array $row) {
            $date = $row['date'] ?? null;

            return $date instanceof Carbon ? $date->timestamp : 0;
        })->values();

        $totalSales = round((float) $rows->sum('amount'), 2);
        $totalCommission = round((float) $rows->sum('commission'), 2);
        $builtyExpenses = round((float) $rows->sum('builty_exp') + (float) ($data['builty_expenses'] ?? 0), 2);
        $previousAdvance = array_key_exists('previous_advance', $data) && $data['previous_advance'] !== null
            ? round((float) $data['previous_advance'], 2)
            : app(SalesmanSettlementService::class)->currentAdvance($salesman);
        $afterCommission = round($totalSales - $totalCommission, 2);
        $afterBuilty = round($afterCommission - $builtyExpenses, 2);
        $netReceivable = round($afterBuilty - $previousAdvance, 2);

        $settings = app(SettingsService::class);

        return view('reports.salesman-commission', [
            'settings' => $settings,
            'companyName' => $settings->companyName(),
            'printedAt' => now(),
            'fromDate' => $from,
            'toDate' => $to,
            'salesman' => $salesman,
            'rows' => $rows,
            'invoices' => $invoices,
            'totalSales' => $totalSales,
            'totalCommission' => $totalCommission,
            'builtyExpenses' => $builtyExpenses,
            'previousAdvance' => $previousAdvance,
            'afterCommission' => $afterCommission,
            'afterBuilty' => $afterBuilty,
            'netReceivable' => $netReceivable,
        ]);
    }

    private function accountReceivablesReport(array $item): View
    {
        $salesmen = Salesman::query()->orderBy('name')->get()->keyBy('id');

        $unpaid = SalesInvoice::query()
            ->with('salesOrder:id,salesman_id')
            ->where('status', '!=', 'draft')
            ->whereRaw('(total - amount_paid) > 0.009')
            ->where(function ($query) {
                $query->whereNotNull('salesman_id')
                    ->orWhereHas('salesOrder', fn ($order) => $order->whereNotNull('salesman_id'));
            })
            ->get(['id', 'salesman_id', 'sales_order_id', 'total', 'amount_paid', 'salesman_commission_amount']);

        $totals = [];
        foreach ($unpaid as $invoice) {
            $salesmanId = (int) ($invoice->salesman_id ?: $invoice->salesOrder?->salesman_id);
            if ($salesmanId < 1) {
                continue;
            }

            $due = $invoice->balanceDue();
            $total = (float) $invoice->total;
            $commission = (float) $invoice->salesman_commission_amount;
            $commissionDue = $total > 0.009 ? round($commission * ($due / $total), 2) : 0.0;

            if (! isset($totals[$salesmanId])) {
                $totals[$salesmanId] = ['invoices' => 0, 'sales' => 0.0, 'commission' => 0.0];
            }

            $totals[$salesmanId]['invoices']++;
            $totals[$salesmanId]['sales'] = round($totals[$salesmanId]['sales'] + $due, 2);
            $totals[$salesmanId]['commission'] = round($totals[$salesmanId]['commission'] + $commissionDue, 2);
        }

        $rows = $salesmen->map(function (Salesman $salesman) use ($totals) {
            $row = $totals[$salesman->id] ?? ['invoices' => 0, 'sales' => 0.0, 'commission' => 0.0];
            $sales = round((float) $row['sales'], 2);
            $commission = round((float) $row['commission'], 2);
            $net = round($sales - $commission, 2);

            return [
                'code' => 1000 + (int) $salesman->id,
                'salesman' => $salesman->name,
                'city' => $salesman->citiesLabel() ?: '',
                'invoices' => (int) $row['invoices'],
                'sales' => $sales,
                'commission' => $commission,
                'debit' => $net > 0.005 ? $net : 0.0,
                'credit' => $net < -0.005 ? abs($net) : 0.0,
            ];
        })->values();

        return view('reports.account-receivables', $this->printData($item, extra: [
            'subtitle' => 'Unpaid salesman invoices · less commission',
            'rows' => $rows,
            'totalSales' => (float) $rows->sum('sales'),
            'totalCommission' => (float) $rows->sum('commission'),
            'totalDebit' => (float) $rows->sum('debit'),
            'totalCredit' => (float) $rows->sum('credit'),
        ]));
    }

    private function customersReport(array $item): View
    {
        $customers = Customer::query()->orderByRaw('COALESCE(NULLIF(company_name, ""), name)')->get();

        $rows = $customers->map(fn (Customer $customer) => [
            'name' => $customer->displayName(),
            'city' => $customer->city ?: '—',
            'phone' => $customer->mobile ?: ($customer->phone ?: '—'),
            'opening' => (float) $customer->opening_balance,
            'balance' => (float) $customer->current_balance,
            'status' => $customer->is_active ? 'Active' : 'Inactive',
        ]);

        return view('reports.simple', $this->printData($item, extra: [
            'columns' => [
                'name' => 'Customer',
                'city' => 'City',
                'phone' => 'Phone',
                'opening' => 'Opening',
                'balance' => 'Balance',
                'status' => 'Status',
            ],
            'numeric' => ['opening', 'balance'],
            'rows' => $rows,
            'footer' => [
                'name' => 'Totals · '.$rows->count().' customers',
                'opening' => (float) $rows->sum('opening'),
                'balance' => (float) $rows->sum('balance'),
            ],
            'empty' => 'No customers found.',
        ]));
    }

    private function payablesReport(array $item): View
    {
        $suppliers = Supplier::query()->orderBy('name')->get();

        $rows = $suppliers->map(fn (Supplier $supplier) => [
            'name' => $supplier->name,
            'city' => $supplier->city ?: '—',
            'phone' => $supplier->phone ?: '—',
            'opening' => (float) $supplier->opening_balance,
            'balance' => (float) $supplier->current_balance,
            'status' => $supplier->is_active ? 'Active' : 'Inactive',
        ]);

        return view('reports.simple', $this->printData($item, extra: [
            'columns' => [
                'name' => 'Supplier',
                'city' => 'City',
                'phone' => 'Phone',
                'opening' => 'Opening',
                'balance' => 'Balance',
                'status' => 'Status',
            ],
            'numeric' => ['opening', 'balance'],
            'rows' => $rows,
            'footer' => [
                'name' => 'Totals · '.$rows->count().' suppliers',
                'opening' => (float) $rows->sum('opening'),
                'balance' => (float) $rows->sum('balance'),
            ],
            'empty' => 'No suppliers found.',
        ]));
    }

    private function stockReport(array $item): View
    {
        $items = $this->stockItemsForReport();
        $rows = $this->stockItemRows($items);

        return view('reports.simple', $this->printData($item, extra: [
            'wide' => true,
            'subtitle' => 'All stock with batches',
            'columns' => $this->stockColumns(compact: true),
            'numeric' => $this->stockNumeric(compact: true),
            'rows' => $rows,
            'footer' => $this->stockFooter($rows, 'Totals · '.$rows->count().' items'),
            'empty' => 'No stock items found.',
        ]));
    }

    private function stockByCategoryReport(array $item): View
    {
        $items = $this->stockItemsForReport();

        $groups = $items
            ->groupBy(fn (StockItem $stock) => $stock->stockCategory?->name ?: 'Uncategorized')
            ->sortKeys()
            ->map(function (Collection $groupItems, string $title) {
                $rows = $this->stockItemRows($groupItems);

                return [
                    'title' => $title.' · '.$rows->count().' items',
                    'rows' => $rows,
                    'footer' => $this->stockFooter($rows, 'Subtotal'),
                ];
            })
            ->values();

        $allRows = $this->stockItemRows($items);

        return view('reports.grouped', $this->printData($item, extra: [
            'wide' => true,
            'subtitle' => 'Items with batches',
            'columns' => $this->stockColumns(),
            'numeric' => $this->stockNumeric(),
            'groups' => $groups,
            'footer' => $this->stockFooter($allRows, 'Grand total'),
            'empty' => 'No stock items found.',
        ]));
    }

    private function lowStockReport(array $item): View
    {
        $items = $this->stockItemsForReport(lowStock: true);

        $rows = $this->stockItemRows($items);

        return view('reports.simple', $this->printData($item, extra: [
            'wide' => true,
            'subtitle' => 'Items with batches',
            'columns' => $this->stockColumns(),
            'numeric' => $this->stockNumeric(),
            'rows' => $rows,
            'footer' => $this->stockFooter($rows, 'Totals · '.$rows->count().' items'),
            'empty' => 'No low-stock items.',
        ]));
    }

    private function salesInvoicesReport(array $item, Request $request): View
    {
        [$from, $to] = $this->period($request);

        $invoices = SalesInvoice::with(['customer', 'salesman'])
            ->where('status', '!=', 'draft')
            ->whereBetween('invoice_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('invoice_date')
            ->orderBy('id')
            ->get();

        $rows = $invoices->map(fn (SalesInvoice $invoice) => [
            'date' => ams_date($invoice->invoice_date),
            'no' => $invoice->invoice_no,
            'customer' => $invoice->customer?->displayName() ?: '—',
            'salesman' => $invoice->salesman?->name ?: '—',
            'status' => $invoice->listStatusLabel(),
            'subtotal' => (float) $invoice->subtotal,
            'tax' => (float) $invoice->vat_amount,
            'total' => (float) $invoice->total,
            'paid' => (float) $invoice->amount_paid,
            'balance' => $invoice->balanceDue(),
        ]);

        return view('reports.simple', $this->printData($item, $from, $to, [
            'wide' => true,
            'columns' => [
                'date' => 'Date',
                'no' => 'Invoice',
                'customer' => 'Customer',
                'salesman' => 'Salesman',
                'status' => 'Status',
                'subtotal' => 'Subtotal',
                'tax' => 'Tax',
                'total' => 'Total',
                'paid' => 'Paid',
                'balance' => 'Balance',
            ],
            'numeric' => ['subtotal', 'tax', 'total', 'paid', 'balance'],
            'rows' => $rows,
            'footer' => [
                'date' => 'Totals',
                'subtotal' => (float) $rows->sum('subtotal'),
                'tax' => (float) $rows->sum('tax'),
                'total' => (float) $rows->sum('total'),
                'paid' => (float) $rows->sum('paid'),
                'balance' => (float) $rows->sum('balance'),
            ],
            'empty' => 'No sales invoices in this period.',
        ]));
    }

    private function salesOrdersReport(array $item, Request $request): View
    {
        [$from, $to] = $this->period($request);

        $orders = SalesOrder::with(['customer', 'salesman', 'invoice'])
            ->whereBetween('order_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('order_date')
            ->orderBy('id')
            ->get();

        $rows = $orders->map(fn (SalesOrder $order) => [
            'date' => ams_date($order->order_date),
            'no' => $order->order_no,
            'salesman' => $order->salesman?->name ?: '—',
            'customer' => $order->customer?->displayName() ?: '—',
            'status' => $order->statusLabel(),
            'subtotal' => (float) $order->subtotal,
            'tax' => (float) $order->vat_amount,
            'total' => (float) $order->total,
            'invoice' => $order->invoice?->invoice_no ?: '—',
        ]);

        return view('reports.simple', $this->printData($item, $from, $to, [
            'wide' => true,
            'columns' => [
                'date' => 'Date',
                'no' => 'Order',
                'salesman' => 'Salesman',
                'customer' => 'Customer',
                'status' => 'Status',
                'subtotal' => 'Subtotal',
                'tax' => 'Tax',
                'total' => 'Total',
                'invoice' => 'Invoice',
            ],
            'numeric' => ['subtotal', 'tax', 'total'],
            'rows' => $rows,
            'footer' => [
                'date' => 'Totals',
                'subtotal' => (float) $rows->sum('subtotal'),
                'tax' => (float) $rows->sum('tax'),
                'total' => (float) $rows->sum('total'),
            ],
            'empty' => 'No sales orders in this period.',
        ]));
    }

    private function salesReturnsReport(array $item, Request $request): View
    {
        [$from, $to] = $this->period($request);

        $returns = SalesReturn::with(['customer', 'items'])
            ->whereBetween('return_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('return_date')
            ->orderBy('id')
            ->get();

        $rows = $returns->map(fn (SalesReturn $return) => [
            'date' => ams_date($return->return_date),
            'no' => $return->return_no,
            'customer' => $return->customer?->displayName() ?: '—',
            'items' => $return->items->count(),
            'total' => (float) $return->total_amount,
            'notes' => $return->notes ?: '—',
        ]);

        return view('reports.simple', $this->printData($item, $from, $to, [
            'columns' => [
                'date' => 'Date',
                'no' => 'Return',
                'customer' => 'Customer',
                'items' => 'Items',
                'total' => 'Total',
                'notes' => 'Notes',
            ],
            'numeric' => ['items', 'total'],
            'rows' => $rows,
            'footer' => [
                'date' => 'Totals',
                'items' => (float) $rows->sum('items'),
                'total' => (float) $rows->sum('total'),
            ],
            'empty' => 'No sales returns in this period.',
        ]));
    }

    private function salesmanReport(array $item, Request $request): View
    {
        [$from, $to] = $this->period($request);
        $salesmanId = $request->integer('salesman_id') ?: null;

        $salesmen = Salesman::query()
            ->orderBy('name')
            ->when($salesmanId, fn ($query) => $query->where('id', $salesmanId))
            ->get();

        $invoices = SalesInvoice::query()
            ->where('status', '!=', 'draft')
            ->whereNotNull('salesman_id')
            ->whereBetween('invoice_date', [$from->toDateString(), $to->toDateString()])
            ->when($salesmanId, fn ($query) => $query->where('salesman_id', $salesmanId))
            ->get()
            ->groupBy('salesman_id');

        $orders = SalesOrder::query()
            ->whereBetween('order_date', [$from->toDateString(), $to->toDateString()])
            ->when($salesmanId, fn ($query) => $query->where('salesman_id', $salesmanId))
            ->get()
            ->groupBy('salesman_id');

        $rows = $salesmen->map(function (Salesman $salesman) use ($invoices, $orders) {
            $salesmanInvoices = $invoices->get($salesman->id, collect());
            $salesmanOrders = $orders->get($salesman->id, collect());
            $sales = (float) $salesmanInvoices->sum('total');
            $target = (float) $salesman->monthly_target;
            $achievement = $target > 0 ? round(($sales / $target) * 100, 1).'%' : '—';

            return [
                'name' => $salesman->name,
                'city' => $salesman->citiesLabel() ?: '—',
                'orders' => $salesmanOrders->count(),
                'order_total' => (float) $salesmanOrders->sum('total'),
                'invoices' => $salesmanInvoices->count(),
                'sales' => $sales,
                'commission' => (float) $salesmanInvoices->sum('salesman_commission_amount'),
                'target' => $target,
                'achievement' => $achievement,
            ];
        });

        $salesman = $salesmanId ? $salesmen->first() : null;

        return view('reports.simple', $this->printData($item, $from, $to, [
            'wide' => true,
            'subtitle' => $salesman?->name,
            'columns' => [
                'name' => 'Salesman',
                'city' => 'City',
                'orders' => 'Orders',
                'order_total' => 'Order total',
                'invoices' => 'Invoices',
                'sales' => 'Sales',
                'commission' => 'Commission',
                'target' => 'Target',
                'achievement' => 'Achievement',
            ],
            'numeric' => ['orders', 'order_total', 'invoices', 'sales', 'commission', 'target'],
            'rows' => $rows,
            'footer' => [
                'name' => 'Totals',
                'orders' => (float) $rows->sum('orders'),
                'order_total' => (float) $rows->sum('order_total'),
                'invoices' => (float) $rows->sum('invoices'),
                'sales' => (float) $rows->sum('sales'),
                'commission' => (float) $rows->sum('commission'),
                'target' => (float) $rows->sum('target'),
            ],
            'empty' => 'No salesmen found.',
        ]));
    }

    private function salesmanProductReport(array $item, Request $request): View
    {
        $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'salesman_id' => ['nullable', 'integer', 'exists:salesmen,id'],
            'stock_category_id' => ['nullable', 'integer', 'exists:stock_categories,id'],
            'stock_item_id' => ['nullable', 'integer', 'exists:stock_items,id'],
            'city' => ['nullable', 'string', 'max:100'],
            'group_by' => ['nullable', 'in:category,salesman'],
        ]);

        [$from, $to] = $this->period($request);
        $salesmanId = $request->integer('salesman_id') ?: null;
        $categoryId = $request->integer('stock_category_id') ?: null;
        $itemId = $request->integer('stock_item_id') ?: null;
        $city = trim((string) $request->input('city', ''));
        $groupBy = $request->input('group_by') === 'salesman' ? 'salesman' : 'category';

        $lines = SalesInvoiceLine::query()
            ->with([
                'invoice.salesman',
                'invoice.salesOrder.salesman',
                'invoice.customer',
                'stockItem.stockCategory',
            ])
            ->whereHas('invoice', function ($query) use ($from, $to, $salesmanId, $city) {
                $query->where('status', '!=', 'draft');
                $this->constrainInvoicePeriod($query, $from, $to);
                if ($salesmanId) {
                    $query->where(function ($inner) use ($salesmanId) {
                        $inner->where('salesman_id', $salesmanId)
                            ->orWhereHas('salesOrder', fn ($order) => $order->where('salesman_id', $salesmanId));
                    });
                }
                if ($city !== '') {
                    $query->where(function ($inner) use ($city) {
                        $inner->whereHas('salesman', fn ($salesman) => $salesman->assignedCity($city))
                            ->orWhereHas('salesOrder.salesman', fn ($salesman) => $salesman->assignedCity($city));
                    });
                }
            })
            ->when($categoryId, fn ($query) => $query->whereHas('stockItem', fn ($itemQuery) => $itemQuery->where('stock_category_id', $categoryId)))
            ->when($itemId, fn ($query) => $query->where('stock_item_id', $itemId))
            ->get();

        $aggregates = [];
        foreach ($lines as $line) {
            $invoice = $line->invoice;
            if (! $invoice) {
                continue;
            }
            $salesman = $invoice->salesman ?: $invoice->salesOrder?->salesman;
            $salesmanIdKey = $salesman?->id ?: 0;
            $productKey = $line->stock_item_id ?: 'name:'.mb_strtolower(trim((string) $line->description));
            $key = $salesmanIdKey.'|'.$productKey;
            if (! isset($aggregates[$key])) {
                $aggregates[$key] = [
                    'salesman' => $salesman?->name ?: 'Unassigned',
                    'salesman_id' => $salesmanIdKey,
                    'city' => $salesman?->citiesLabel() ?: ($invoice->customer?->city ?: ''),
                    'category' => $line->stockItem?->stockCategory?->name ?: 'Uncategorized',
                    'product' => $line->stockItem?->name ?: (trim((string) $line->description) ?: 'Item'),
                    'sku' => $line->stockItem?->sku ?: '',
                    'product_key' => (string) $productKey,
                    'qty' => 0.0,
                    'amount' => 0.0,
                    'bills' => [],
                ];
            }
            $aggregates[$key]['qty'] += (float) $line->quantity;
            $aggregates[$key]['amount'] += (float) $line->line_total;
            if ($invoice->invoice_no) {
                $aggregates[$key]['bills'][$invoice->id] = true;
            }
        }

        $detailRows = collect($aggregates)
            ->map(function (array $row) {
                $row['qty'] = round((float) $row['qty'], 3);
                $row['amount'] = round((float) $row['amount'], 2);
                $row['bills'] = count($row['bills']);

                return $row;
            })
            ->sortBy([
                ['category', 'asc'],
                ['salesman', 'asc'],
                ['product', 'asc'],
            ])
            ->values();

        $topProducts = $detailRows
            ->groupBy('product_key')
            ->map(function (Collection $rows) {
                $leader = $rows->sortByDesc('qty')->first();

                return [
                    'product' => $leader['product'],
                    'sku' => $leader['sku'],
                    'category' => $leader['category'],
                    'qty' => round((float) $rows->sum('qty'), 3),
                    'amount' => round((float) $rows->sum('amount'), 2),
                    'salesmen' => $rows->count(),
                    'top_salesman' => $leader['salesman'],
                    'top_qty' => $leader['qty'],
                    'top_amount' => $leader['amount'],
                ];
            })
            ->sortByDesc('qty')
            ->values();

        $groups = $detailRows
            ->groupBy($groupBy === 'salesman' ? 'salesman' : 'category')
            ->map(function (Collection $rows, string $title) {
                return [
                    'title' => $title.' · '.$rows->count().' lines',
                    'rows' => $rows->values(),
                    'footer' => [
                        'salesman' => 'Subtotal',
                        'qty' => round((float) $rows->sum('qty'), 3),
                        'amount' => round((float) $rows->sum('amount'), 2),
                        'bills' => (float) $rows->sum('bills'),
                    ],
                ];
            })
            ->values();

        $filters = collect([
            $salesmanId ? Salesman::query()->find($salesmanId)?->name : null,
            $city !== '' ? $city : null,
            $categoryId ? StockCategory::query()->find($categoryId)?->name : null,
            $itemId ? StockItem::query()->find($itemId)?->name : null,
            $groupBy === 'salesman' ? 'Grouped by salesman' : 'Grouped by category',
        ])->filter()->implode(' · ');

        $detailColumns = $groupBy === 'salesman'
            ? [
                'category' => 'Category',
                'product' => 'Product',
                'sku' => 'SKU',
                'city' => 'City',
                'qty' => 'Qty',
                'amount' => 'Amount',
                'bills' => 'Bills',
            ]
            : [
                'salesman' => 'Salesman',
                'city' => 'City',
                'product' => 'Product',
                'sku' => 'SKU',
                'qty' => 'Qty',
                'amount' => 'Amount',
                'bills' => 'Bills',
            ];

        return view('reports.salesman-product', $this->printData($item, $from, $to, [
            'wide' => true,
            'subtitle' => $filters !== '' ? $filters : null,
            'groupLabel' => $groupBy === 'salesman' ? 'salesman' : 'category',
            'topProducts' => $topProducts,
            'groups' => $groups,
            'detailColumns' => $detailColumns,
            'detailNumeric' => ['qty', 'amount', 'bills'],
            'footer' => [
                'label' => 'Grand total',
                'qty' => round((float) $detailRows->sum('qty'), 3),
                'amount' => round((float) $detailRows->sum('amount'), 2),
                'bills' => (float) $detailRows->sum('bills'),
            ],
        ]));
    }

    private function constrainInvoicePeriod($query, Carbon $from, Carbon $to): void
    {
        $fromStr = $from->toDateString();
        $toStr = $to->toDateString();

        $query->where(function ($inner) use ($from, $to, $fromStr, $toStr) {
            $inner->whereBetween('invoice_date', [$fromStr, $toStr])
                ->orWhereBetween('created_at', [$from, $to])
                ->orWhereHas('salesOrder', function ($order) use ($from, $to, $fromStr, $toStr) {
                    $order->whereBetween('order_date', [$fromStr, $toStr])
                        ->orWhereBetween('created_at', [$from, $to]);
                });
        });
    }

    private function purchaseOrdersReport(array $item, Request $request): View
    {
        [$from, $to] = $this->period($request);

        $orders = PurchaseOrder::with('supplier')
            ->withCount('items')
            ->whereBetween('po_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('po_date')
            ->orderBy('id')
            ->get();

        $rows = $orders->map(fn (PurchaseOrder $order) => [
            'date' => ams_date($order->po_date),
            'no' => $order->po_no,
            'supplier' => $order->supplier?->name ?: '—',
            'items' => (int) $order->items_count,
            'total' => (float) $order->total_amount,
            'notes' => $order->notes ?: '—',
        ]);

        return view('reports.simple', $this->printData($item, $from, $to, [
            'columns' => [
                'date' => 'Date',
                'no' => 'PO',
                'supplier' => 'Supplier',
                'items' => 'Items',
                'total' => 'Total',
                'notes' => 'Notes',
            ],
            'numeric' => ['items', 'total'],
            'rows' => $rows,
            'footer' => [
                'date' => 'Totals',
                'items' => (float) $rows->sum('items'),
                'total' => (float) $rows->sum('total'),
            ],
            'empty' => 'No purchase orders in this period.',
        ]));
    }

    private function purchaseReturnsReport(array $item, Request $request): View
    {
        [$from, $to] = $this->period($request);

        $returns = PurchaseReturn::with(['supplier', 'items'])
            ->whereBetween('return_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('return_date')
            ->orderBy('id')
            ->get();

        $rows = $returns->map(fn (PurchaseReturn $return) => [
            'date' => ams_date($return->return_date),
            'no' => $return->return_no,
            'supplier' => $return->supplier?->name ?: '—',
            'items' => $return->items->count(),
            'total' => (float) $return->total_amount,
            'notes' => $return->notes ?: '—',
        ]);

        return view('reports.simple', $this->printData($item, $from, $to, [
            'columns' => [
                'date' => 'Date',
                'no' => 'Return',
                'supplier' => 'Supplier',
                'items' => 'Items',
                'total' => 'Total',
                'notes' => 'Notes',
            ],
            'numeric' => ['items', 'total'],
            'rows' => $rows,
            'footer' => [
                'date' => 'Totals',
                'items' => (float) $rows->sum('items'),
                'total' => (float) $rows->sum('total'),
            ],
            'empty' => 'No purchase returns in this period.',
        ]));
    }

    private function expensesReport(array $item, Request $request): View
    {
        [$from, $to] = $this->period($request);

        $vouchers = CashVoucher::query()
            ->where('account_type', 'expense')
            ->whereBetween('voucher_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('voucher_date')
            ->orderBy('id')
            ->get();

        CashVoucher::loadAccountNames($vouchers);

        $groups = $vouchers
            ->groupBy(fn (CashVoucher $voucher) => $voucher->account_display_name ?: 'Unassigned')
            ->sortKeys()
            ->map(function (Collection $groupVouchers, string $title) {
                $rows = $groupVouchers->map(fn (CashVoucher $voucher) => $this->expenseRow($voucher));

                return [
                    'title' => $title.' · '.$rows->count().' vouchers',
                    'rows' => $rows,
                    'footer' => [
                        'date' => 'Subtotal',
                        'amount' => (float) $rows->sum('amount'),
                    ],
                ];
            })
            ->values();

        $allRows = $vouchers->map(fn (CashVoucher $voucher) => $this->expenseRow($voucher));

        $accounts = ExpenseAccount::query()->orderBy('name')->get();
        $subtitle = $accounts->isEmpty()
            ? null
            : $accounts->count().' expense accounts';

        return view('reports.grouped', $this->printData($item, $from, $to, [
            'subtitle' => $subtitle,
            'columns' => [
                'date' => 'Date',
                'no' => 'Voucher',
                'type' => 'Type',
                'method' => 'Method',
                'amount' => 'Amount',
                'notes' => 'Notes',
            ],
            'numeric' => ['amount'],
            'groups' => $groups,
            'footer' => [
                'date' => 'Grand total',
                'amount' => (float) $allRows->sum('amount'),
            ],
            'empty' => 'No expense vouchers in this period.',
        ]));
    }

    private function monthlySheetReport(array $item, Request $request): View
    {
        [$from, $to] = $this->monthPeriod($request);

        $invoices = SalesInvoice::query()
            ->where('status', '!=', 'draft')
            ->whereBetween('invoice_date', [$from->toDateString(), $to->toDateString()])
            ->get()
            ->groupBy(fn (SalesInvoice $invoice) => $invoice->invoice_date->toDateString());

        $orders = SalesOrder::query()
            ->whereBetween('order_date', [$from->toDateString(), $to->toDateString()])
            ->get()
            ->groupBy(fn (SalesOrder $order) => $order->order_date->toDateString());

        $purchases = PurchaseOrder::query()
            ->whereBetween('po_date', [$from->toDateString(), $to->toDateString()])
            ->get()
            ->groupBy(fn (PurchaseOrder $order) => $order->po_date->toDateString());

        $vouchers = CashVoucher::query()
            ->whereBetween('voucher_date', [$from->toDateString(), $to->toDateString()])
            ->get()
            ->groupBy(fn (CashVoucher $voucher) => $voucher->voucher_date->toDateString());

        $days = collect();

        foreach (CarbonPeriod::create($from->copy()->startOfDay(), $to->copy()->startOfDay()) as $date) {
            $key = $date->toDateString();
            $dayInvoices = $invoices->get($key, collect());
            $dayOrders = $orders->get($key, collect());
            $dayPurchases = $purchases->get($key, collect());
            $dayVouchers = $vouchers->get($key, collect());

            $sales = (float) $dayInvoices->sum('total');
            $orderTotal = (float) $dayOrders->sum('total');
            $purchaseTotal = (float) $dayPurchases->sum('total_amount');
            $expenses = (float) $dayVouchers
                ->where('account_type', 'expense')
                ->sum(fn (CashVoucher $voucher) => $voucher->type === 'payment' ? (float) $voucher->amount : -1 * (float) $voucher->amount);
            $cashIn = (float) $dayVouchers->where('type', 'receive')->filter(fn (CashVoucher $voucher) => $voucher->affectsCashBalance())->sum('amount');
            $cashOut = (float) $dayVouchers->where('type', 'payment')->filter(fn (CashVoucher $voucher) => $voucher->affectsCashBalance())->sum('amount');

            $days->push([
                'label' => ams_date($date),
                'sales' => $sales,
                'orders' => $orderTotal,
                'purchases' => $purchaseTotal,
                'expenses' => $expenses,
                'cash_in' => $cashIn,
                'cash_out' => $cashOut,
                'net_cash' => $cashIn - $cashOut,
            ]);
        }

        $totals = [
            'sales' => (float) $days->sum('sales'),
            'orders' => (float) $days->sum('orders'),
            'purchases' => (float) $days->sum('purchases'),
            'expenses' => (float) $days->sum('expenses'),
            'cash_in' => (float) $days->sum('cash_in'),
            'cash_out' => (float) $days->sum('cash_out'),
            'net_cash' => (float) $days->sum('net_cash'),
        ];

        $invoiceBySalesman = SalesInvoice::with('salesman')
            ->where('status', '!=', 'draft')
            ->whereNotNull('salesman_id')
            ->whereBetween('invoice_date', [$from->toDateString(), $to->toDateString()])
            ->get()
            ->groupBy('salesman_id');

        $salesmen = Salesman::query()->orderBy('name')->get()->map(function (Salesman $salesman) use ($invoiceBySalesman) {
            $salesmanInvoices = $invoiceBySalesman->get($salesman->id, collect());
            $sales = (float) $salesmanInvoices->sum('total');
            $target = (float) $salesman->monthly_target;

            return [
                'name' => $salesman->name,
                'city' => $salesman->citiesLabel() ?: '—',
                'invoices' => $salesmanInvoices->count(),
                'sales' => $sales,
                'commission' => (float) $salesmanInvoices->sum('salesman_commission_amount'),
                'target' => $target,
                'achievement' => $target > 0 ? round(($sales / $target) * 100, 1).'%' : '—',
            ];
        })->filter(fn (array $row) => $row['invoices'] > 0 || $row['sales'] > 0)->values();

        return view('reports.monthly-sheet', $this->printData($item, $from, $to, [
            'wide' => true,
            'days' => $days,
            'totals' => $totals,
            'salesmen' => $salesmen,
        ]));
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function period(Request $request): array
    {
        $from = $request->filled('from_date')
            ? Carbon::parse($request->input('from_date'))->startOfDay()
            : now()->startOfMonth()->startOfDay();
        $to = $request->filled('to_date')
            ? Carbon::parse($request->input('to_date'))->endOfDay()
            : now()->endOfDay();

        if ($to->lt($from)) {
            $to = $from->copy()->endOfDay();
        }

        return [$from, $to];
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function monthPeriod(Request $request): array
    {
        if ($request->filled('month')) {
            try {
                $from = Carbon::createFromFormat('Y-m', (string) $request->input('month'))->startOfMonth();
            } catch (\Throwable) {
                $from = now()->startOfMonth();
            }

            return [$from, $from->copy()->endOfMonth()];
        }

        return $this->period($request);
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function printData(array $item, ?Carbon $from = null, ?Carbon $to = null, array $extra = []): array
    {
        $period = null;
        if ($from && $to) {
            $period = ams_date($from).' to '.ams_date($to);
        }

        $settings = app(SettingsService::class);

        return array_merge([
            'title' => $item['title'],
            'settings' => $settings,
            'companyName' => $settings->companyName(),
            'printedAt' => now(),
            'period' => $period,
            'fromDate' => $from,
            'toDate' => $to,
            'wide' => false,
            'subtitle' => null,
        ], $extra);
    }

    /**
     * @return array<string, string>
     */
    private function stockColumns(bool $compact = false): array
    {
        $columns = [
            'sku' => 'SKU',
            'name' => 'Item',
            'category' => 'Category',
            'batch' => 'Batch #',
            'unit' => 'Unit',
            'qty' => 'Qty',
            'reorder' => 'Reorder',
            'cost' => 'Cost',
            'sale' => 'Sale',
            'cost_value' => 'Cost value',
            'sale_value' => 'Sale value',
        ];

        if ($compact) {
            unset($columns['reorder'], $columns['sale_value']);
        }

        return $columns;
    }

    /**
     * @return list<string>
     */
    private function stockNumeric(bool $compact = false): array
    {
        $columns = ['qty', 'reorder', 'cost', 'sale', 'cost_value', 'sale_value'];

        if ($compact) {
            return array_values(array_diff($columns, ['reorder', 'sale_value']));
        }

        return $columns;
    }

    /**
     * @return Collection<int, StockItem>
     */
    private function stockItemsForReport(bool $lowStock = false): Collection
    {
        $query = StockItem::query()
            ->with([
                'stockCategory',
                'lots' => fn ($lots) => $lots->orderBy('received_at')->orderBy('id'),
            ]);

        if ($lowStock) {
            $query->lowStock()->orderBy('quantity')->orderBy('name');
        } else {
            $query->orderBy('name');
        }

        return $query->get();
    }

    /**
     * @param  Collection<int, StockItem>  $items
     * @return Collection<int, array<string, mixed>>
     */
    private function stockItemRows(Collection $items): Collection
    {
        return $items->map(function (StockItem $item) {
            $qty = (float) $item->quantity;
            $cost = (float) $item->cost_price;
            $sale = (float) $item->sale_price;

            return [
                'sku' => $item->sku ?: '—',
                'name' => $item->purchaseLabel(),
                'category' => $item->stockCategory?->name ?: 'Uncategorized',
                'batch' => $item->lotsHtml(),
                'unit' => StockItem::UNITS[$item->unit] ?? ($item->unit ?: '—'),
                'qty' => $qty,
                'reorder' => (float) $item->reorder_level,
                'cost' => $cost,
                'sale' => $sale,
                'cost_value' => $qty * $cost,
                'sale_value' => $qty * $sale,
            ];
        })->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    private function stockFooter(Collection $rows, string $label): array
    {
        return [
            'sku' => $label,
            'qty' => (float) $rows->sum('qty'),
            'cost_value' => (float) $rows->sum('cost_value'),
            'sale_value' => (float) $rows->sum('sale_value'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function expenseRow(CashVoucher $voucher): array
    {
        $amount = (float) $voucher->amount;
        if ($voucher->type === 'receive') {
            $amount *= -1;
        }

        return [
            'date' => ams_date($voucher->voucher_date),
            'no' => $voucher->voucher_no,
            'type' => $voucher->type === 'receive' ? 'Refund' : 'Payment',
            'method' => $voucher->payment_method === 'bank' ? 'Bank' : 'Cash',
            'amount' => $amount,
            'notes' => $voucher->notes ?: ($voucher->reference ?: '—'),
        ];
    }
}
