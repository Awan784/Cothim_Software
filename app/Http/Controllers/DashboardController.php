<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\CashAccount;
use App\Models\CashVoucher;
use App\Models\Customer;
use App\Models\ExpenseAccount;
use App\Models\PurchaseOrder;
use App\Models\SalesInvoice;
use App\Models\SalesOrder;
use App\Models\StockItem;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $customersCount = Customer::count();
        $suppliersCount = Supplier::count();
        $bankAccountsCount = BankAccount::count();

        $totalCustomerReceivable = (float) Customer::sum('current_balance');
        $totalSupplierPayable = (float) Supplier::sum('current_balance');
        $totalBankBalance = (float) BankAccount::sum('current_balance');

        $cashBalance = (float) CashAccount::query()
            ->orderByDesc('is_active')
            ->orderBy('id')
            ->value('current_balance');

        $totalCashReceived = (float) CashVoucher::query()
            ->where('type', 'receive')
            ->where('payment_method', 'cash')
            ->affectingCash()
            ->sum('amount');

        $totalBankReceived = (float) CashVoucher::query()
            ->where('type', 'receive')
            ->where('payment_method', 'bank')
            ->affectingCash()
            ->sum('amount');

        $totalBankPaid = (float) CashVoucher::query()
            ->where('type', 'payment')
            ->where('payment_method', 'bank')
            ->affectingCash()
            ->sum('amount');

        $expenseAccountsCount = ExpenseAccount::count();
        ExpenseAccount::syncAllTotalSpent();
        $totalExpenses = ExpenseAccount::totalExpenses();

        $purchaseOrdersCount = PurchaseOrder::count();
        $purchaseOrdersTotal = (float) PurchaseOrder::sum('total_amount');
        $recentPurchaseOrders = PurchaseOrder::with('supplier')
            ->orderByDesc('po_date')
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        $pendingSalesOrders = SalesOrder::with(['customer', 'salesman'])
            ->where('status', SalesOrder::STATUS_PENDING)
            ->orderByDesc('id')
            ->limit(8)
            ->get();
        $pendingSalesOrdersCount = SalesOrder::query()->where('status', SalesOrder::STATUS_PENDING)->count();

        $stockItemsCount = StockItem::count();
        $invoicesCount = SalesInvoice::count();
        $userName = auth()->user()?->name ?: 'there';

        $today = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();
        $todayCashReceive = (float) CashVoucher::query()
            ->where('type', 'receive')
            ->whereDate('voucher_date', $today)
            ->affectingCash()
            ->sum('amount');
        $todayCashPayment = (float) CashVoucher::query()
            ->where('type', 'payment')
            ->whereDate('voucher_date', $today)
            ->affectingCash()
            ->sum('amount');
        $monthCashReceive = (float) CashVoucher::query()
            ->where('type', 'receive')
            ->whereDate('voucher_date', '>=', $monthStart)
            ->affectingCash()
            ->sum('amount');
        $monthCashPayment = (float) CashVoucher::query()
            ->where('type', 'payment')
            ->whereDate('voucher_date', '>=', $monthStart)
            ->affectingCash()
            ->sum('amount');

        $monthSales = (float) SalesInvoice::query()
            ->where('status', '!=', 'draft')
            ->whereDate('invoice_date', '>=', $monthStart)
            ->sum('total');

        $charts = [
            'sales' => $this->salesChart(7),
            'cash' => $this->cashFlowChart(14),
            'balances' => [
                'labels' => ['Cash', 'Bank', 'Receivable', 'Payable'],
                'values' => [
                    round(max(0, (float) $cashBalance), 2),
                    round(max(0, (float) $totalBankBalance), 2),
                    round(max(0, (float) $totalCustomerReceivable), 2),
                    round(max(0, (float) $totalSupplierPayable), 2),
                ],
            ],
        ];

        return view('dashboard', compact(
            'customersCount',
            'suppliersCount',
            'bankAccountsCount',
            'totalCustomerReceivable',
            'totalSupplierPayable',
            'totalBankBalance',
            'cashBalance',
            'totalCashReceived',
            'totalBankReceived',
            'totalBankPaid',
            'expenseAccountsCount',
            'totalExpenses',
            'purchaseOrdersCount',
            'purchaseOrdersTotal',
            'recentPurchaseOrders',
            'pendingSalesOrders',
            'pendingSalesOrdersCount',
            'stockItemsCount',
            'invoicesCount',
            'userName',
            'todayCashReceive',
            'todayCashPayment',
            'monthCashReceive',
            'monthCashPayment',
            'monthSales',
            'charts',
        ));
    }

    /**
     * @return array{labels: list<string>, values: list<float>}
     */
    private function salesChart(int $days): array
    {
        $from = now()->subDays($days - 1)->startOfDay();
        $rows = SalesInvoice::query()
            ->where('status', '!=', 'draft')
            ->whereDate('invoice_date', '>=', $from->toDateString())
            ->selectRaw('DATE(invoice_date) as day, SUM(total) as total')
            ->groupBy(DB::raw('DATE(invoice_date)'))
            ->pluck('total', 'day');

        $labels = [];
        $values = [];
        for ($i = 0; $i < $days; $i++) {
            $day = $from->copy()->addDays($i);
            $key = $day->toDateString();
            $labels[] = $day->format('d M');
            $values[] = round((float) ($rows[$key] ?? 0), 2);
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * @return array{labels: list<string>, receive: list<float>, payment: list<float>}
     */
    private function cashFlowChart(int $days): array
    {
        $from = now()->subDays($days - 1)->startOfDay();
        $rows = CashVoucher::query()
            ->affectingCash()
            ->whereDate('voucher_date', '>=', $from->toDateString())
            ->selectRaw('DATE(voucher_date) as day, type, SUM(amount) as total')
            ->groupBy(DB::raw('DATE(voucher_date)'), 'type')
            ->get();

        $receiveMap = [];
        $paymentMap = [];
        foreach ($rows as $row) {
            $key = Carbon::parse($row->day)->toDateString();
            if ($row->type === 'receive') {
                $receiveMap[$key] = round((float) $row->total, 2);
            } else {
                $paymentMap[$key] = round((float) $row->total, 2);
            }
        }

        $labels = [];
        $receive = [];
        $payment = [];
        for ($i = 0; $i < $days; $i++) {
            $day = $from->copy()->addDays($i);
            $key = $day->toDateString();
            $labels[] = $day->format('d M');
            $receive[] = (float) ($receiveMap[$key] ?? 0);
            $payment[] = (float) ($paymentMap[$key] ?? 0);
        }

        return ['labels' => $labels, 'receive' => $receive, 'payment' => $payment];
    }
}

