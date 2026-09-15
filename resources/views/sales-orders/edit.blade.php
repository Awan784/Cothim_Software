@extends('template.layout')
@section('title', 'Edit '.$order->order_no)

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <h1 class="h4 mb-1">Edit {{ $order->order_no }}</h1>
                <p class="mb-0 text-muted">{{ $order->salesman?->name }} · Pending salesman order</p>
            </div>
            <a href="{{ route('sales-orders.show', $order) }}" class="btn btn-sm btn-secondary">Back</a>
        </div>
        <form method="post" action="{{ route('sales-orders.update', $order) }}">
            @csrf
            @method('put')
            @include('salesman.orders._fields')
            <button class="btn btn-primary" type="submit">Update order</button>
        </form>
    </div>
@endsection
