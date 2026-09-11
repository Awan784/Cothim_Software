@extends('template.layout')
@section('title', 'Create Customer')

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h4 mb-0">Create Customer</h1>
                <p class="mb-0">Add a shop or pharmacy account.</p>
            </div>
            <div>
                <a href="{{ route('customers.index') }}" class="btn btn-sm btn-secondary">Back</a>
            </div>
        </div>

        <div class="card p-4">
            <form method="post" action="{{ route('customers.store') }}">
                @csrf
                @include('customers._fields')
                <button class="btn btn-gray-800" type="submit">Save</button>
            </form>
        </div>
    </div>
@endsection
