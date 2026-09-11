@extends('template.layout')
@section('title', 'Cash Accounts')

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h4 mb-0">Cash Accounts</h1>
                <p class="mb-0">Manage cash accounts (cash boxes).</p>
            </div>
            <div>
                <a href="{{ route('cash-accounts.create') }}" class="btn btn-sm btn-gray-800">Add Cash Account</a>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive py-4">
                <table class="table table-flush">
                    <thead class="thead-light">
                        <tr>
                            <th>Name</th>
                            <th class="text-end">Opening Balance</th>
                            <th class="text-end">Current Balance</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cashAccounts as $acc)
                            <tr>
                                <td class="text-gray-900">
                                    <a href="{{ route('cash-accounts.show', $acc) }}">{{ $acc->name }}</a>
                                </td>
                                <td class="text-gray-900 text-end">{{ number_format((float) $acc->opening_balance, 2) }}</td>
                                <td class="text-gray-900 text-end">{{ number_format((float) $acc->current_balance, 2) }}</td>
                                <td class="text-gray-900">{{ $acc->is_active ? 'Active' : 'Inactive' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('cash-accounts.edit', $acc) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <form action="{{ route('cash-accounts.destroy', $acc) }}" method="post" class="d-inline">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this cash account?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-gray-600">No cash accounts yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

