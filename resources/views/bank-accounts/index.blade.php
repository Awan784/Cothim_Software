@extends('template.layout')
@section('title', 'Bank Accounts')

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h4 mb-0">Bank Accounts</h1>
                <p class="mb-0">Shop cash and bank accounts.</p>
            </div>
            <div>
                <a href="{{ route('bank-accounts.create') }}" class="btn btn-sm btn-gray-800">Add Bank Account</a>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive py-4">
                <table class="table table-flush" data-datatable="true">
                    <thead class="thead-light">
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Bank</th>
                            <th>Account No.</th>
                            <th class="text-end">Opening Balance</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bankAccounts as $acc)
                            <tr>
                                <td class="text-gray-900">
                                    <a href="{{ route('bank-accounts.show', $acc) }}">{{ $acc->name }}</a>
                                </td>
                                <td class="text-gray-900">{{ $acc->is_cash ? 'Shop cash' : 'Bank' }}</td>
                                <td class="text-gray-900">{{ $acc->bank_name ?: '—' }}</td>
                                <td class="text-gray-900">{{ $acc->account_number ?: '—' }}</td>
                                <td class="text-gray-900 text-end">{{ number_format((float) $acc->current_balance, 2) }}</td>
                                <td class="text-gray-900">{{ $acc->is_active ? 'Active' : 'Inactive' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('bank-accounts.edit', $acc) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    @unless($acc->is_cash)
                                        <form action="{{ route('bank-accounts.destroy', $acc) }}" method="post" class="d-inline">
                                            @csrf
                                            @method('delete')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this bank account?')">Delete</button>
                                        </form>
                                    @endunless
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-gray-600">No bank accounts yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

