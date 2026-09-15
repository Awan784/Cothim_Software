@extends('template.layout')
@section('title', $order->order_no)

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <h1 class="h4 mb-1">{{ $order->order_no }}</h1>
                <p class="mb-0 text-muted">{{ $order->customer?->displayName() }} · <span class="ams-status-tag is-{{ $order->status }}">{{ $order->statusLabel() }}</span></p>
            </div>
            <div class="d-flex gap-2">
                @if($order->isPending())
                    <a href="{{ route('salesman.orders.edit', $order) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                    <form method="post" action="{{ route('salesman.orders.destroy', $order) }}" onsubmit="return confirm('Delete this pending order?')">
                        @csrf
                        @method('delete')
                        <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                    </form>
                @endif
                <a href="{{ route('salesman.orders.index') }}" class="btn btn-sm btn-secondary">Back</a>
            </div>
        </div>

        @if($order->isRejected() && $order->reject_reason)
            <div class="alert alert-danger">Rejected: {{ $order->reject_reason }}</div>
        @endif

        @include('sales-orders._lines', ['order' => $order])
    </div>
@endsection
