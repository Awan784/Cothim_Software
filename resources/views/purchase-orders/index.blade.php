@extends('template.layout')
@section('title', 'Purchase Orders')

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h4 mb-0">Purchase Orders</h1>
                <p class="mb-0">Supplier purchase notes with items.</p>
            </div>
            <div>
                <a href="{{ route('purchase-orders.create') }}" class="btn btn-sm btn-gray-800">Create Purchase Order</a>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive py-4">
                <table class="table table-flush" data-datatable="true" id="poOrdersTable">
                    <thead class="thead-light">
                        <tr>
                            <th>Date</th>
                            <th>PO No</th>
                            <th>Supplier</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($purchaseOrders as $po)
                            <tr class="po-row" data-po-id="{{ $po->id }}" tabindex="0">
                                <td class="text-gray-900">{{ ams_date($po->po_date) }}</td>
                                <td class="text-gray-900">
                                    <button type="button" class="btn btn-link p-0 po-open" data-po-id="{{ $po->id }}">{{ $po->po_no }}</button>
                                </td>
                                <td class="text-gray-900">
                                    <a href="{{ route('suppliers.show', $po->supplier_id) }}">{{ $po->supplier?->name }}</a>
                                </td>
                                <td class="text-gray-900 text-end">{{ ams_num($po->total_amount) }}</td>
                                <td class="text-end text-nowrap po-row-actions">
                                    <a href="{{ route('purchase-orders.print', $po) }}" target="_blank" class="btn btn-sm btn-outline-primary">Print</a>
                                    <a href="{{ route('purchase-orders.edit', $po) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <form action="{{ route('purchase-orders.destroy', $po) }}" method="post" class="d-inline">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this purchase order?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-gray-600">No purchase orders yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @php
        $poDetails = $purchaseOrders->mapWithKeys(function ($po) {
            return [$po->id => [
                'po_no' => $po->po_no,
                'po_date' => ams_date($po->po_date),
                'supplier' => $po->supplier?->name ?: '—',
                'supplier_meta' => collect([$po->supplier?->phone, $po->supplier?->city])->filter()->join(' · ') ?: '—',
                'notes' => $po->notes ?: '—',
                'total' => ams_num($po->total_amount),
                'print_url' => route('purchase-orders.print', $po),
                'edit_url' => route('purchase-orders.edit', $po),
                'items' => $po->items->map(fn ($item) => [
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
        <div class="modal fade" id="poDetailModal" tabindex="-1" aria-labelledby="poDetailModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title mb-0" id="poDetailModalLabel">Purchase order</h5>
                            <div class="small text-muted" id="poDetailNo">—</div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3 mb-3">
                            <div class="col-sm-4">
                                <div class="small text-muted">Date</div>
                                <div class="fw-semibold" id="poDetailDate">—</div>
                            </div>
                            <div class="col-sm-8">
                                <div class="small text-muted">Supplier</div>
                                <div class="fw-semibold" id="poDetailSupplier">—</div>
                                <div class="small text-muted" id="poDetailSupplierMeta">—</div>
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
                                <tbody id="poDetailItems"></tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="4" class="text-end"><strong>Total</strong></td>
                                        <td class="text-end"><strong id="poDetailTotal">—</strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <div class="mt-3">
                            <div class="small text-muted">Notes</div>
                            <div id="poDetailNotes">—</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="#" class="btn btn-primary" id="poDetailPrintBtn" target="_blank">Print</a>
                        <a href="#" class="btn btn-outline-primary" id="poDetailEditBtn">Edit</a>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    @endpush
@endsection

@push('scripts')
    <style>
        .po-row { cursor: pointer; }
        .po-row:hover { filter: brightness(0.97); }
        .po-open { font-weight: 600; text-decoration: none; }
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
            const details = @json($poDetails);
            const modalEl = document.getElementById('poDetailModal');
            const modal = modalEl ? new bootstrap.Modal(modalEl) : null;

            function esc(value) {
                return String(value == null ? '' : value)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;');
            }

            function showPo(id) {
                const po = details[id];
                if (!po || !modal) return;

                document.getElementById('poDetailNo').textContent = po.po_no;
                document.getElementById('poDetailDate').textContent = po.po_date;
                document.getElementById('poDetailSupplier').textContent = po.supplier;
                document.getElementById('poDetailSupplierMeta').textContent = po.supplier_meta;
                document.getElementById('poDetailNotes').textContent = po.notes;
                document.getElementById('poDetailTotal').textContent = po.total;
                document.getElementById('poDetailPrintBtn').href = po.print_url;
                document.getElementById('poDetailEditBtn').href = po.edit_url;

                const body = document.getElementById('poDetailItems');
                body.innerHTML = '';
                po.items.forEach(function (item) {
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
                const openBtn = event.target.closest('.po-open');
                if (openBtn) {
                    event.preventDefault();
                    showPo(openBtn.dataset.poId);
                    return;
                }

                const row = event.target.closest('.po-row');
                if (!row) return;
                if (event.target.closest('.po-row-actions, a, button, form')) return;
                showPo(row.dataset.poId);
            });
        })();
    </script>
@endpush
