@extends('template.layout')
@section('title', 'Bank Account Ledger')

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h4 mb-1">{{ $bankAccount->name }}</h1>
                <p class="mb-0">
                    Current Balance:
                    <strong>{{ number_format((float) $bankAccount->current_balance, 2) }}</strong>
                </p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('bank-accounts.edit', $bankAccount) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                <a href="{{ route('bank-accounts.index') }}" class="btn btn-sm btn-secondary">Back</a>
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
                            <th>Account</th>
                            <th>Reference</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cashVouchers as $v)
                            <tr @class([
                                'ledger-row-receive' => $v->type === 'receive',
                                'ledger-row-payment' => $v->type === 'payment',
                            ])>
                                <td class="text-gray-900">{{ ams_date($v->voucher_date) }}</td>
                                <td class="text-gray-900">{{ $v->voucher_no }}</td>
                                <td class="text-gray-900 text-uppercase">{{ $v->type }}</td>
                                <td class="text-gray-900">
                                    {{ ucfirst($v->account_type) }}
                                    @if($v->account_display_name)
                                        - {{ $v->account_display_name }}
                                    @endif
                                </td>
                                <td class="text-gray-900">{{ $v->reference }}</td>
                                <td class="text-gray-900 text-end {{ $v->type === 'payment' ? 'text-danger' : 'text-success' }}">
                                    {{ number_format((float) $v->amount, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-gray-600">No ledger entries yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
