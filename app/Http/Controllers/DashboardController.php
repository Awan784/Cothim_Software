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
            ->sum('amount');

        $totalBankReceived = (float) CashVoucher::query()
            ->where('type', 'receive')
            ->where('payment_method', 'bank')
            ->sum('amount');

        $totalBankPaid = (float) CashVoucher::query()
            ->where('type', 'payment')
            ->where('payment_method', 'bank')
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
            ->sum('amount');
        $todayCashPayment = (float) CashVoucher::query()
            ->where('type', 'payment')
            ->whereDate('voucher_date', $today)
            ->sum('amount');
        $monthCashReceive = (float) CashVoucher::query()
            ->where('type', 'receive')
            ->whereDate('voucher_date', '>=', $monthStart)
            ->sum('amount');
        $monthCashPayment = (float) CashVoucher::query()
            ->where('type', 'payment')
            ->whereDate('voucher_date', '>=', $monthStart)
            ->sum('amount');

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
        ));
    }
}

