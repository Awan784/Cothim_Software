@extends('template.layout')
@section('title', 'Cash Vouchers')

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between align-items-center ams-page-header">
            <div>
                <h1 class="h4 mb-0">Cash Vouchers</h1>
                <p class="mb-0">Cash receive and cash payment vouchers.</p>
            </div>
            <div>
                <a href="{{ route('cash-vouchers.create') }}" class="btn btn-sm btn-gray-800">Create Voucher</a>
            </div>
        </div>

        <div class="card shadow-sm">
            @php
                $accountFilterParams = array_filter([
                    'account_type' => $accountTypeFilter,
                    'account_id' => $accountIdFilter,
                ]);
            @endphp
            <div class="card-body border-bottom pb-3">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="small text-muted me-1">Filter:</span>
                    <div class="btn-group btn-group-sm" role="group" aria-label="Voucher type filter">
                        <a href="{{ route('cash-vouchers.index', $accountFilterParams) }}"
                            @class(['btn', 'btn-gray-800' => ! $typeFilter, 'btn-outline-secondary' => $typeFilter])>
                            All
                        </a>
                        <a href="{{ route('cash-vouchers.index', array_merge($accountFilterParams, ['type' => 'receive'])) }}"
                            @class(['btn', 'btn-success' => $typeFilter === 'receive', 'btn-outline-success' => $typeFilter !== 'receive'])>
                            Cash Received
                        </a>
                        <a href="{{ route('cash-vouchers.index', array_merge($accountFilterParams, ['type' => 'payment'])) }}"
                            @class(['btn', 'btn-danger' => $typeFilter === 'payment', 'btn-outline-danger' => $typeFilter !== 'payment'])>
                            Cash Payment
                        </a>
                    </div>
                </div>

                <form method="get" class="row g-2 align-items-end mt-3">
                    @if ($typeFilter)
                        <input type="hidden" name="type" value="{{ $typeFilter }}">
                    @endif
                    <div class="col-md-3">
                        <label for="filter_account_type" class="form-label small mb-1">Account Type</label>
                        <select name="account_type" id="filter_account_type" class="form-select form-select-sm">
                            <option value="">All account types</option>
                            @foreach (['customer' => 'Customer', 'supplier' => 'Supplier', 'investor' => 'Investor', 'expense' => 'Expense', 'other' => 'Other'] as $value => $label)
                                <option value="{{ $value }}" @selected($accountTypeFilter === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="filter_account_id" class="form-label small mb-1">Account</label>
                        <select name="account_id" id="filter_account_id" class="form-select form-select-sm">
                            <option value="">All accounts</option>
                        </select>
                    </div>
                    <div class="col-md-auto d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-gray-800">Apply</button>
                        <a href="{{ route('cash-vouchers.index', $typeFilter ? ['type' => $typeFilter] : []) }}"
                            class="btn btn-sm btn-outline-secondary">Clear Account</a>
                    </div>
                </form>
            </div>
            <div class="table-responsive py-4">
                <table class="table table-flush" data-datatable="true">
                    <thead class="thead-light">
                        <tr>
                            <th>Date</th>
                            <th>Voucher No</th>
                            <th>Type</th>
                            <th>Payment</th>
                            <th>Account</th>
                            <th class="text-end">Amount</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cashVouchers as $v)
                            <tr
                                @class([
                                    'voucher-row',
                                    'table-success' => $v->type === 'receive',
                                    'table-danger' => $v->type === 'payment',
                                ])
                                role="button"
                                tabindex="0"
                                data-voucher-id="{{ $v->id }}"
                                aria-label="View voucher {{ $v->voucher_no }}"
                            >
                                <td class="text-gray-900">{{ ams_date($v->voucher_date) }}</td>
                                <td class="text-gray-900 fw-semibold">{{ $v->voucher_no }}</td>
                                <td class="text-gray-900 text-uppercase">{{ $v->type }}</td>
                                <td class="text-gray-900 text-uppercase">
                                    @if(($v->payment_method ?? 'cash') === 'bank')
                                        Bank{{ $v->bankAccount ? ': '.$v->bankAccount->name : '' }}
                                    @else
                                        Cash
                                    @endif
                                </td>
                                <td class="text-gray-900">
                                    {{ ucfirst($v->account_type) }}
                                    @if($v->account_display_name)
                                        - {{ $v->account_display_name }}
                                    @endif
                                </td>
                                <td class="text-gray-900 text-end {{ $v->type === 'payment' ? 'text-danger' : 'text-success' }}">
                                    {{ number_format((float) $v->amount, 2) }}
                                </td>
                                <td class="text-end text-nowrap voucher-row-actions">
                                    <a href="{{ route('cash-vouchers.print', $v) }}" target="_blank"
                                        class="btn btn-sm btn-outline-primary">Print Invoice</a>
                                    <a href="{{ route('cash-vouchers.edit', $v) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <form action="{{ route('cash-vouchers.destroy', $v) }}" method="post" class="d-inline">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this voucher?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-gray-600">
                                    @if ($typeFilter === 'receive')
                                        No cash received vouchers found.
                                    @elseif ($typeFilter === 'payment')
                                        No cash payment vouchers found.
                                    @elseif ($accountTypeFilter)
                                        No vouchers found for the selected account.
                                    @else
                                        No vouchers yet.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @php
        $voucherDetails = $cashVouchers->mapWithKeys(function ($v) {
            $paymentLabel = ($v->payment_method ?? 'cash') === 'bank'
                ? 'Bank'.($v->bankAccount ? ' — '.$v->bankAccount->name : '')
                : 'Cash'.($v->cashAccount ? ' — '.$v->cashAccount->name : '');

            return [$v->id => [
                'voucher_no' => $v->voucher_no,
                'voucher_date' => ams_date($v->voucher_date),
                'type' => $v->type,
                'type_label' => $v->type === 'receive' ? 'Cash Receive' : 'Cash Payment',
                'payment_label' => $paymentLabel,
                'account_type' => ucfirst($v->account_type),
                'account_name' => $v->account_display_name ?: '—',
                'amount' => number_format((float) $v->amount, 2),
                'reference' => $v->reference ?: '—',
                'notes' => $v->notes ?: '—',
                'edit_url' => route('cash-vouchers.edit', $v),
                'print_url' => route('cash-vouchers.print', $v),
            ]];
        });
    @endphp

    @push('modals')
        <div class="modal fade" id="voucherDetailModal" tabindex="-1" aria-labelledby="voucherDetailModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header" id="voucherDetailModalHeader">
                        <div>
                            <h5 class="modal-title mb-1" id="voucherDetailModalLabel">Voucher Details</h5>
                            <span class="badge" id="voucherDetailTypeBadge"></span>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <div class="small text-muted">Voucher No</div>
                                <div class="fw-semibold" id="detailVoucherNo">—</div>
                            </div>
                            <div class="col-sm-6">
                                <div class="small text-muted">Date</div>
                                <div class="fw-semibold" id="detailVoucherDate">—</div>
                            </div>
                            <div class="col-sm-6">
                                <div class="small text-muted">Type</div>
                                <div id="detailType">—</div>
                            </div>
                            <div class="col-sm-6">
                                <div class="small text-muted">Payment Method</div>
                                <div id="detailPayment">—</div>
                            </div>
                            <div class="col-sm-6">
                                <div class="small text-muted">Account Type</div>
                                <div id="detailAccountType">—</div>
                            </div>
                            <div class="col-sm-6">
                                <div class="small text-muted">Account</div>
                                <div id="detailAccountName">—</div>
                            </div>
                            <div class="col-sm-6">
                                <div class="small text-muted">Reference</div>
                                <div id="detailReference">—</div>
                            </div>
                            <div class="col-sm-6">
                                <div class="small text-muted">Amount</div>
                                <div class="fs-5 fw-bold" id="detailAmount">—</div>
                            </div>
                            <div class="col-12">
                                <div class="small text-muted">Notes</div>
                                <div id="detailNotes">—</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="#" class="btn btn-outline-primary" id="detailPrintBtn" target="_blank">Print Invoice</a>
                        <a href="#" class="btn btn-outline-primary" id="detailEditBtn">Edit</a>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    @endpush
