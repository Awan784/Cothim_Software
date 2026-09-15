@extends('template.layout')
@section('title', 'Salesman dashboard')

@section('content')
    <div class="pb-4 ams-dashboard">
        <div class="py-4 ams-page-header d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <h1 class="h4 mb-1">Welcome, {{ $salesman->name }}</h1>
                <p class="mb-0 text-gray-600">{{ $salesman->city ?: 'All cities' }} · Create an order and admin will confirm it as an invoice.</p>
            </div>
            <a href="{{ route('salesman.orders.create') }}" class="btn btn-primary">Create order</a>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-12 col-md-3">
                <a class="ams-kpi" href="{{ route('salesman.orders.index') }}">
                    <div class="ams-kpi-top">
                        <span class="ams-kpi-label">Pending orders</span>
                    </div>
                    <div class="ams-kpi-value">{{ number_format((int) $pendingCount) }}</div>
                </a>
            </div>
            <div class="col-12 col-md-3">
                <div class="ams-kpi">
                    <div class="ams-kpi-top">
                        <span class="ams-kpi-label">This month orders</span>
                    </div>
                    <div class="ams-kpi-value">{{ number_format((int) $monthOrders) }}</div>
                </div>
            </div>
            <div class="col-12 col-md-3">
                <a class="ams-kpi" href="{{ route('salesman.invoices.index') }}">
                    <div class="ams-kpi-top">
                        <span class="ams-kpi-label">This month sales</span>
                    </div>
                    <div class="ams-kpi-value">{{ ams_num($monthSales) }}</div>
                </a>
            </div>
            <div class="col-12 col-md-3">
                <a class="ams-kpi" href="{{ route('salesman.commission.index') }}">
                    <div class="ams-kpi-top">
                        <span class="ams-kpi-label">This month commission</span>
                    </div>
                    <div class="ams-kpi-value">{{ ams_num($monthCommission) }}</div>
                    <div class="ams-kpi-foot">
                        <span class="ams-kpi-meta">{{ ams_num($rates['salesman_commission_percent']) }}% of remaining after {{ ams_num($rates['company_retain_percent']) }}% retain</span>
                    </div>
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <strong>Recent orders</strong>
            </div>
            <div class="table-responsive">
                <table class="table table-flush mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>No.</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">Commission</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentOrders as $order)
                            <tr>
                                <td><a href="{{ route('salesman.orders.show', $order) }}">{{ $order->order_no }}</a></td>
                                <td>{{ $order->customer?->displayName() ?: '—' }}</td>
                                <td>{{ ams_date($order->order_date) }}</td>
                                <td><span class="ams-status-tag is-{{ $order->status }}">{{ $order->statusLabel() }}</span></td>
                                <td class="text-end">{{ ams_num($order->total) }}</td>
                                <td class="text-end">{{ ams_num($order->salesman_commission_amount) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted">No orders yet. Create your first order.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
