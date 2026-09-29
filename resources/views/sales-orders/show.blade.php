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
                <form method="post" action="{{ route('sales-orders.confirm', $order) }}">
                    @csrf
                    <p class="mb-3">Confirming this order generates a sales invoice, updates stock, and snapshots commission. Add builty details if you have them.</p>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Builty postal</label>
                            <input name="builty_postal" class="form-control" maxlength="100" value="{{ old('builty_postal', $order->builty_postal) }}" placeholder="e.g. 23299048">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Builty expenses</label>
                            <input name="builty_exp" type="number" min="0" step="0.01" class="form-control" value="{{ old('builty_exp', $order->builty_exp) }}" placeholder="0.00">
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <button class="btn btn-primary" type="submit">Confirm &amp; generate invoice</button>
                    </div>
                </form>
                <form method="post" action="{{ route('sales-orders.reject', $order) }}" class="d-flex gap-2 mt-3" onsubmit="return confirm('Reject this order?')">
                    @csrf
                    <input name="reject_reason" class="form-control" placeholder="Reason (optional)">
                    <button class="btn btn-outline-danger" type="submit">Reject</button>
                </form>
            </div>
        @elseif($order->isRejected())
            @if($order->reject_reason)
                <div class="alert alert-danger">Rejected: {{ $order->reject_reason }}</div>
            @endif
        @else
            <div class="card p-4 mb-3">
                <form method="post" action="{{ route('sales-orders.builty', $order) }}" class="row g-3 align-items-end">
                    @csrf
                    <div class="col-md-4">
                        <label class="form-label">Builty postal</label>
                        <input name="builty_postal" class="form-control" maxlength="100" value="{{ old('builty_postal', $order->invoice?->builty_postal ?? $order->builty_postal) }}" placeholder="e.g. 23299048">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Builty expenses</label>
                        <input name="builty_exp" type="number" min="0" step="0.01" class="form-control" value="{{ old('builty_exp', $order->invoice?->builty_exp ?? $order->builty_exp) }}" placeholder="0.00">
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-outline-primary" type="submit">Save builty</button>
                    </div>
                </form>
            </div>
        @endif

        @include('sales-orders._lines', ['order' => $order])
    </div>
@endsection
