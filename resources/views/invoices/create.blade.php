@extends('template.layout')
@section('title', 'Create sales invoice')

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between">
            <h1 class="h4 mb-0">Create sales invoice</h1>
            <a href="{{ route('invoices.index') }}" class="btn btn-sm btn-secondary">Back</a>
        </div>
        <form method="post" action="{{ route('invoices.store') }}">
            @csrf
            @include('invoices._fields', ['invoice' => null, 'customers' => $customers, 'vatRate' => $vatRate])
            <button class="btn btn-primary" type="submit">Generate invoice</button>
        </form>
    </div>
@endsection
