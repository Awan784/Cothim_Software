@extends('template.layout')
@section('title', 'Expenses')

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h4 mb-0">Expenses</h1>
                <p class="mb-0">Manage expenses.</p>
            </div>
            <div>
                <a href="{{ route('expenses.create') }}" class="btn btn-sm btn-gray-800">Add Expense</a>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive py-4">
                <table class="table table-flush" data-datatable="true">
                    <thead class="thead-light">
                        <tr>
                            <th>Date</th>
                            <th>Category</th>
                            <th>Supplier</th>
                            <th>Bank Account</th>
                            <th class="text-end">Amount</th>
                            <th>Reference</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($expenses as $e)
                            <tr>
                                <td class="text-gray-900">{{ ams_date($e->expense_date) }}</td>
                                <td class="text-gray-900">{{ $e->expenseCategory?->name }}</td>
                                <td class="text-gray-900">{{ $e->supplier?->name }}</td>
                                <td class="text-gray-900">{{ $e->bankAccount?->name }}</td>
                                <td class="text-gray-900 text-end">{{ number_format((float) $e->amount, 2) }}</td>
                                <td class="text-gray-900">{{ $e->reference }}</td>
                                <td class="text-end">
                                    <a href="{{ route('expenses.edit', $e) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <form action="{{ route('expenses.destroy', $e) }}" method="post" class="d-inline">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this expense?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-gray-600">No expenses yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

