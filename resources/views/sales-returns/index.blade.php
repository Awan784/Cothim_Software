@extends('template.layout')
@section('title', 'Sales Returns')

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h4 mb-0">Sales Returns</h1>
                <p class="mb-0">Return sold items from customers. Stock quantity increases.</p>
            </div>
            <div>
                <a href="{{ route('sales-returns.create') }}" class="btn btn-sm btn-gray-800">Create Sales Return</a>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive py-4">
                <table class="table table-flush" data-datatable="true" id="srReturnsTable">
                    <thead class="thead-light">
                        <tr>
                            <th>Date</th>
                            <th>Return No</th>
                            <th>Customer</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($salesReturns as $sr)
                            <tr class="sr-row" data-sr-id="{{ $sr->id }}" tabindex="0">
                                <td class="text-gray-900">{{ ams_date($sr->return_date) }}</td>
                                <td class="text-gray-900">
                                    <button type="button" class="btn btn-link p-0 sr-open" data-sr-id="{{ $sr->id }}">{{ $sr->return_no }}</button>
                                </td>
                                <td class="text-gray-900">
                                    <a href="{{ route('customers.show', $sr->customer_id) }}">{{ $sr->customer?->displayName() }}</a>
                                </td>
                                <td class="text-gray-900 text-end">{{ ams_num($sr->total_amount) }}</td>
                                <td class="text-end text-nowrap sr-row-actions">
                                    <a href="{{ route('sales-returns.print', $sr) }}" target="_blank" class="btn btn-sm btn-outline-primary">Print</a>
                                    <a href="{{ route('sales-returns.edit', $sr) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <form action="{{ route('sales-returns.destroy', $sr) }}" method="post" class="d-inline">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this sales return?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-gray-600">No sales returns yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @php
        $srDetails = $salesReturns->mapWithKeys(function ($sr) {
            return [$sr->id => [
                'return_no' => $sr->return_no,
                'return_date' => ams_date($sr->return_date),
                'customer' => $sr->customer?->displayName() ?: '—',
                'customer_meta' => collect([$sr->customer?->mobile ?: $sr->customer?->phone, $sr->customer?->city])->filter()->join(' · ') ?: '—',
                'notes' => $sr->notes ?: '—',
                'total' => ams_num($sr->total_amount),
                'print_url' => route('sales-returns.print', $sr),
                'edit_url' => route('sales-returns.edit', $sr),
                'items' => $sr->items->map(fn ($item) => [
                    'name' => $item->displayName(),
                    'unit' => strtoupper($item->unit ?: 'PCS'),
                    'qty' => ams_num($item->quantity),
                    'price' => ams_num($item->unit_price),
                    'total' => ams_num($item->line_total),
                    'note' => $item->note ?: '',
                ])->values(),
            ]];
        });
    @endphp

    @push('modals')
        <div class="modal fade" id="srDetailModal" tabindex="-1" aria-labelledby="srDetailModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title mb-0" id="srDetailModalLabel">Sales return</h5>
                            <div class="small text-muted" id="srDetailNo">—</div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3 mb-3">
                            <div class="col-sm-4">
                                <div class="small text-muted">Date</div>
                                <div class="fw-semibold" id="srDetailDate">—</div>
                            </div>
                            <div class="col-sm-8">
                                <div class="small text-muted">Customer</div>
                                <div class="fw-semibold" id="srDetailCustomer">—</div>
                                <div class="small text-muted" id="srDetailCustomerMeta">—</div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm mb-0 po-excel-table">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Unit</th>
                                        <th class="text-end">Qty</th>
                                        <th class="text-end">Rate</th>
                                        <th class="text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody id="srDetailItems"></tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="4" class="text-end"><strong>Total</strong></td>
                                        <td class="text-end"><strong id="srDetailTotal">—</strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <div class="mt-3">
                            <div class="small text-muted">Notes</div>
                            <div id="srDetailNotes">—</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="#" class="btn btn-primary" id="srDetailPrintBtn" target="_blank">Print</a>
                        <a href="#" class="btn btn-outline-primary" id="srDetailEditBtn">Edit</a>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    @endpush
@endsection

@push('scripts')
    <style>
        .sr-row { cursor: pointer; }
        .sr-row:hover { filter: brightness(0.97); }
        .sr-open { font-weight: 600; text-decoration: none; }
        .po-excel-table {
            border-collapse: collapse;
            width: 100%;
        }
        .po-excel-table th,
        .po-excel-table td {
            border: 1px solid #000;
            padding: 0.45rem 0.6rem;
        }
        .po-excel-table th {
            background: #f3f3f3;
            font-weight: 700;
        }
        .po-excel-table tfoot td {
            font-weight: 700;
            background: #f3f3f3;
        }
    </style>
    <script>
        (function () {
            const details = @json($srDetails);
            const modalEl = document.getElementById('srDetailModal');
            const modal = modalEl ? new bootstrap.Modal(modalEl) : null;

            function esc(value) {
                return String(value == null ? '' : value)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;');
            }

            function showSr(id) {
                const sr = details[id];
                if (!sr || !modal) return;

                document.getElementById('srDetailNo').textContent = sr.return_no;
                document.getElementById('srDetailDate').textContent = sr.return_date;
                document.getElementById('srDetailCustomer').textContent = sr.customer;
                document.getElementById('srDetailCustomerMeta').textContent = sr.customer_meta;
                document.getElementById('srDetailNotes').textContent = sr.notes;
                document.getElementById('srDetailTotal').textContent = sr.total;
                document.getElementById('srDetailPrintBtn').href = sr.print_url;
                document.getElementById('srDetailEditBtn').href = sr.edit_url;

                const body = document.getElementById('srDetailItems');
                body.innerHTML = '';
                sr.items.forEach(function (item) {
                    const tr = document.createElement('tr');
                    tr.innerHTML =
                        '<td>' + esc(item.name) + (item.note ? '<div class="small text-muted">' + esc(item.note) + '</div>' : '') + '</td>' +
                        '<td>' + esc(item.unit) + '</td>' +
                        '<td class="text-end">' + esc(item.qty) + '</td>' +
                        '<td class="text-end">' + esc(item.price) + '</td>' +
                        '<td class="text-end">' + esc(item.total) + '</td>';
                    body.appendChild(tr);
                });

                modal.show();
            }

            document.addEventListener('click', function (event) {
                const openBtn = event.target.closest('.sr-open');
                if (openBtn) {
                    event.preventDefault();
                    showSr(openBtn.dataset.srId);
                    return;
                }

                const row = event.target.closest('.sr-row');
                if (!row) return;
                if (event.target.closest('.sr-row-actions, a, button, form')) return;
                showSr(row.dataset.srId);
            });
        })();
    </script>
@endpush
