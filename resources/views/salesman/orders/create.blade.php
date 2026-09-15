@extends('template.layout')
@section('title', 'Create order')

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between">
            <h1 class="h4 mb-0">Create order</h1>
            <a href="{{ route('salesman.orders.index') }}" class="btn btn-sm btn-secondary">Back</a>
        </div>
        <form method="post" action="{{ route('salesman.orders.store') }}">
            @csrf
            @include('salesman.orders._fields')
            <button class="btn btn-primary" type="submit">Submit order</button>
        </form>
    </div>
@endsection
