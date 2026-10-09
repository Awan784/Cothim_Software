@extends('template.layout')
@section('title', 'Vendors')

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h4 mb-0">Vendors</h1>
                <p class="mb-0">Raw material and other purchases that stay off inventory.</p>
            </div>
            <div>
                <a href="{{ route('vendors.create') }}" class="btn btn-sm btn-gray-800">Add Vendor</a>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive py-4">
                <table class="table table-flush" data-datatable="true">
                    <thead class="thead-light">
                        <tr>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>City</th>
                            <th class="text-end">Opening Balance</th>
                            <th class="text-end">Balance</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vendors as $vendor)
                            <tr class="vendor-row" data-href="{{ route('vendors.show', $vendor) }}" tabindex="0">
                                <td class="text-gray-900">
                                    <a href="{{ route('vendors.show', $vendor) }}" class="vendor-open">{{ $vendor->name }}</a>
                                </td>
                                <td class="text-gray-900">{{ $vendor->phone ?: '—' }}</td>
                                <td class="text-gray-900">{{ $vendor->city ?: '—' }}</td>
                                <td class="text-gray-900 text-end">{{ number_format((float) $vendor->opening_balance, 2) }}</td>
                                <td class="text-gray-900 text-end">{{ number_format((float) $vendor->current_balance, 2) }}</td>
                                <td class="text-gray-900">{{ $vendor->is_active ? 'Active' : 'Inactive' }}</td>
                                <td class="text-end vendor-row-actions">
                                    <a href="{{ route('vendors.show', $vendor) }}" class="btn btn-sm btn-outline-secondary">Ledger</a>
                                    <a href="{{ route('vendors.edit', $vendor) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <form action="{{ route('vendors.destroy', $vendor) }}" method="post" class="d-inline">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this vendor?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-gray-600">No vendors yet.</td>
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
        .vendor-row { cursor: pointer; }
        .vendor-row:hover { filter: brightness(0.97); }
        .vendor-open { font-weight: 600; text-decoration: none; }
    </style>
    <script>
        document.addEventListener('click', function (event) {
            const row = event.target.closest('.vendor-row');
            if (!row) return;
            if (event.target.closest('.vendor-row-actions, a, button, form')) return;
            window.location.href = row.dataset.href;
        });
    </script>
@endpush
