@extends('template.layout')
@section('title', 'Sales invoices')

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h4 mb-1">Sales invoices</h1>
            </div>
            <a href="{{ route('invoices.create') }}" class="btn btn-primary">Create sales invoice</a>
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
                            <th class="text-end">Due</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoices as $invoice)
                            @php $status = $invoice->listStatus(); @endphp
                            <tr>
                                <td class="text-gray-900 fw-semibold">{{ $invoice->invoice_no ?: '—' }}</td>
                                <td>
                                    @if($invoice->customer)
                                        <a href="{{ route('customers.show', $invoice->customer) }}" class="fw-semibold text-gray-900">{{ $invoice->customer->displayName() }}</a>
                                        @if($invoice->customer->name && $invoice->customer->name !== $invoice->customer->displayName())
                                            <div class="small text-muted">{{ $invoice->customer->name }}</div>
                                        @endif
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td data-order="{{ optional($invoice->invoice_date)->format('Y-m-d') }}">
                                    <span class="d-none">{{ optional($invoice->invoice_date)->format('Ymd') }}</span>
                                    {{ ams_date($invoice->invoice_date) ?: '—' }}
                                </td>
                                <td>
                                    <span class="ams-status-tag is-{{ $status }}">{{ $invoice->listStatusLabel() }}</span>
                                </td>
                                <td class="text-end text-gray-900">{{ ams_num($invoice->total) }}</td>
                                <td class="text-end {{ $invoice->balanceDue() > 0.009 ? 'text-danger fw-semibold' : 'text-muted' }}">{{ ams_num($invoice->balanceDue()) }}</td>
                                <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('invoices.show', $invoice) }}">Open</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted">No invoices yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
