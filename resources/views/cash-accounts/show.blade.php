@extends('template.layout')
@section('title', 'Cash Ledger')

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h4 mb-1">{{ $cashAccount->name }}</h1>
                <p class="mb-0">
                    Current Balance:
                    <strong>{{ number_format((float) $cashAccount->current_balance, 2) }}</strong>
                </p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('cash-vouchers.create') }}" class="btn btn-sm btn-gray-800">New Voucher</a>
                <a href="{{ route('cash-accounts.index') }}" class="btn btn-sm btn-secondary">Back</a>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive py-4">
                <table class="table table-flush">
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
                        @forelse($vouchers as $v)
                            <tr @class([
                                'ledger-row-receive' => $v->type === 'receive',
                                'ledger-row-payment' => $v->type === 'payment',
                            ])>
                                <td class="text-gray-900">{{ ams_date($v->voucher_date) }}</td>
                                <td class="text-gray-900">{{ $v->voucher_no }}</td>
                                <td class="text-gray-900 text-uppercase">{{ $v->type }}</td>
                                <td class="text-gray-900">
                                    {{ ucfirst($v->account_type) }}
                                    @if($v->account_type === 'other')
                                        - {{ $v->other_name }}
                                    @else
                                        - #{{ $v->account_id }}
                                    @endif
                                </td>
                                <td class="text-gray-900">{{ $v->reference }}</td>
                                <td class="text-gray-900 text-end {{ $v->type === 'payment' ? 'text-danger' : 'text-success' }}">
                                    {{ number_format((float) $v->amount, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-gray-600">No vouchers yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

