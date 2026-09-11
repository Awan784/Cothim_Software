@extends('template.layout')
@section('title', 'Edit Item')

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h4 mb-0">Edit Item</h1>
                <p class="mb-0">Update medicine details.</p>
            </div>
            <div>
                <a href="{{ route('stock-items.index') }}" class="btn btn-sm btn-secondary">Back</a>
            </div>
        </div>

        <div class="card p-4">
            <form method="post" action="{{ route('stock-items.update', $stockItem) }}">
                @csrf
                @method('put')
                @include('stock-items._fields')
                <button class="btn btn-gray-800" type="submit">Update</button>
            </form>
        </div>
    </div>
    @include('stock-items._variant-script')
@endsection
