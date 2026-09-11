@php
    $isEdit = isset($journalVoucher);
    $oldLines = old('lines');
    $initialLines = is_array($oldLines)
        ? $oldLines
        : ($isEdit ? $journalVoucher->lines->map(fn ($l) => [
            'account_type' => $l->account_type,
            'account_id' => $l->account_id,
            'account_name' => $l->accountDisplayName(),
            'account_no' => $l->account_id,
            'debit' => (float) $l->debit,
            'credit' => (float) $l->credit,
            'line_note' => $l->line_note,
        ])->values()->toArray() : []);

    $voucherNo = $isEdit ? $journalVoucher->voucher_no : ($previewVoucherNo ?? '');
@endphp

<div class="text-center mb-4">
    <h2 class="h5 mb-0 text-gray-800">General Journal</h2>
</div>

<div class="journal-entry-form mb-3">
    <div class="row g-3">
        <div class="col-lg-6">
            <div class="row align-items-center mb-2">
                <label class="col-sm-4 col-form-label col-form-label-sm">Type of Account</label>
                <div class="col-sm-8">
                    <select id="entry_account_type" class="form-select form-select-sm">
                        @foreach(['supplier' => 'Suppliers', 'customer' => 'Customers', 'expense' => 'Expenses', 'bank' => 'Banks', 'cash' => 'Cash'] as $k => $label)
                            <option value="{{ $k }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="row align-items-center mb-2">
                <label class="col-sm-4 col-form-label col-form-label-sm">Account</label>
                <div class="col-sm-8">
                    <select id="entry_account_id" class="form-select form-select-sm">
                        <option value="">---Select Account---</option>
                    </select>
                </div>
            </div>
            <div class="row align-items-center mb-2">
                <label class="col-sm-4 col-form-label col-form-label-sm">Debit</label>
                <div class="col-sm-8">
                    <input type="text" id="entry_debit" class="form-control form-control-sm ams-amount-input text-end" placeholder="0.00">
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="row align-items-center mb-2">
                <label class="col-sm-4 col-form-label col-form-label-sm">Voucher No</label>
                <div class="col-sm-8">
                    <input type="text" class="form-control form-control-sm bg-light" value="{{ $voucherNo }}" readonly>
                </div>
            </div>
            <div class="row align-items-center mb-2">
                <label class="col-sm-4 col-form-label col-form-label-sm">Date</label>
                <div class="col-sm-8">
                    <x-ams-date-input
                        name="voucher_date"
                        id="voucher_date"
                        class="form-control form-control-sm"
                        :value="$isEdit ? $journalVoucher->voucher_date : now()"
                        required
                    />
                </div>
            </div>
            <div class="row align-items-center mb-2">
                <label class="col-sm-4 col-form-label col-form-label-sm">Narration</label>
                <div class="col-sm-8">
                    <input type="text" id="entry_narration" class="form-control form-control-sm" placeholder="">
                </div>
            </div>
            <div class="row align-items-center mb-2">
                <label class="col-sm-4 col-form-label col-form-label-sm">Credit</label>
                <div class="col-sm-8">
                    <input type="text" id="entry_credit" class="form-control form-control-sm ams-amount-input text-end" placeholder="0.00">
                </div>
            </div>
        </div>
    </div>

    <div class="text-end mt-2">
        <button type="button" class="btn btn-sm btn-success" id="addLineBtn">Add</button>
    </div>
</div>

<div class="table-responsive mb-3">
    <table class="table table-bordered mb-0 journal-lines-table">
        <thead>
            <tr>
                <th>Ac/NO</th>
                <th>Account Name</th>
                <th>Narration</th>
                <th class="text-end">Debit</th>
                <th class="text-end">Credit</th>
                <th class="text-center" style="width: 80px;">Action</th>
            </tr>
        </thead>
        <tbody id="linesTableBody">
        </tbody>
        <tfoot>
            <tr class="table-light">
                <td colspan="3" class="text-end"><strong>Total</strong></td>
                <td class="text-end"><strong id="footerTotalDebit">0.00</strong></td>
                <td class="text-end"><strong id="footerTotalCredit">0.00</strong></td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</div>

<div id="linesHiddenInputs"></div>

<div class="d-flex justify-content-between align-items-center">
    <span id="balanceStatus" class="small text-muted"></span>
    <button type="submit" class="btn btn-info text-white px-4" id="saveJournalBtn">Save</button>
