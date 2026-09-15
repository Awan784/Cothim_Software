<?php

namespace App\Http\Controllers\Salesman;

use App\Http\Controllers\Controller;
use App\Models\SalesInvoice;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function index(): View
    {
        $invoices = SalesInvoice::with('customer')
            ->where('salesman_id', auth('salesman')->id())
            ->where('status', '!=', 'draft')
            ->orderByDesc('id')
            ->limit(300)
            ->get();

        return view('salesman.invoices.index', compact('invoices'));
    }

    public function commission(): View
    {
        $salesman = auth('salesman')->user();
        $invoices = SalesInvoice::with('customer')
            ->where('salesman_id', $salesman->id)
            ->where('status', '!=', 'draft')
            ->orderByDesc('invoice_date')
            ->orderByDesc('id')
            ->limit(400)
            ->get();

        return view('salesman.commission.index', [
            'salesman' => $salesman,
            'invoices' => $invoices,
            'totalSales' => (float) $invoices->sum('total'),
            'totalCommission' => (float) $invoices->sum('salesman_commission_amount'),
        ]);
    }
}
