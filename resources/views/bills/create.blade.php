@extends('template.layout')
@section('title', 'New bill')

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between">
            <h1 class="h4 mb-0">New purchase bill</h1>
            <a href="{{ route('bills.index') }}" class="btn btn-sm btn-secondary">Back</a>
        </div>
        <form method="post" action="{{ route('bills.store') }}">
            @csrf
            @include('bills._fields', ['bill' => null, 'suppliers' => $suppliers, 'vatRate' => $vatRate])
            <button class="btn btn-primary" type="submit">Save draft</button>
        </form>
    </div>
@endsection
