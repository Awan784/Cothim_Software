@php
    $isEdit = isset($cashVoucher);
    $showTypeField = $showTypeField ?? true;
    $selectedAccountId = old('account_id', $isEdit ? $cashVoucher->account_id : request('account_id', ''));
    $selectedAccountType = old('account_type', $isEdit ? $cashVoucher->account_type : request('account_type', 'customer'));
    if (! $isEdit && ! $showTypeField) {
        $defaultVoucherType = old('type', request('type', ''));
    } else {
        $defaultVoucherType = old('type', $isEdit ? $cashVoucher->type : request('type', request('account_type') === 'expense' ? 'payment' : 'receive'));
    }
    $selectedPaymentMethod = old('payment_method', $isEdit ? ($cashVoucher->payment_method ?? 'cash') : 'cash');
    $selectedBankAccountId = old('bank_account_id', $isEdit ? ($cashVoucher->bank_account_id ?? '') : '');
@endphp

<div class="row">
    @if ($showTypeField)
        <div class="col-md-4 mb-3">
            <label class="form-label">Type</label>
            <select name="type" class="form-select @error('type') is-invalid @enderror" id="voucher_type" required>
                @foreach(['receive' => 'Cash Receive', 'payment' => 'Cash Payment'] as $k => $label)
                    <option value="{{ $k }}" {{ $defaultVoucherType === $k ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    @else
        <input type="hidden" name="type" id="voucher_type" value="{{ $defaultVoucherType }}">
    @endif

    <div @class(['col-md-4 mb-3' => $showTypeField, 'col-md-6 mb-3' => ! $showTypeField])>
        <label class="form-label">Payment Method</label>
        <select name="payment_method" class="form-select @error('payment_method') is-invalid @enderror" id="payment_method" required>
            <option value="cash" {{ $selectedPaymentMethod === 'cash' ? 'selected' : '' }}>Cash</option>
            <option value="bank" {{ $selectedPaymentMethod === 'bank' ? 'selected' : '' }}>Bank Account</option>
        </select>
        @error('payment_method') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div @class(['col-md-4 mb-3' => $showTypeField, 'col-md-6 mb-3' => ! $showTypeField])>
        <label class="form-label">Voucher Date</label>
        <x-ams-date-input
            name="voucher_date"
            :value="$isEdit ? $cashVoucher->voucher_date : now()"
            required
        />
    </div>
</div>

<div class="row" id="cash_info_row">
    <div class="col-md-4 mb-3">
        <label class="form-label">Cash</label>
        <div class="form-control bg-light">
            Current Cash Balance: <strong class="text-danger">{{ number_format((float) ($cashBalance ?? 0), 2) }}</strong>
        </div>
    </div>
</div>

<div class="row d-none" id="bank_row">
    <div class="col-md-8 mb-3">
        <label class="form-label">Bank Account</label>
        <select name="bank_account_id" class="form-select @error('bank_account_id') is-invalid @enderror" id="bank_account_id">
            <option value="">Select bank account</option>
            @foreach($bankAccounts ?? [] as $b)
                <option value="{{ $b->id }}" {{ (string) $selectedBankAccountId === (string) $b->id ? 'selected' : '' }}>
                    {{ $b->name }}
                </option>
            @endforeach
        </select>
        @error('bank_account_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        <div class="form-text" id="bank_balance_text"></div>
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label">Account Type</label>
        <select name="account_type" class="form-select @error('account_type') is-invalid @enderror" required id="account_type">
            @foreach(['customer' => 'Customer', 'supplier' => 'Supplier', 'expense' => 'Expense', 'other' => 'Other'] as $k => $label)
                <option value="{{ $k }}" {{ $selectedAccountType === $k ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        @error('account_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-8 mb-3" id="account_select_wrap">
        <label class="form-label">Account</label>
        <select name="account_id" class="form-select @error('account_id') is-invalid @enderror" id="account_id" required>
            <option value="">Select account</option>
        </select>
        @error('account_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
    </div>
</div>

<div class="mb-3 d-none" id="other_name_wrap">
    <label class="form-label">Other Name</label>
    <input name="other_name" value="{{ old('other_name', $isEdit ? $cashVoucher->other_name : '') }}" class="form-control @error('other_name') is-invalid @enderror">
    @error('other_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label">Amount</label>
        <input
            type="text"
            name="amount"
            value="{{ old('amount', $isEdit ? $cashVoucher->amount : '') }}"
            class="form-control ams-amount-input text-end @error('amount') is-invalid @enderror"
            placeholder="0.00"
            required
        >
        @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Reference</label>
        <input name="reference" value="{{ old('reference', $isEdit ? $cashVoucher->reference : '') }}" class="form-control @error('reference') is-invalid @enderror">
        @error('reference') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Notes</label>
        <input name="notes" value="{{ old('notes', $isEdit ? $cashVoucher->notes : '') }}" class="form-control @error('notes') is-invalid @enderror">
        @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

@push('page_scripts')
    <script>
        (function () {
            const paymentMethodSelect = document.getElementById('payment_method');
            const cashInfoRow = document.getElementById('cash_info_row');
            const bankRow = document.getElementById('bank_row');
            const bankSelect = document.getElementById('bank_account_id');
            const bankBalanceText = document.getElementById('bank_balance_text');

            const accountOptions = {
                customer: @json($customers->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->values()),
                supplier: @json($suppliers->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])->values()),
                expense: @json(($expenseAccounts ?? collect())->map(fn ($e) => ['id' => $e->id, 'name' => $e->name])->values()),
            };

            const typeSelect = document.getElementById('account_type');
            const otherWrap = document.getElementById('other_name_wrap');
            const accountWrap = document.getElementById('account_select_wrap');
            const accountSelect = document.getElementById('account_id');
            const selectedId = @json((string) $selectedAccountId);
            const selectedType = @json($selectedAccountType);

            function toggleSource() {
                const method = paymentMethodSelect.value;
                const isBank = method === 'bank';

                cashInfoRow.classList.toggle('d-none', isBank);
                bankRow.classList.toggle('d-none', !isBank);
                bankSelect.required = isBank;
            }

            async function loadBankBalance() {
                const id = bankSelect.value;
                if (!id) {
                    bankBalanceText.textContent = '';
                    return;
                }
                try {
                    const res = await fetch(`{{ url('bank-accounts') }}/${id}/balance`);
                    const json = await res.json();
                    bankBalanceText.textContent = `Current Balance: ${Number(json.current_balance || 0).toFixed(2)}`;
                } catch (e) {
                    bankBalanceText.textContent = '';
                }
            }

            function rebuildAccountSelect() {
                const t = typeSelect.value;
                const list = accountOptions[t] || [];
                const labels = { customer: 'customer', supplier: 'supplier', expense: 'expense' };

                accountSelect.innerHTML = '<option value="">Select ' + (labels[t] || 'account') + '</option>';
                list.forEach(function (item) {
                    const opt = document.createElement('option');
                    opt.value = item.id;
                    opt.textContent = item.name;
                    if (String(item.id) === String(selectedId)) {
                        opt.selected = true;
                    }
                    accountSelect.appendChild(opt);
                });

                accountSelect.required = t !== 'other';
            }

            function toggleAccountInputs() {
                const t = typeSelect.value;
                const isOther = t === 'other';

                otherWrap.classList.toggle('d-none', !isOther);
                accountWrap.classList.toggle('d-none', isOther);
                accountSelect.required = !isOther;

                if (!isOther) {
                    rebuildAccountSelect();
                }
            }

            typeSelect.addEventListener('change', function () {
                accountSelect.value = '';
                toggleAccountInputs();
            });

            paymentMethodSelect.addEventListener('change', function () {
                toggleSource();
                loadBankBalance();
            });

            bankSelect?.addEventListener('change', loadBankBalance);

            toggleAccountInputs();
            toggleSource();
            loadBankBalance();
        })();
    </script>
@endpush
