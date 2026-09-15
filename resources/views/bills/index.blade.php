@extends('template.layout')
@section('title', 'Purchase bills')

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h4 mb-1">Purchase bills</h1>
                <p class="mb-0 text-muted">Supplier bills with recoverable tax.</p>
            </div>
            <a href="{{ route('bills.create') }}" class="btn btn-primary">New bill</a>
        </div>
        <div class="card">
            <div class="table-responsive py-3">
                <table class="table table-flush" data-datatable="true">
                    <thead class="thead-light">
                        <tr>
                            <th>No.</th>
                            <th>Supplier</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th class="text-end">Tax</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">Due</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bills as $bill)
                            <tr>
                                <td>{{ $bill->bill_no ?: 'Draft' }}</td>
                                <td>{{ $bill->supplier?->name }}</td>
                                <td>{{ ams_date($bill->bill_date) }}</td>
                                <td>{{ $bill->status }}</td>
                                <td class="text-end">{{ number_format((float) $bill->vat_amount, 2) }}</td>
                                <td class="text-end">{{ number_format((float) $bill->total, 2) }}</td>
                                <td class="text-end">{{ number_format($bill->balanceDue(), 2) }}</td>
                                <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('bills.show', $bill) }}">Open</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted">No bills yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
