@extends('template.layout')
@section('title', 'Edit Customer')

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h4 mb-0">Edit Customer</h1>
                <p class="mb-0">Update shop details.</p>
            </div>
            <div>
                <a href="{{ route('customers.index') }}" class="btn btn-sm btn-secondary">Back</a>
            </div>
        </div>

        <div class="card p-4">
            <form method="post" action="{{ route('customers.update', $customer) }}">
                @csrf
                @method('put')
                @include('customers._fields')
                <button class="btn btn-gray-800" type="submit">Update</button>
            </form>
        </div>
    </div>
@endsection
