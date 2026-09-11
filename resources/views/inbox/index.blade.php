@extends('template.layout')
@section('title', 'Inbox')

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h4 mb-1">Inbox</h1>
                <p class="mb-0 text-muted">Receipts, bills, and statements waiting to be posted.</p>
            </div>
            <a href="{{ route('inbox.create') }}" class="btn btn-primary">Upload document</a>
        </div>

        <div class="card">
            <div class="table-responsive py-3">
                <table class="table table-flush" data-datatable="true">
                    <thead class="thead-light">
                        <tr>
                            <th>Document</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                            <tr>
                                <td class="text-gray-900">{{ $item->title }}</td>
                                <td>{{ str_replace('_', ' ', $item->type) }}</td>
                                <td>{{ $item->extracted_amount !== null ? number_format((float) $item->extracted_amount, 2) : '—' }}</td>
                                <td>{{ $item->extracted_date ? ams_date($item->extracted_date) : '—' }}</td>
                                <td><span class="badge bg-{{ $item->status === 'new' ? 'warning' : ($item->status === 'posted' ? 'success' : 'secondary') }}">{{ $item->status }}</span></td>
                                <td class="text-end"><a href="{{ route('inbox.show', $item) }}" class="btn btn-sm btn-outline-primary">Open</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted">Inbox is empty. Upload a receipt or bill to start.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
