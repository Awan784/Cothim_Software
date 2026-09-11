@extends('template.layout')
@section('title', 'Sales invoices')

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h4 mb-1">Sales invoices</h1>
                <p class="mb-0 text-muted">VAT tax invoices with ZATCA Phase 1 QR when issued.</p>
            </div>
            <a href="{{ route('invoices.create') }}" class="btn btn-primary">New invoice</a>
        </div>
        <div class="card">
            <div class="table-responsive py-3">
                <table class="table table-flush" data-datatable="true">
                    <thead class="thead-light">
                        <tr>
                            <th>No.</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>ZATCA</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">Due</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoices as $invoice)
                            <tr>
                                <td>{{ $invoice->invoice_no ?: 'Draft' }}</td>
                                <td>{{ $invoice->customer?->name }}</td>
                                <td>{{ ams_date($invoice->invoice_date) }}</td>
                                <td>{{ $invoice->status }}</td>
                                <td>{{ $invoice->zatca_status }}</td>
                                <td class="text-end">{{ number_format((float) $invoice->total, 2) }}</td>
                                <td class="text-end">{{ number_format($invoice->balanceDue(), 2) }}</td>
                                <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('invoices.show', $invoice) }}">Open</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted">No invoices yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
