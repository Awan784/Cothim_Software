@extends('template.layout')
@section('title', 'Edit order')

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between">
            <h1 class="h4 mb-0">Edit {{ $order->order_no }}</h1>
            <a href="{{ route('salesman.orders.show', $order) }}" class="btn btn-sm btn-secondary">Back</a>
        </div>
        <form method="post" action="{{ route('salesman.orders.update', $order) }}">
            @csrf
            @method('put')
            @include('salesman.orders._fields')
            <button class="btn btn-primary" type="submit">Update order</button>
        </form>
    </div>
@endsection
