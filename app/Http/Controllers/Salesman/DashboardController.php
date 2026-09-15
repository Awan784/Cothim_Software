<?php

namespace App\Http\Controllers\Salesman;

use App\Http\Controllers\Controller;
use App\Models\SalesInvoice;
use App\Models\SalesOrder;
use App\Services\CommissionService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(CommissionService $commission): View
    {
        $salesman = auth('salesman')->user();
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();

        $pendingCount = SalesOrder::query()
            ->where('salesman_id', $salesman->id)
            ->where('status', SalesOrder::STATUS_PENDING)
            ->count();

        $monthOrders = SalesOrder::query()
            ->where('salesman_id', $salesman->id)
            ->whereBetween('order_date', [$monthStart, $monthEnd])
            ->count();

        $monthInvoices = SalesInvoice::query()
            ->where('salesman_id', $salesman->id)
            ->where('status', '!=', 'draft')
            ->whereBetween('invoice_date', [$monthStart, $monthEnd])
            ->get();

        $monthSales = (float) $monthInvoices->sum('total');
        $monthCommission = (float) $monthInvoices->sum('salesman_commission_amount');
        $rates = $commission->ratesFor($salesman);

        $recentOrders = SalesOrder::with('customer')
            ->where('salesman_id', $salesman->id)
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        return view('salesman.dashboard', compact(
            'salesman',
            'pendingCount',
            'monthOrders',
            'monthSales',
            'monthCommission',
            'rates',
            'recentOrders',
        ));
    }
}
