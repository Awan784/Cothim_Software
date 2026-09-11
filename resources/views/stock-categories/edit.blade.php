@extends('template.layout')
@section('title', 'Edit Category')

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h4 mb-0">Edit Category</h1>
                <p class="mb-0">Update this inventory category.</p>
            </div>
            <div>
                <a href="{{ route('stock-categories.index') }}" class="btn btn-sm btn-secondary">Back</a>
            </div>
        </div>

        <div class="card p-4">
            <form method="post" action="{{ route('stock-categories.update', $stockCategory) }}">
                @csrf
                @method('put')
                @include('stock-categories._fields')
                <button class="btn btn-gray-800" type="submit">Update</button>
            </form>
        </div>
    </div>
@endsection
