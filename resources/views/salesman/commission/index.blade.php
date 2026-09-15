@extends('template.layout')
@section('title', 'Commission')

@section('content')
    <div class="pb-4">
        <div class="py-4">
            <h1 class="h4 mb-1">Commission</h1>
            <p class="mb-0 text-muted">Sales and commission from your confirmed invoices.</p>
        </div>
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <div class="card p-3">
                    <div class="small text-muted">Sales</div>
                    <div class="h4 mb-0">{{ ams_num($totalSales) }}</div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card p-3">
                    <div class="small text-muted">Commission</div>
                    <div class="h4 mb-0">{{ ams_num($totalCommission) }}</div>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="table-responsive py-3">
                <table class="table table-flush" data-datatable="true">
                    <thead class="thead-light">
                        <tr>
                            <th>Invoice</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th class="text-end">Sales</th>
                            <th class="text-end">Commission</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoices as $invoice)
                            <tr>
                                <td class="fw-semibold">{{ $invoice->invoice_no ?: '—' }}</td>
                                <td>{{ $invoice->customer?->displayName() ?: '—' }}</td>
                                <td data-order="{{ optional($invoice->invoice_date)->format('Y-m-d') }}">{{ ams_date($invoice->invoice_date) }}</td>
                                <td class="text-end">{{ ams_num($invoice->total) }}</td>
                                <td class="text-end">{{ ams_num($invoice->salesman_commission_amount) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted">No commission yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
