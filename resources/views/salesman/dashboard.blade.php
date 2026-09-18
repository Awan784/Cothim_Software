@extends('template.layout')
@section('title', 'Salesman dashboard')

@section('content')
    <div class="pb-4 ams-dashboard">
        <div class="db-welcome">
            <div>
                <h2>Welcome back, {{ $salesman->name }}</h2>
                <p>{{ $salesman->city ?: 'All cities' }} · Create an order and admin will confirm it as an invoice.</p>
            </div>
            <div class="db-welcome-meta">
                <div class="db-chip"><span>Today</span>{{ now()->format('d M Y') }}</div>
                <div class="db-chip"><span>Pending</span>{{ number_format((int) $pendingCount) }}</div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-xl-3 col-md-6">
                <a class="db-card" href="{{ route('salesman.orders.index') }}">
                    <div class="db-card-top">
                        <p class="db-card-label">Pending orders</p>
                        <div class="db-card-icon orange"></div>
                    </div>
                    <h3 class="db-card-value">{{ number_format((int) $pendingCount) }}</h3>
                    <p class="db-card-note">Waiting for confirmation</p>
                </a>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="db-card">
                    <div class="db-card-top">
                        <p class="db-card-label">This month orders</p>
                        <div class="db-card-icon blue"></div>
                    </div>
                    <h3 class="db-card-value">{{ number_format((int) $monthOrders) }}</h3>
                    <p class="db-card-note">{{ now()->format('F Y') }}</p>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <a class="db-card" href="{{ route('salesman.invoices.index') }}">
                    <div class="db-card-top">
                        <p class="db-card-label">This month sales</p>
                        <div class="db-card-icon"></div>
                    </div>
                    <h3 class="db-card-value">{{ ams_num($monthSales) }}</h3>
                    <p class="db-card-note">Confirmed invoices</p>
                </a>
            </div>
            <div class="col-xl-3 col-md-6">
                <a class="db-card" href="{{ route('salesman.commission.index') }}">
                    <div class="db-card-top">
                        <p class="db-card-label">This month commission</p>
                        <div class="db-card-icon sand"></div>
                    </div>
                    <h3 class="db-card-value">{{ ams_num($monthCommission) }}</h3>
                    <p class="db-card-note">{{ ams_num($rates['salesman_commission_percent']) }}% after {{ ams_num($rates['company_retain_percent']) }}% retain</p>
                </a>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-xl-4">
                <div class="db-panel">
                    <div class="db-panel-head">
                        <h4>Quick actions</h4>
                    </div>
                    <div class="db-panel-body">
                        <div class="db-actions">
                            <a class="db-action" href="{{ route('salesman.orders.create') }}">Create order</a>
                            <a class="db-action" href="{{ route('salesman.orders.index') }}">My orders</a>
                            <a class="db-action" href="{{ route('salesman.invoices.index') }}">My invoices</a>
                            <a class="db-action" href="{{ route('salesman.commission.index') }}">Commission</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-8">
                <div class="db-panel">
                    <div class="db-panel-head">
                        <h4>Recent orders</h4>
                        <a href="{{ route('salesman.orders.index') }}" class="db-link">Open list</a>
                    </div>
                    <div class="db-panel-body">
                        @if($recentOrders->count())
                            <div class="table-responsive">
                                <table class="db-table">
                                    <thead>
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
                                        @foreach($recentOrders as $order)
                                            <tr>
                                                <td><strong><a href="{{ route('salesman.orders.show', $order) }}">{{ $order->order_no }}</a></strong></td>
                                                <td>{{ $order->customer?->displayName() ?: '—' }}</td>
                                                <td>{{ ams_date($order->order_date) }}</td>
                                                <td><span class="ams-status-tag is-{{ $order->status }}">{{ $order->statusLabel() }}</span></td>
                                                <td class="text-end">{{ ams_num($order->total) }}</td>
                                                <td class="text-end">{{ ams_num($order->salesman_commission_amount) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="db-empty">No orders yet. Create your first order.</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
