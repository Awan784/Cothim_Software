@extends('template.layout')
@section('title', 'Expense Ledger')

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h4 mb-1">{{ $expenseAccount->name }}</h1>
                <p class="mb-0">
                    Total Spent:
                    <strong>{{ number_format((float) $expenseAccount->total_spent, 2) }}</strong>
                </p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('cash-vouchers.create', ['account_type' => 'expense', 'account_id' => $expenseAccount->id, 'type' => 'payment']) }}" class="btn btn-sm btn-gray-800">Pay Expense</a>
                <a href="{{ route('expense-accounts.edit', $expenseAccount) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                <a href="{{ route('expense-accounts.index') }}" class="btn btn-sm btn-secondary">Back</a>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive py-4">
                <table class="table table-flush" data-datatable="true">
                    <thead class="thead-light">
                        <tr>
                            <th>Date</th>
                            <th>Voucher</th>
                            <th>Type</th>
                            <th>Payment</th>
                            <th>Reference</th>
                            <th>Notes</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vouchers as $v)
                            <tr @class([
                                'ledger-row-receive' => $v->type === 'receive',
                                'ledger-row-payment' => $v->type === 'payment',
                            ])>
                                <td class="text-gray-900">{{ ams_date($v->voucher_date) }}</td>
                                <td class="text-gray-900">
                                    <a href="{{ route('cash-vouchers.edit', $v) }}">{{ $v->voucher_no }}</a>
                                </td>
                                <td class="text-gray-900 text-uppercase">{{ $v->type }}</td>
                                <td class="text-gray-900 text-uppercase">{{ $v->payment_method ?? 'cash' }}</td>
                                <td class="text-gray-900">{{ $v->reference }}</td>
                                <td class="text-gray-900">{{ $v->notes }}</td>
                                <td class="text-gray-900 text-end text-danger">{{ number_format((float) $v->amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-gray-600">No payments recorded yet. Use Cash Vouchers.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
