@extends('template.layout')
@section('title', $inbox->title)

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h4 mb-0">{{ $inbox->title }}</h1>
                <p class="mb-0 text-muted">{{ ucfirst(str_replace('_', ' ', $inbox->type)) }} · {{ $inbox->status }}</p>
            </div>
            <a href="{{ route('inbox.index') }}" class="btn btn-sm btn-secondary">Back</a>
        </div>

        <div class="row g-3">
            <div class="col-lg-5">
                <div class="card p-3 mb-3">
                    <p class="mb-1"><strong>Amount:</strong> {{ $inbox->extracted_amount !== null ? number_format((float) $inbox->extracted_amount, 2) : '—' }}</p>
                    <p class="mb-1"><strong>Date:</strong> {{ $inbox->extracted_date ? ams_date($inbox->extracted_date) : '—' }}</p>
                    <p class="mb-0"><strong>Party:</strong> {{ $inbox->extracted_party ?: '—' }}</p>
                    @if($inbox->fileUrl())
                        <a class="btn btn-sm btn-outline-primary mt-3" href="{{ $inbox->fileUrl() }}" target="_blank">View file</a>
                    @endif
                </div>

                @if($inbox->isNew())
                    <div class="card p-4 mb-3">
                        <h2 class="h6">Post as expense</h2>
                        <form method="post" action="{{ route('inbox.post-expense', $inbox) }}">
                            @csrf
                            <div class="mb-2">
                                <label class="form-label">Expense account</label>
                                <select name="expense_account_id" class="form-select" required>
                                    @foreach($expenseAccounts as $account)
                                        <option value="{{ $account->id }}">{{ $account->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Amount</label>
                                <input name="amount" class="form-control" required value="{{ $inbox->extracted_amount ?? '' }}">
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Pay from</label>
                                <select name="payment_method" class="form-select" required>
                                    <option value="cash">Cash</option>
                                    <option value="bank">Bank</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Bank account</label>
                                <select name="bank_account_id" class="form-select">
                                    <option value="">—</option>
                                    @foreach($bankAccounts as $bank)
                                        <option value="{{ $bank->id }}">{{ $bank->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button class="btn btn-primary btn-sm" type="submit">Post expense</button>
                        </form>
                    </div>

                    <div class="card p-4 mb-3">
                        <h2 class="h6">Post as supplier bill (with tax)</h2>
                        <form method="post" action="{{ route('inbox.post-bill', $inbox) }}">
                            @csrf
                            <div class="mb-2">
                                <label class="form-label">Supplier</label>
                                <select name="supplier_id" class="form-select" required>
                                    <option value="">Select</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Gross amount (incl. tax)</label>
                                <input name="amount" class="form-control" required value="{{ $inbox->extracted_amount ?? '' }}">
                            </div>
                            <button class="btn btn-outline-primary btn-sm" type="submit">Create bill</button>
                        </form>
                    </div>

                    <form method="post" action="{{ route('inbox.reject', $inbox) }}">
                        @csrf
                        <button class="btn btn-outline-secondary btn-sm" type="submit">Reject</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
@endsection
