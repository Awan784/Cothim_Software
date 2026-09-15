@extends('template.layout')
@section('title', 'Create Purchase Return')

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h4 mb-0">Create Purchase Return</h1>
                <p class="mb-0">Return items to a supplier. Quantity is removed from stock on save.</p>
            </div>
            <div>
                <a href="{{ route('purchase-returns.index') }}" class="btn btn-sm btn-secondary">Back</a>
            </div>
        </div>

        <div class="card p-4">
            <form method="post" action="{{ route('purchase-returns.store') }}">
                @csrf
                @include('purchase-returns._form')
                <button class="btn btn-gray-800" type="submit">Save</button>
            </form>
        </div>
    </div>
@endsection
