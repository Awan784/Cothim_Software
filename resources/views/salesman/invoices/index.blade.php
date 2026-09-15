@extends('template.layout')
@section('title', 'My invoices')

@section('content')
    <div class="pb-4">
        <div class="py-4">
            <h1 class="h4 mb-1">My invoices</h1>
            <p class="mb-0 text-muted">Invoices generated after admin confirmed your orders.</p>
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
                            <th class="text-end">Total</th>
                            <th class="text-end">Commission</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoices as $invoice)
                            @php $status = $invoice->listStatus(); @endphp
                            <tr>
                                <td class="fw-semibold">{{ $invoice->invoice_no ?: '—' }}</td>
                                <td>{{ $invoice->customer?->displayName() ?: '—' }}</td>
                                <td data-order="{{ optional($invoice->invoice_date)->format('Y-m-d') }}">{{ ams_date($invoice->invoice_date) }}</td>
                                <td><span class="ams-status-tag is-{{ $status }}">{{ $invoice->listStatusLabel() }}</span></td>
                                <td class="text-end">{{ ams_num($invoice->total) }}</td>
                                <td class="text-end">{{ ams_num($invoice->salesman_commission_amount) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted">No confirmed invoices yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
