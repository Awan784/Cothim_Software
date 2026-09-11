@extends('template.layout')
@section('title', 'Edit bill')

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between">
            <h1 class="h4 mb-0">Edit draft bill</h1>
            <a href="{{ route('bills.show', $bill) }}" class="btn btn-sm btn-secondary">Back</a>
        </div>
        <form method="post" action="{{ route('bills.update', $bill) }}">
            @csrf
            @method('put')
            @include('bills._fields', ['bill' => $bill, 'suppliers' => $suppliers, 'vatRate' => $vatRate])
            <button class="btn btn-primary" type="submit">Update draft</button>
        </form>
    </div>
@endsection
