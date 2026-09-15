@extends('template.layout')
@section('title', 'Edit sales invoice')

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between">
            <h1 class="h4 mb-0">Edit sales invoice</h1>
            <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-sm btn-secondary">Back</a>
        </div>
        <form method="post" action="{{ route('invoices.update', $invoice) }}">
            @csrf
            @method('put')
            @include('invoices._fields', ['invoice' => $invoice, 'customers' => $customers, 'vatRate' => $vatRate])
            <button class="btn btn-primary" type="submit">Update draft</button>
        </form>
    </div>
@endsection
