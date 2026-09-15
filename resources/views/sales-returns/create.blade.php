@extends('template.layout')
@section('title', 'Create Sales Return')

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h4 mb-0">Create Sales Return</h1>
                <p class="mb-0">Take goods back from a customer. Quantity is added to stock on save.</p>
            </div>
            <div>
                <a href="{{ route('sales-returns.index') }}" class="btn btn-sm btn-secondary">Back</a>
            </div>
        </div>

        <div class="card p-4">
            <form method="post" action="{{ route('sales-returns.store') }}">
                @csrf
                @include('sales-returns._form')
                <button class="btn btn-gray-800" type="submit">Save</button>
            </form>
        </div>
    </div>
@endsection