</div>

@push('page_scripts')
    <style>
        .journal-lines-table thead tr {
            background-color: #17a2b8;
            color: #fff;
        }
        .journal-lines-table thead th {
            border-color: #17a2b8;
            font-weight: 600;
        }
    </style>
    <script>
        (function () {
            const form = document.querySelector('form[action*="journal-vouchers"]');
            if (!form || form.dataset.journalFormInit === '1') {
                return;
            }
            form.dataset.journalFormInit = '1';

            const accountOptions = {
                customer: @json($customers->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'code' => (string) $c->id])->values()),
                supplier: @json($suppliers->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'code' => (string) $s->id])->values()),
                investor: @json($investors->map(fn ($i) => ['id' => $i->id, 'name' => $i->name, 'code' => (string) $i->id])->values()),
                expense: @json($expenseAccounts->map(fn ($e) => ['id' => $e->id, 'name' => $e->name, 'code' => (string) $e->id])->values()),
                bank: @json($bankAccounts->map(fn ($b) => ['id' => $b->id, 'name' => $b->name, 'code' => (string) $b->id])->values()),
                cash: @json($cashAccounts->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'code' => (string) $c->id])->values()),
                nominal: @json($nominalAccounts->map(fn ($n) => ['id' => $n->id, 'name' => $n->name, 'code' => $n->code ?? (string) $n->id])->values()),
            };

            const typeLabels = {
                customer: 'Customers',
                supplier: 'Suppliers',
                investor: 'Investors',
                expense: 'Expenses',
                bank: 'Banks',
                cash: 'Cash',
                nominal: 'Nominal A/C',
            };

            let lines = @json($initialLines).map(function (line) {
                if (line.account_name) {
                    return line;
                }
                const list = accountOptions[line.account_type] || [];
                const found = list.find(function (i) {
                    return String(i.id) === String(line.account_id);
                });
                return {
                    account_type: line.account_type,
                    account_id: line.account_id,
                    account_name: found ? found.name : '',
                    account_no: found ? (found.code || found.id) : line.account_id,
                    debit: parseFloat(line.debit || 0),
                    credit: parseFloat(line.credit || 0),
                    line_note: line.line_note || '',
                };
            });

            const entryType = document.getElementById('entry_account_type');
            const entryAccount = document.getElementById('entry_account_id');
            const entryDebit = document.getElementById('entry_debit');
            const entryCredit = document.getElementById('entry_credit');
            const entryNarration = document.getElementById('entry_narration');
            const linesTableBody = document.getElementById('linesTableBody');
            const linesHiddenInputs = document.getElementById('linesHiddenInputs');
            const footerTotalDebit = document.getElementById('footerTotalDebit');
            const footerTotalCredit = document.getElementById('footerTotalCredit');
            const balanceStatus = document.getElementById('balanceStatus');

            function parseAmount(value) {
                const cleaned = String(value ?? '').replace(/,/g, '').trim();
                const amount = parseFloat(cleaned);
                return Number.isFinite(amount) ? amount : 0;
            }

            function resetSubmitButtons() {
                form.querySelectorAll('button[type="submit"]').forEach(function (btn) {
                    btn.classList.remove('is-loading');
                    btn.disabled = false;
                });
            }

            function rebuildEntryAccounts(selectedId) {
                const list = accountOptions[entryType.value] || [];
                entryAccount.innerHTML = '<option value="">---Select Account---</option>';
                list.forEach(function (item) {
                    const opt = document.createElement('option');
                    opt.value = item.id;
                    opt.textContent = item.name;
                    opt.dataset.code = item.code || item.id;
                    opt.dataset.name = item.name;
                    if (String(item.id) === String(selectedId)) {
                        opt.selected = true;
                    }
                    entryAccount.appendChild(opt);
                });
            }

            function formatAmount(n) {
                return Number(n || 0).toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                });
            }

            function recalcTotals() {
                let debit = 0;
                let credit = 0;
                lines.forEach(function (line) {
                    debit += parseFloat(line.debit || 0);
                    credit += parseFloat(line.credit || 0);
                });
                footerTotalDebit.textContent = formatAmount(debit);
                footerTotalCredit.textContent = formatAmount(credit);

                const diff = Math.abs(debit - credit);
                if (lines.length < 2) {
                    balanceStatus.textContent = 'Add at least 2 lines.';
                    balanceStatus.className = 'small text-warning';
                } else if (diff < 0.01 && debit > 0) {
                    balanceStatus.textContent = 'Totals are balanced.';
                    balanceStatus.className = 'small text-success';
                } else {
                    balanceStatus.textContent = 'Difference: ' + formatAmount(diff);
                    balanceStatus.className = 'small text-danger';
                }
            }

            function syncHiddenInputs() {
                linesHiddenInputs.innerHTML = '';
                lines.forEach(function (line, idx) {
                    const fields = {
                        account_type: line.account_type,
                        account_id: line.account_id,
                        debit: line.debit,
                        credit: line.credit,
                        line_note: line.line_note || '',
                    };
                    Object.keys(fields).forEach(function (key) {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'lines[' + idx + '][' + key + ']';
                        input.value = fields[key];
                        linesHiddenInputs.appendChild(input);
                    });
                });
            }

            function renderTable() {
                linesTableBody.innerHTML = '';
                lines.forEach(function (line, idx) {
                    const tr = document.createElement('tr');
                    tr.innerHTML =
                        '<td class="text-gray-900">' + (line.account_no || line.account_id) + '</td>' +
                        '<td class="text-gray-900">' + (line.account_name || '') + '</td>' +
                        '<td class="text-gray-900">' + (line.line_note || '') + '</td>' +
                        '<td class="text-gray-900 text-end">' + (line.debit > 0 ? formatAmount(line.debit) : '') + '</td>' +
                        '<td class="text-gray-900 text-end">' + (line.credit > 0 ? formatAmount(line.credit) : '') + '</td>' +
                        '<td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger" data-remove="' + idx + '">×</button></td>';
                    linesTableBody.appendChild(tr);
                });

                linesTableBody.querySelectorAll('[data-remove]').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        const i = parseInt(btn.getAttribute('data-remove'), 10);
                        lines.splice(i, 1);
                        renderTable();
                        syncHiddenInputs();
                        recalcTotals();
                    });
                });

                syncHiddenInputs();
                recalcTotals();
            }

            document.getElementById('addLineBtn').addEventListener('click', function () {
                const accountId = entryAccount.value;
                if (!accountId) {
                    alert('Please select an account.');
                    return;
                }

                const debit = parseAmount(entryDebit.value);
                const credit = parseAmount(entryCredit.value);

                if ((debit > 0 && credit > 0) || (debit <= 0 && credit <= 0)) {
                    alert('Enter either a debit or a credit amount.');
                    return;
                }

                const selected = entryAccount.options[entryAccount.selectedIndex];

                lines.push({
                    account_type: entryType.value,
                    account_id: accountId,
                    account_name: selected.dataset.name || selected.textContent,
                    account_no: selected.dataset.code || accountId,
                    debit: debit > 0 ? debit : 0,
                    credit: credit > 0 ? credit : 0,
                    line_note: entryNarration.value.trim(),
                });

                entryDebit.value = '';
                entryCredit.value = '';
                entryNarration.value = '';

                renderTable();
            });

            entryType.addEventListener('change', function () {
                rebuildEntryAccounts();
            });

            entryDebit.addEventListener('input', function () {
                if (parseAmount(entryDebit.value) > 0) {
                    entryCredit.value = '';
                }
            });

            entryCredit.addEventListener('input', function () {
                if (parseAmount(entryCredit.value) > 0) {
                    entryDebit.value = '';
                }
            });

            form.addEventListener('submit', function (e) {
                syncHiddenInputs();
                if (lines.length < 2) {
                    e.preventDefault();
                    resetSubmitButtons();
                    alert('Please add at least 2 journal lines.');
                    return;
                }
                let debit = 0;
                let credit = 0;
                lines.forEach(function (line) {
                    debit += parseAmount(line.debit);
                    credit += parseAmount(line.credit);
                });
                if (Math.abs(debit - credit) > 0.009) {
                    e.preventDefault();
                    resetSubmitButtons();
                    alert('Total debit must equal total credit.');
                    balanceStatus.textContent = 'Total debit must equal total credit before saving.';
                    balanceStatus.className = 'small text-danger fw-semibold';
                    return;
                }

                balanceStatus.textContent = 'Totals are balanced.';
                balanceStatus.className = 'small text-success';
            });

            rebuildEntryAccounts();
            renderTable();
        })();
    </script>
@endpush
