<?php

namespace App\Http\Controllers;

use App\Models\CashVoucher;
use App\Models\Customer;
use App\Models\ExpenseAccount;
use App\Models\PurchaseOrder;
use App\Models\PurchaseReturn;
use App\Models\SalesInvoice;
use App\Models\Salesman;
use App\Models\SalesOrder;
use App\Models\SalesReturn;
use App\Models\StockItem;
use App\Models\Supplier;
use App\Services\CashRegisterService;
use App\Services\JournalReportService;
use App\Services\PartyLedgerService;
use App\Services\SettingsService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
            'salesmen' => Salesman::query()->orderBy('name')->get(['id', 'name', 'city']),
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
            'salesman_id' => ['nullable', 'integer', 'exists:salesmen,id'],
        ]);

        $from = Carbon::parse($data['from_date'])->startOfDay();
        $to = Carbon::parse($data['to_date'])->endOfDay();

        $query = SalesInvoice::with(['customer', 'salesman'])
            ->where('status', '!=', 'draft')
            ->whereNotNull('salesman_id')
            ->whereBetween('invoice_date', [$from->toDateString(), $to->toDateString()]);

        if (! empty($data['salesman_id'])) {
            $query->where('salesman_id', $data['salesman_id']);
        }

        $invoices = $query->orderBy('invoice_date')->orderBy('id')->get();

        $salesman = ! empty($data['salesman_id']) ? Salesman::query()->find($data['salesman_id']) : null;

        return view('reports.salesman-commission', $this->printData(
            ['title' => 'Salesman Commission'],
            $from,
            $to,
            [
                'subtitle' => $salesman?->name,
                'salesman' => $salesman,
                'invoices' => $invoices,
                'totalSales' => (float) $invoices->sum('total'),
                'totalRetain' => (float) $invoices->sum('company_retain_amount'),
                'totalCommission' => (float) $invoices->sum('salesman_commission_amount'),
            ]
        ));
    }

    private function accountReceivablesReport(array $item): View
    {
        $rows = collect();

        foreach (Customer::orderBy('name')->get() as $customer) {
            $balance = (float) $customer->current_balance;
            if (abs($balance) < 0.005) {
                continue;
            }

            $rows->push([
                'code' => 1000 + (int) $customer->id,
                'party_name' => $customer->name,
                'party_type' => 'Customer',
                'debit' => max($balance, 0),
                'credit' => $balance < 0 ? abs($balance) : 0,
            ]);
        }

        foreach (Supplier::orderBy('name')->get() as $supplier) {
            $balance = (float) $supplier->current_balance;
            if (abs($balance) < 0.005) {
                continue;
            }

            $rows->push([
                'code' => 2000 + (int) $supplier->id,
                'party_name' => $supplier->name,
                'party_type' => 'Supplier',
                'debit' => $balance < 0 ? abs($balance) : 0,
                'credit' => max($balance, 0),
            ]);
        }

        $rows = $rows->sortBy('code')->values();

        return view('reports.account-receivables', $this->printData($item, extra: [
            'rows' => $rows,
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
        $items = StockItem::query()->with('stockCategory')->orderBy('name')->get();
        $rows = $this->stockRows($items);

        return view('reports.simple', $this->printData($item, extra: [
            'wide' => true,
            'columns' => $this->stockColumns(),
            'numeric' => $this->stockNumeric(),
            'rows' => $rows,
            'footer' => $this->stockFooter($rows, 'Totals · '.$rows->count().' items'),
            'empty' => 'No stock items found.',
        ]));
    }

    private function stockByCategoryReport(array $item): View
    {
        $items = StockItem::query()->with('stockCategory')->orderBy('name')->get();

        $groups = $items
            ->groupBy(fn (StockItem $stock) => $stock->stockCategory?->name ?: 'Uncategorized')
            ->sortKeys()
            ->map(function (Collection $groupItems, string $title) {
                $rows = $this->stockRows($groupItems);

                return [
                    'title' => $title.' · '.$rows->count().' items',
                    'rows' => $rows,
                    'footer' => $this->stockFooter($rows, 'Subtotal'),
                ];
            })
            ->values();

        $allRows = $this->stockRows($items);

        return view('reports.grouped', $this->printData($item, extra: [
            'wide' => true,
            'columns' => $this->stockColumns(),
            'numeric' => $this->stockNumeric(),
            'groups' => $groups,
            'footer' => $this->stockFooter($allRows, 'Grand total'),
            'empty' => 'No stock items found.',
        ]));
    }

    private function lowStockReport(array $item): View
    {
        $items = StockItem::query()
            ->with('stockCategory')
            ->where(function ($query) {
                $query->where('quantity', '<=', 0)
                    ->orWhere(function ($inner) {
                        $inner->where('reorder_level', '>', 0)
                            ->whereColumn('quantity', '<=', 'reorder_level');
                    });
            })
            ->orderBy('quantity')
            ->orderBy('name')
            ->get();

        $rows = $this->stockRows($items);

        return view('reports.simple', $this->printData($item, extra: [
            'wide' => true,
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
                'city' => $salesman->city ?: '—',
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
            $cashIn = (float) $dayVouchers->where('type', 'receive')->sum('amount');
            $cashOut = (float) $dayVouchers->where('type', 'payment')->sum('amount');

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
                'city' => $salesman->city ?: '—',
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
    private function stockColumns(): array
    {
        return [
            'sku' => 'SKU',
            'name' => 'Item',
            'category' => 'Category',
            'unit' => 'Unit',
            'qty' => 'Qty',
            'reorder' => 'Reorder',
            'cost' => 'Cost',
            'sale' => 'Sale',
            'cost_value' => 'Cost value',
            'sale_value' => 'Sale value',
        ];
    }

    /**
     * @return list<string>
     */
    private function stockNumeric(): array
    {
        return ['qty', 'reorder', 'cost', 'sale', 'cost_value', 'sale_value'];
    }

    /**
     * @param  Collection<int, StockItem>  $items
     * @return Collection<int, array<string, mixed>>
     */
    private function stockRows(Collection $items): Collection
    {
        return $items->map(function (StockItem $item) {
            $qty = (float) $item->quantity;
            $cost = (float) $item->cost_price;
            $sale = (float) $item->sale_price;

            return [
                'sku' => $item->sku ?: '—',
                'name' => $item->purchaseLabel(),
                'category' => $item->stockCategory?->name ?: 'Uncategorized',
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
