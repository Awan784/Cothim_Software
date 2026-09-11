@extends('template.layout')
@section('title', 'Expenses')

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h4 mb-0">Expenses</h1>
                <p class="mb-0">Expense accounts (e.g. Utility, Rent). Record spending via Cash Vouchers.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('cash-vouchers.create', ['account_type' => 'expense']) }}" class="btn btn-sm btn-outline-primary">Pay Expense</a>
                <a href="{{ route('expense-accounts.create') }}" class="btn btn-sm btn-gray-800">Add Expense</a>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive py-4">
                <table class="table table-flush" data-datatable="true">
                    <thead class="thead-light">
                        <tr>
                            <th>Name</th>
                            <th>Description</th>
                            <th class="text-end">Total Spent</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($expenseAccounts as $exp)
                            <tr>
                                <td class="text-gray-900">
                                    <a href="{{ route('expense-accounts.show', $exp) }}">{{ $exp->name }}</a>
                                </td>
                                <td class="text-gray-900">{{ $exp->description }}</td>
                                <td class="text-gray-900 text-end">{{ number_format((float) $exp->total_spent, 2) }}</td>
                                <td class="text-gray-900">{{ $exp->is_active ? 'Active' : 'Inactive' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('expense-accounts.edit', $exp) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <form action="{{ route('expense-accounts.destroy', $exp) }}" method="post" class="d-inline">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this expense account?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-gray-600">No expense accounts yet. Add Utility, Rent, etc.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
