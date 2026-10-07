@extends('template.layout')
@section('title', 'New salesman settlement')

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h4 mb-1">New salesman settlement</h1>
                <p class="mb-0 text-muted">Cash from the salesman is recorded once. Tick invoices to post customer receive payments for verification. Leftover becomes advance.</p>
            </div>
            <a href="{{ route('salesman-settlements.index') }}" class="btn btn-sm btn-secondary">Back</a>
        </div>

        <form method="post" action="{{ route('salesman-settlements.store') }}" id="settlementForm">
            @csrf
            <div class="card p-4 mb-3">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="salesman_id">Salesman</label>
                        <select name="salesman_id" id="salesman_id" class="form-select" required>
                            <option value="">Select salesman</option>
                            @foreach($salesmen as $salesman)
                                <option value="{{ $salesman->id }}"
                                    data-advance="{{ number_format((float) $salesman->advance_balance, 2, '.', '') }}"
                                    @selected((string) old('salesman_id', $selectedSalesmanId) === (string) $salesman->id)>
                                    {{ $salesman->name }}{{ $salesman->citiesLabel() !== '' ? ' · '.$salesman->citiesLabel() : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date</label>
                        <x-ams-date-input name="settlement_date" :value="old('settlement_date', now())" required />
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="cash_received">Cash received</label>
                        <input type="number" min="0" step="0.01" name="cash_received" id="cash_received" class="form-control" value="{{ old('cash_received', '0') }}" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="payment_method">Via</label>
                        <select name="payment_method" id="payment_method" class="form-select">
                            <option value="cash" @selected(old('payment_method', 'cash') === 'cash')>Cash</option>
                            <option value="bank" @selected(old('payment_method') === 'bank')>Bank</option>
                        </select>
                    </div>
                    <div class="col-md-6 {{ old('payment_method') === 'bank' ? '' : 'd-none' }}" id="bank_row">
                        <label class="form-label" for="bank_account_id">Bank account</label>
                        <select name="bank_account_id" id="bank_account_id" class="form-select">
                            <option value="">Select bank</option>
                            @foreach($bankAccounts as $bank)
                                <option value="{{ $bank->id }}" @selected((string) old('bank_account_id') === (string) $bank->id)>{{ $bank->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="notes">Notes</label>
                        <input type="text" name="notes" id="notes" class="form-control" value="{{ old('notes') }}">
                    </div>
                </div>
            </div>

            <div class="card p-4 mb-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h2 class="h6 mb-0">Customer invoices to verify</h2>
                        <p class="small text-muted mb-0" id="invoiceHint">Select a salesman to load unpaid invoices.</p>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="allocateAllBtn">Allocate all dues</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-flush mb-0" id="invoiceTable">
                        <thead class="thead-light">
                            <tr>
                                <th></th>
                                <th>Invoice</th>
                                <th>Date</th>
                                <th>Customer</th>
                                <th>City</th>
                                <th class="text-end">Total</th>
                                <th class="text-end">Due</th>
                                <th class="text-end" style="width:8rem;">Receive</th>
                            </tr>
                        </thead>
                        <tbody id="invoiceBody">
                            <tr id="invoiceEmpty">
                                <td colspan="8" class="text-center text-muted">No unpaid invoices.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card p-4 mb-3">
                <div class="row text-center g-3">
                    <div class="col-md-3">
                        <div class="small text-muted">Opening advance</div>
                        <div class="fs-5 fw-semibold" id="sumOpening">0.00</div>
                    </div>
                    <div class="col-md-3">
                        <div class="small text-muted">Cash received</div>
                        <div class="fs-5 fw-semibold" id="sumCash">0.00</div>
                    </div>
                    <div class="col-md-3">
                        <div class="small text-muted">Allocated to customers</div>
                        <div class="fs-5 fw-semibold" id="sumAllocated">0.00</div>
                    </div>
                    <div class="col-md-3">
                        <div class="small text-muted">New advance</div>
                        <div class="fs-5 fw-semibold text-primary" id="sumAdvance">0.00</div>
                    </div>
                </div>
                <p class="small text-muted mb-0 mt-3" id="sumWarning"></p>
            </div>

            <button type="submit" class="btn btn-gray-800">Save settlement</button>
        </form>
    </div>
@endsection

@section('scripts')
    <script>
        (function () {
            const invoicesUrl = @json(route('salesman-settlements.invoices'));
            const salesmanSelect = document.getElementById('salesman_id');
            const cashInput = document.getElementById('cash_received');
            const methodSelect = document.getElementById('payment_method');
            const bankRow = document.getElementById('bank_row');
            const body = document.getElementById('invoiceBody');
            const empty = document.getElementById('invoiceEmpty');
            const hint = document.getElementById('invoiceHint');
            const oldAllocations = @json(old('allocations', []));

            function money(value) {
                return Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            function openingAdvance() {
                const option = salesmanSelect.options[salesmanSelect.selectedIndex];
                return option ? Number(option.dataset.advance || 0) : 0;
            }

            function allocatedTotal() {
                let total = 0;
                body.querySelectorAll('.alloc-check:checked').forEach(function (checkbox) {
                    const amount = Number(checkbox.closest('tr').querySelector('.alloc-amount').value || 0);
                    total += amount;
                });
                return Math.round(total * 100) / 100;
            }

            function refreshSummary() {
                const opening = openingAdvance();
                const cash = Number(cashInput.value || 0);
                const allocated = allocatedTotal();
                const available = Math.round((opening + cash) * 100) / 100;
                const advance = Math.round((available - allocated) * 100) / 100;
                document.getElementById('sumOpening').textContent = money(opening);
                document.getElementById('sumCash').textContent = money(cash);
                document.getElementById('sumAllocated').textContent = money(allocated);
                document.getElementById('sumAdvance').textContent = money(advance);
                const warning = document.getElementById('sumWarning');
                if (allocated - available > 0.009) {
                    warning.textContent = 'Allocated amount is more than opening advance plus cash received.';
                    warning.classList.add('text-danger');
                } else {
                    warning.textContent = 'New advance is deducted as LESS PREVIOUS ADVANCE on the next commission report.';
                    warning.classList.remove('text-danger');
                }
            }

            function setRowEnabled(row, enabled) {
                const amount = row.querySelector('.alloc-amount');
                const hidden = row.querySelector('.alloc-id');
                amount.disabled = !enabled;
                hidden.disabled = !enabled;
                amount.required = enabled;
                if (!enabled) {
                    amount.value = '';
                } else if (!amount.value) {
                    amount.value = amount.dataset.due;
                }
            }

            function renderInvoices(invoices) {
                body.querySelectorAll('tr[data-invoice-id]').forEach(function (row) { row.remove(); });
                if (!invoices.length) {
                    empty.classList.remove('d-none');
                    hint.textContent = 'No unpaid invoices for this salesman.';
                    refreshSummary();
                    return;
                }
                empty.classList.add('d-none');
                hint.textContent = 'Tick each customer bill the salesman collected. Each creates a receive voucher for that customer (cash is not counted again).';
                invoices.forEach(function (invoice, index) {
                    const row = document.createElement('tr');
                    row.dataset.invoiceId = invoice.id;
                    row.innerHTML =
                        '<td><input type="checkbox" class="form-check-input alloc-check"></td>' +
                        '<td class="fw-semibold">' + (invoice.invoice_no || ('#' + invoice.id)) + '</td>' +
                        '<td>' + invoice.invoice_date + '</td>' +
                        '<td>' + invoice.customer + '</td>' +
                        '<td>' + (invoice.city || '—') + '</td>' +
                        '<td class="text-end">' + money(invoice.total) + '</td>' +
                        '<td class="text-end">' + money(invoice.due) + '</td>' +
                        '<td><input type="hidden" name="allocations[' + index + '][invoice_id]" class="alloc-id" value="' + invoice.id + '" disabled>' +
                        '<input type="number" min="0.01" step="0.01" max="' + invoice.due + '" name="allocations[' + index + '][amount]" class="form-control form-control-sm text-end alloc-amount" data-due="' + invoice.due + '" disabled></td>';
                    body.appendChild(row);
                    const checkbox = row.querySelector('.alloc-check');
                    checkbox.addEventListener('change', function () {
                        setRowEnabled(row, checkbox.checked);
                        refreshSummary();
                    });
                    row.querySelector('.alloc-amount').addEventListener('input', refreshSummary);
                    const old = oldAllocations.find(function (item) { return String(item.invoice_id) === String(invoice.id); });
                    if (old) {
                        checkbox.checked = true;
                        setRowEnabled(row, true);
                        row.querySelector('.alloc-amount').value = old.amount;
                    }
                });
                refreshSummary();
            }

            function loadInvoices() {
                const id = salesmanSelect.value;
                if (!id) {
                    renderInvoices([]);
                    hint.textContent = 'Select a salesman to load unpaid invoices.';
                    return;
                }
                hint.textContent = 'Loading invoices…';
                fetch(invoicesUrl + '?salesman_id=' + encodeURIComponent(id), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                }).then(function (response) { return response.json(); }).then(function (payload) {
                    const option = salesmanSelect.options[salesmanSelect.selectedIndex];
                    if (option) {
                        option.dataset.advance = payload.opening_advance;
                    }
                    renderInvoices(payload.invoices || []);
                }).catch(function () {
                    hint.textContent = 'Could not load invoices.';
                });
            }

            salesmanSelect.addEventListener('change', loadInvoices);
            cashInput.addEventListener('input', refreshSummary);
            methodSelect.addEventListener('change', function () {
                bankRow.classList.toggle('d-none', methodSelect.value !== 'bank');
            });
            document.getElementById('allocateAllBtn').addEventListener('click', function () {
                let remaining = openingAdvance() + Number(cashInput.value || 0);
                body.querySelectorAll('tr[data-invoice-id]').forEach(function (row) {
                    const checkbox = row.querySelector('.alloc-check');
                    const amountInput = row.querySelector('.alloc-amount');
                    const due = Number(amountInput.dataset.due || 0);
                    if (remaining > 0.009) {
                        const amount = Math.min(due, remaining);
                        checkbox.checked = true;
                        setRowEnabled(row, true);
                        amountInput.value = amount.toFixed(2);
                        remaining = Math.round((remaining - amount) * 100) / 100;
                    } else {
                        checkbox.checked = false;
                        setRowEnabled(row, false);
                    }
                });
                refreshSummary();
            });
            document.getElementById('settlementForm').addEventListener('submit', function (event) {
                const available = openingAdvance() + Number(cashInput.value || 0);
                if (allocatedTotal() - available > 0.009) {
                    event.preventDefault();
                    alert('Allocated amount cannot exceed opening advance plus cash received.');
                }
            });

            if (salesmanSelect.value) {
                loadInvoices();
            } else {
                refreshSummary();
            }
        })();
    </script>
@endsection