@endsection

@section('scripts')
    <style>
        .voucher-row {
            cursor: pointer;
        }

        .voucher-row:hover {
            filter: brightness(0.97);
        }
    </style>
    <script>
        (function () {
            const voucherDetails = @json($voucherDetails);

            const accountOptions = {
                customer: @json($customers->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->values()),
                supplier: @json($suppliers->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])->values()),
                investor: @json($investors->map(fn ($i) => ['id' => $i->id, 'name' => $i->name])->values()),
                expense: @json($expenseAccounts->map(fn ($e) => ['id' => $e->id, 'name' => $e->name])->values()),
            };

            const typeSelect = document.getElementById('filter_account_type');
            const accountSelect = document.getElementById('filter_account_id');
            const selectedType = @json($accountTypeFilter);
            const selectedId = @json($accountIdFilter ? (string) $accountIdFilter : '');

            function rebuildAccountFilter() {
                const type = typeSelect.value;
                const list = accountOptions[type] || [];

                accountSelect.innerHTML = '<option value="">All accounts</option>';
                accountSelect.disabled = !type || type === 'other';

                list.forEach(function (item) {
                    const opt = document.createElement('option');
                    opt.value = item.id;
                    opt.textContent = item.name;
                    if (String(item.id) === String(selectedId)) {
                        opt.selected = true;
                    }
                    accountSelect.appendChild(opt);
                });
            }

            typeSelect.addEventListener('change', function () {
                accountSelect.value = '';
                rebuildAccountFilter();
            });

            rebuildAccountFilter();

            const detailModalEl = document.getElementById('voucherDetailModal');
            const detailModal = detailModalEl ? new bootstrap.Modal(detailModalEl) : null;

            function showVoucherDetail(voucherId) {
                const detail = voucherDetails[voucherId];
                if (!detail || !detailModal) {
                    return;
                }

                const isReceive = detail.type === 'receive';
                const header = document.getElementById('voucherDetailModalHeader');
                const badge = document.getElementById('voucherDetailTypeBadge');

                header.style.backgroundColor = isReceive ? '#d1e7dd' : '#f8d7da';
                header.style.borderBottom = '1px solid ' + (isReceive ? '#badbcc' : '#f5c2c7');

                badge.textContent = detail.type_label;
                badge.className = 'badge ' + (isReceive ? 'bg-success' : 'bg-danger');

                document.getElementById('detailVoucherNo').textContent = detail.voucher_no;
                document.getElementById('detailVoucherDate').textContent = detail.voucher_date;
                document.getElementById('detailType').textContent = detail.type_label;
                document.getElementById('detailPayment').textContent = detail.payment_label;
                document.getElementById('detailAccountType').textContent = detail.account_type;
                document.getElementById('detailAccountName').textContent = detail.account_name;
                document.getElementById('detailReference').textContent = detail.reference;
                document.getElementById('detailAmount').textContent = detail.amount;
                document.getElementById('detailAmount').className = 'fs-5 fw-bold ' + (isReceive ? 'text-success' : 'text-danger');
                document.getElementById('detailNotes').textContent = detail.notes;

                document.getElementById('detailPrintBtn').href = detail.print_url;
                document.getElementById('detailEditBtn').href = detail.edit_url;

                detailModal.show();
            }

            document.querySelectorAll('.voucher-row').forEach(function (row) {
                row.addEventListener('click', function (event) {
                    if (event.target.closest('.voucher-row-actions, a, button, form')) {
                        return;
                    }

                    showVoucherDetail(row.dataset.voucherId);
                });

                row.addEventListener('keydown', function (event) {
                    if (event.key !== 'Enter' && event.key !== ' ') {
                        return;
                    }

                    event.preventDefault();
                    showVoucherDetail(row.dataset.voucherId);
                });
            });
        })();
    </script>
@endsection

