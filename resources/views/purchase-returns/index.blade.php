@extends('template.layout')
@section('title', 'Purchase Returns')

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h4 mb-0">Purchase Returns</h1>
                <p class="mb-0">Return purchased items to suppliers. Stock quantity decreases.</p>
            </div>
            <div>
                <a href="{{ route('purchase-returns.create') }}" class="btn btn-sm btn-gray-800">Create Purchase Return</a>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive py-4">
                <table class="table table-flush" data-datatable="true" id="prReturnsTable">
                    <thead class="thead-light">
                        <tr>
                            <th>Date</th>
                            <th>Return No</th>
                            <th>Supplier</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($purchaseReturns as $pr)
                            <tr class="pr-row" data-pr-id="{{ $pr->id }}" tabindex="0">
                                <td class="text-gray-900">{{ ams_date($pr->return_date) }}</td>
                                <td class="text-gray-900">
                                    <button type="button" class="btn btn-link p-0 pr-open" data-pr-id="{{ $pr->id }}">{{ $pr->return_no }}</button>
                                </td>
                                <td class="text-gray-900">
                                    <a href="{{ route('suppliers.show', $pr->supplier_id) }}">{{ $pr->supplier?->name }}</a>
                                </td>
                                <td class="text-gray-900 text-end">{{ ams_num($pr->total_amount) }}</td>
                                <td class="text-end text-nowrap pr-row-actions">
                                    <a href="{{ route('purchase-returns.print', $pr) }}" target="_blank" class="btn btn-sm btn-outline-primary">Print</a>
                                    <a href="{{ route('purchase-returns.edit', $pr) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <form action="{{ route('purchase-returns.destroy', $pr) }}" method="post" class="d-inline">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this purchase return?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-gray-600">No purchase returns yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @php
        $prDetails = $purchaseReturns->mapWithKeys(function ($pr) {
            return [$pr->id => [
                'return_no' => $pr->return_no,
                'return_date' => ams_date($pr->return_date),
                'supplier' => $pr->supplier?->name ?: '—',
                'supplier_meta' => collect([$pr->supplier?->phone, $pr->supplier?->city])->filter()->join(' · ') ?: '—',
                'notes' => $pr->notes ?: '—',
                'total' => ams_num($pr->total_amount),
                'print_url' => route('purchase-returns.print', $pr),
                'edit_url' => route('purchase-returns.edit', $pr),
                'items' => $pr->items->map(fn ($item) => [
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
        <div class="modal fade" id="prDetailModal" tabindex="-1" aria-labelledby="prDetailModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title mb-0" id="prDetailModalLabel">Purchase return</h5>
                            <div class="small text-muted" id="prDetailNo">—</div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3 mb-3">
                            <div class="col-sm-4">
                                <div class="small text-muted">Date</div>
                                <div class="fw-semibold" id="prDetailDate">—</div>
                            </div>
                            <div class="col-sm-8">
                                <div class="small text-muted">Supplier</div>
                                <div class="fw-semibold" id="prDetailSupplier">—</div>
                                <div class="small text-muted" id="prDetailSupplierMeta">—</div>
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
                                <tbody id="prDetailItems"></tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="4" class="text-end"><strong>Total</strong></td>
                                        <td class="text-end"><strong id="prDetailTotal">—</strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <div class="mt-3">
                            <div class="small text-muted">Notes</div>
                            <div id="prDetailNotes">—</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="#" class="btn btn-primary" id="prDetailPrintBtn" target="_blank">Print</a>
                        <a href="#" class="btn btn-outline-primary" id="prDetailEditBtn">Edit</a>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    @endpush
@endsection

@push('scripts')
    <style>
        .pr-row { cursor: pointer; }
        .pr-row:hover { filter: brightness(0.97); }
        .pr-open { font-weight: 600; text-decoration: none; }
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
            const details = @json($prDetails);
            const modalEl = document.getElementById('prDetailModal');
            const modal = modalEl ? new bootstrap.Modal(modalEl) : null;

            function esc(value) {
                return String(value == null ? '' : value)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;');
            }

            function showPr(id) {
                const pr = details[id];
                if (!pr || !modal) return;

                document.getElementById('prDetailNo').textContent = pr.return_no;
                document.getElementById('prDetailDate').textContent = pr.return_date;
                document.getElementById('prDetailSupplier').textContent = pr.supplier;
                document.getElementById('prDetailSupplierMeta').textContent = pr.supplier_meta;
                document.getElementById('prDetailNotes').textContent = pr.notes;
                document.getElementById('prDetailTotal').textContent = pr.total;
                document.getElementById('prDetailPrintBtn').href = pr.print_url;
                document.getElementById('prDetailEditBtn').href = pr.edit_url;

                const body = document.getElementById('prDetailItems');
                body.innerHTML = '';
                pr.items.forEach(function (item) {
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
                const openBtn = event.target.closest('.pr-open');
                if (openBtn) {
                    event.preventDefault();
                    showPr(openBtn.dataset.prId);
                    return;
                }

                const row = event.target.closest('.pr-row');
                if (!row) return;
                if (event.target.closest('.pr-row-actions, a, button, form')) return;
                showPr(row.dataset.prId);
            });
        })();
    </script>
@endpush
