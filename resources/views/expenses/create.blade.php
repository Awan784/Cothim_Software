@extends('template.layout')
@section('title', 'Create Expense')

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h4 mb-0">Create Expense</h1>
                <p class="mb-0">Record a new expense.</p>
            </div>
            <div>
                <a href="{{ route('expenses.index') }}" class="btn btn-sm btn-secondary">Back</a>
            </div>
        </div>

        <div class="card p-4">
            <form method="post" action="{{ route('expenses.store') }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label">Expense Category</label>
                    <select name="expense_category_id" class="form-select @error('expense_category_id') is-invalid @enderror" required>
                        <option value="">Select category</option>
                        @foreach($expenseCategories as $cat)
                            <option value="{{ $cat->id }}" {{ old('expense_category_id') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('expense_category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Amount</label>
                        <input name="amount" value="{{ old('amount') }}" class="form-control @error('amount') is-invalid @enderror" required>
                        @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Expense Date</label>
                        <x-ams-date-input name="expense_date" :value="now()" required />
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Reference</label>
                        <input name="reference" value="{{ old('reference') }}" class="form-control @error('reference') is-invalid @enderror">
                        @error('reference') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Supplier (optional)</label>
                        <select name="supplier_id" class="form-select @error('supplier_id') is-invalid @enderror">
                            <option value="">Select supplier</option>
                            @foreach($suppliers as $s)
                                <option value="{{ $s->id }}" {{ old('supplier_id') == $s->id ? 'selected' : '' }}>
                                    {{ $s->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('supplier_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Bank Account (optional)</label>
                        <select name="bank_account_id" class="form-select @error('bank_account_id') is-invalid @enderror">
                            <option value="">Select bank account</option>
                            @foreach($bankAccounts as $b)
                                <option value="{{ $b->id }}" {{ old('bank_account_id') == $b->id ? 'selected' : '' }}>
                                    {{ $b->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('bank_account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div id="bank-balance-hint" class="mt-1 small text-danger d-none"></div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="3">{{ old('notes') }}</textarea>
                    @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <button class="btn btn-gray-800" type="submit">Save</button>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        (function () {
            const select = document.querySelector('select[name="bank_account_id"]');
            const hint = document.getElementById('bank-balance-hint');

            async function updateHint() {
                const id = select.value;
                if (!id) {
                    hint.classList.add('d-none');
                    hint.textContent = '';
                    return;
                }

                try {
                    const res = await fetch(`{{ url('/bank-accounts') }}/${id}/balance`, {
                        headers: { 'Accept': 'application/json' }
                    });
                    if (!res.ok) throw new Error('failed');
                    const data = await res.json();
                    const bal = Number(data.current_balance || 0).toFixed(2);
                    hint.textContent = `Current Balance: ${bal}`;
                    hint.classList.remove('d-none');
                } catch (e) {
                    hint.textContent = '';
                    hint.classList.add('d-none');
                }
            }

            select.addEventListener('change', updateHint);
            updateHint();
        })();
    </script>
@endsection

