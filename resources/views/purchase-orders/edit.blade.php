@extends('template.layout')
@section('title', 'Edit Purchase Order')

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h4 mb-0">Edit Purchase Order</h1>
                <p class="mb-0">{{ $purchaseOrder->po_no }}</p>
            </div>
            <div>
                <a href="{{ route('purchase-orders.index') }}" class="btn btn-sm btn-secondary">Back</a>
            </div>
        </div>

        <div class="card p-4">
            <form method="post" action="{{ route('purchase-orders.update', $purchaseOrder) }}">
                @csrf
                @method('put')
                @include('purchase-orders._form')
                <button class="btn btn-gray-800" type="submit">Update</button>
            </form>
        </div>
    </div>
@endsection

