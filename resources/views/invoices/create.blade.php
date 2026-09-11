@extends('template.layout')
@section('title', 'New invoice')

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between">
            <div>
                <h1 class="h4 mb-0">New tax invoice</h1>
                <p class="mb-0 text-muted">Save as draft, then issue to generate the ZATCA QR.</p>
            </div>
            <a href="{{ route('invoices.index') }}" class="btn btn-sm btn-secondary">Back</a>
        </div>
        <form method="post" action="{{ route('invoices.store') }}">
            @csrf
            @include('invoices._fields', ['invoice' => null, 'customers' => $customers, 'vatRate' => $vatRate])
            <button class="btn btn-primary" type="submit">Save draft</button>
        </form>
    </div>
@endsection
