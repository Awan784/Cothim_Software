@extends('template.layout')
@section('title', 'Create Purchase Order')

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h4 mb-0">Create Purchase Order</h1>
                <p class="mb-0">Buy inventory items. Quantity is added to stock on save.</p>
            </div>
            <div>
                <a href="{{ route('purchase-orders.index') }}" class="btn btn-sm btn-secondary">Back</a>
            </div>
        </div>

        <div class="card p-4">
            <form method="post" action="{{ route('purchase-orders.store') }}">
                @csrf
                @include('purchase-orders._form')
                <button class="btn btn-gray-800" type="submit">Save</button>
            </form>
        </div>
    </div>
@endsection

