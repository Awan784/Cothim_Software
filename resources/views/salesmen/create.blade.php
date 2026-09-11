@extends('template.layout')
@section('title', 'Create Salesman')

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h4 mb-0">Create Salesman</h1>
                <p class="mb-0">Add a salesman with city, username, and password.</p>
            </div>
            <div>
                <a href="{{ route('salesmen.index') }}" class="btn btn-sm btn-secondary">Back</a>
            </div>
        </div>

        <div class="card p-4">
            <form method="post" action="{{ route('salesmen.store') }}">
                @csrf
                @include('salesmen._fields')
                <button class="btn btn-gray-800" type="submit">Save</button>
            </form>
        </div>
    </div>
@endsection
