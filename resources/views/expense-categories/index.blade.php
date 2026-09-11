@extends('template.layout')
@section('title', 'Expense Categories')

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h4 mb-0">Expense Categories</h1>
                <p class="mb-0">Manage expense categories.</p>
            </div>
            <div>
                <a href="{{ route('expense-categories.create') }}" class="btn btn-sm btn-gray-800">Add Category</a>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive py-4">
                <table class="table table-flush">
                    <thead class="thead-light">
                        <tr>
                            <th>Name</th>
                            <th>Nominal A/C</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($expenseCategories as $cat)
                            <tr>
                                <td class="text-gray-900">{{ $cat->name }}</td>
                                <td class="text-gray-900">{{ $cat->nominalAccount?->name }}</td>
                                <td class="text-gray-900">{{ $cat->is_active ? 'Active' : 'Inactive' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('expense-categories.edit', $cat) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <form action="{{ route('expense-categories.destroy', $cat) }}" method="post" class="d-inline">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this category?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-gray-600">No categories yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

