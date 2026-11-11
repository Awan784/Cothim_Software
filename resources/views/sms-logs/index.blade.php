@extends('template.layout')
@section('title', 'SMS')

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between align-items-center ams-page-header">
            <div>
                <h1 class="h4 mb-0">SMS</h1>
                <p class="mb-0">Track each dispatched message: number, date, and delivery status.</p>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body border-bottom pb-3">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="small text-muted me-1">Filter:</span>
                    @php
                        $filters = [
                            '' => 'All',
                            'sent' => 'Sent',
                            'failed' => 'Failed',
                            'blocked' => 'Blocked',
                            'skipped' => 'Skipped',
                        ];
                    @endphp
                    <div class="btn-group btn-group-sm" role="group" aria-label="SMS status filter">
                        @foreach($filters as $value => $label)
                            <a href="{{ route('sms-logs.index', $value !== '' ? ['status' => $value] : []) }}"
                                @class([
                                    'btn',
                                    'btn-gray-800' => $statusFilter === $value,
                                    'btn-outline-secondary' => $statusFilter !== $value,
                                ])>
                                {{ $label }}
                                @if($value === '')
                                    ({{ $counts->sum() }})
                                @else
                                    ({{ (int) ($counts[$value] ?? 0) }})
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="table-responsive py-4">
                <table class="table table-flush" data-datatable="true">
                    <thead class="thead-light">
                        <tr>
                            <th>Date</th>
                            <th>Number</th>
                            <th>To</th>
                            <th>Purpose</th>
                            <th>Invoice</th>
                            <th>Status</th>
                            <th>Reply</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td class="text-gray-900">{{ ams_date($log->created_at) }} {{ $log->created_at?->format('H:i') }}</td>
                                <td class="text-gray-900 fw-semibold">{{ $log->phone ?: '—' }}</td>
                                <td class="text-gray-900">
                                    {{ $log->recipient_name ?: '—' }}
                                    @if($log->recipient_type)
                                        <span class="d-block small text-muted">{{ ucfirst($log->recipient_type) }}</span>
                                    @endif
                                </td>
                                <td class="text-gray-900">{{ ucfirst(str_replace('_', ' ', $log->purpose)) }}</td>
                                <td class="text-gray-900">
                                    @if($log->invoice)
                                        <a href="{{ route('invoices.show', $log->invoice) }}">{{ $log->invoice->invoice_no ?: '#'.$log->invoice->id }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ $log->statusBadgeClass() }}">{{ $log->statusLabel() }}</span>
                                </td>
                                <td class="text-gray-700 small" style="max-width: 280px;">
                                    {{ \Illuminate\Support\Str::limit(trim((string) $log->provider_reply), 120) ?: '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-gray-600">No SMS sent yet. Dispatch from Sales invoices to see logs here.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
