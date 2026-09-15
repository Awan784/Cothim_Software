@extends('template.layout')
@section('title', $order->order_no)

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <h1 class="h4 mb-1">{{ $order->order_no }}</h1>
                <p class="mb-0 text-muted">
                    {{ $order->salesman?->name }} · {{ $order->customer?->displayName() }}
                    · <span class="ams-status-tag is-{{ $order->status }}">{{ $order->statusLabel() }}</span>
                </p>
            </div>
            <div class="d-flex gap-2">
                @if($order->isPending())
                    <a href="{{ route('sales-orders.edit', $order) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                @endif
                @if($order->isConfirmed() && $order->invoice)
                    <a href="{{ route('invoices.show', $order->invoice) }}" class="btn btn-sm btn-primary">Open invoice</a>
                @endif
                <a href="{{ route('sales-orders.index') }}" class="btn btn-sm btn-secondary">Back</a>
            </div>
        </div>

        @if($order->isPending())
            <div class="card p-4 mb-3">
                <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between">
                    <p class="mb-0">Confirming this order generates a sales invoice, updates stock, and snapshots commission.</p>
                    <div class="d-flex gap-2">
                        <form method="post" action="{{ route('sales-orders.confirm', $order) }}">
                            @csrf
                            <button class="btn btn-primary" type="submit">Confirm &amp; generate invoice</button>
                        </form>
                        <form method="post" action="{{ route('sales-orders.reject', $order) }}" class="d-flex gap-2" onsubmit="return confirm('Reject this order?')">
                            @csrf
                            <input name="reject_reason" class="form-control" placeholder="Reason (optional)">
                            <button class="btn btn-outline-danger" type="submit">Reject</button>
                        </form>
                    </div>
                </div>
            </div>
        @elseif($order->isRejected() && $order->reject_reason)
            <div class="alert alert-danger">Rejected: {{ $order->reject_reason }}</div>
        @endif

        @include('sales-orders._lines', ['order' => $order])
    </div>
@endsection
