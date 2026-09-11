@extends('template.layout')
@section('title', 'Create Item')

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h4 mb-0">Create Item</h1>
                <p class="mb-0">Add a homeopathic medicine to inventory.</p>
            </div>
            <div>
                <a href="{{ route('stock-items.index') }}" class="btn btn-sm btn-secondary">Back</a>
            </div>
        </div>

        <div class="card p-4">
            <form method="post" action="{{ route('stock-items.store') }}">
                @csrf
                @include('stock-items._fields')
                <button class="btn btn-gray-800" type="submit">Save</button>
            </form>
        </div>
    </div>
    @include('stock-items._variant-script')
@endsection
