@extends('template.layout')
@section('title', 'Sales orders')

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h4 mb-1">Sales orders</h1>
                <p class="mb-0 text-muted">Confirm a pending order to generate the sales invoice.</p>
            </div>
            @if($pendingCount > 0)
                <span class="ams-status-tag is-pending">{{ $pendingCount }} pending</span>
            @endif
        </div>
        <div class="card">
            <div class="table-responsive py-3">
                <table class="table table-flush" data-datatable="true">
                    <thead class="thead-light">
                        <tr>
                            <th>No.</th>
                            <th>Salesman</th>
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
                                <td>{{ $order->salesman?->name ?: '—' }}</td>
                                <td>{{ $order->customer?->displayName() ?: '—' }}</td>
                                <td data-order="{{ optional($order->order_date)->format('Y-m-d') }}">{{ ams_date($order->order_date) }}</td>
                                <td><span class="ams-status-tag is-{{ $order->status }}">{{ $order->statusLabel() }}</span></td>
                                <td class="text-end">{{ ams_num($order->total) }}</td>
                                <td class="text-end">{{ ams_num($order->salesman_commission_amount) }}</td>
                                <td class="text-end text-nowrap">
                                    @if($order->isPending())
                                        <a class="btn btn-sm btn-outline-primary" href="{{ route('sales-orders.edit', $order) }}">Edit</a>
                                    @endif
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('sales-orders.show', $order) }}">Open</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted">No salesman orders yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
