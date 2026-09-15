@extends('template.layout')
@section('title', 'My orders')

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h4 mb-1">My orders</h1>
                <p class="mb-0 text-muted">Pending orders wait for admin confirmation.</p>
            </div>
            <a href="{{ route('salesman.orders.create') }}" class="btn btn-primary">Create order</a>
        </div>
        <div class="card">
            <div class="table-responsive py-3">
                <table class="table table-flush" data-datatable="true">
                    <thead class="thead-light">
                        <tr>
                            <th>No.</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">Commission</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                            <tr>
                                <td class="fw-semibold">{{ $order->order_no }}</td>
                                <td>{{ $order->customer?->displayName() ?: '—' }}</td>
                                <td data-order="{{ optional($order->order_date)->format('Y-m-d') }}">{{ ams_date($order->order_date) }}</td>
                                <td><span class="ams-status-tag is-{{ $order->status }}">{{ $order->statusLabel() }}</span></td>
                                <td class="text-end">{{ ams_num($order->total) }}</td>
                                <td class="text-end">{{ ams_num($order->salesman_commission_amount) }}</td>
                                <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('salesman.orders.show', $order) }}">Open</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted">No orders yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
