@extends('template.layout')
@section('title', 'Create Category')

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h4 mb-0">Create Category</h1>
                <p class="mb-0">Group medicines before you add items.</p>
            </div>
            <div>
                <a href="{{ route('stock-categories.index') }}" class="btn btn-sm btn-secondary">Back</a>
            </div>
        </div>

        <div class="card p-4">
            <form method="post" action="{{ route('stock-categories.store') }}">
                @csrf
                @include('stock-categories._fields', ['stockCategory' => new \App\Models\StockCategory(['is_active' => true])])
                <button class="btn btn-gray-800" type="submit">Save</button>
            </form>
        </div>
    </div>
@endsection
