@extends('template.layout')
@section('title', 'Salesman settlements')

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between align-items-center ams-page-header">
            <div>
                <h1 class="h4 mb-1">Salesman settlements</h1>
                <p class="mb-0 text-muted">Receive cash from a salesman once, allocate it to customer invoices, and keep the leftover as advance.</p>
            </div>
            <div>
                <a href="{{ route('salesman-settlements.create') }}" class="btn btn-sm btn-gray-800">New settlement</a>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive py-4">
                <table class="table table-flush" data-datatable="true">
                    <thead class="thead-light">
                        <tr>
                            <th>No.</th>
                            <th>Date</th>
                            <th>Salesman</th>
                            <th class="text-end">Cash received</th>
                            <th class="text-end">Allocated to invoices</th>
                            <th class="text-end">Advance after</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($settlements as $settlement)
                            <tr>
                                <td class="fw-semibold">{{ $settlement->settlement_no }}</td>
                                <td>{{ ams_date($settlement->settlement_date) }}</td>
                                <td>{{ $settlement->salesman?->name ?: '—' }}</td>
                                <td class="text-end">{{ number_format((float) $settlement->cash_received, 2) }}</td>
                                <td class="text-end">{{ number_format((float) $settlement->allocated_amount, 2) }}</td>
                                <td class="text-end">{{ number_format((float) $settlement->closing_advance, 2) }}</td>
                                <td class="text-end">
                                    <a href="{{ route('salesman-settlements.show', $settlement) }}" class="btn btn-sm btn-outline-primary">Open</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">No salesman settlements yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
