@extends('template.layout')
@section('title', 'Suppliers')

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h4 mb-0">Suppliers</h1>
                <p class="mb-0">Manage suppliers.</p>
            </div>
            <div>
                <a href="{{ route('suppliers.create') }}" class="btn btn-sm btn-gray-800">Add Supplier</a>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive py-4">
                <table class="table table-flush" data-datatable="true">
                    <thead class="thead-light">
                        <tr>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th class="text-end">Opening Balance</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($suppliers as $supplier)
                            <tr class="supplier-row" data-href="{{ route('suppliers.show', $supplier) }}" tabindex="0">
                                <td class="text-gray-900">
                                    <a href="{{ route('suppliers.show', $supplier) }}" class="supplier-open">{{ $supplier->name }}</a>
                                </td>
                                <td class="text-gray-900">{{ $supplier->phone }}</td>
                                <td class="text-gray-900">{{ $supplier->email }}</td>
                                <td class="text-gray-900 text-end">{{ number_format((float) $supplier->opening_balance, 2) }}</td>
                                <td class="text-gray-900">{{ $supplier->is_active ? 'Active' : 'Inactive' }}</td>
                                <td class="text-end supplier-row-actions">
                                    <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <form action="{{ route('suppliers.destroy', $supplier) }}" method="post" class="d-inline">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this supplier?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-gray-600">No suppliers yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <style>
        .supplier-row { cursor: pointer; }
        .supplier-row:hover { filter: brightness(0.97); }
        .supplier-open { font-weight: 600; text-decoration: none; }
    </style>
    <script>
        document.addEventListener('click', function (event) {
            const row = event.target.closest('.supplier-row');
            if (!row) return;
            if (event.target.closest('.supplier-row-actions, a, button, form')) return;
            window.location.href = row.dataset.href;
        });
    </script>
@endpush

