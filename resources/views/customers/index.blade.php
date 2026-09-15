@extends('template.layout')
@section('title', 'Customers')

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h4 mb-0">Customers</h1>
                <p class="mb-0">Shops and pharmacies.</p>
            </div>
            <div>
                <a href="{{ route('customers.create') }}" class="btn btn-sm btn-gray-800">Add Customer</a>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive py-4">
                <table class="table table-flush" data-datatable="true">
                    <thead class="thead-light">
                        <tr>
                            <th>Company / shop</th>
                            <th>Contact</th>
                            <th>NTN</th>
                            <th>City</th>
                            <th>Phone</th>
                            <th class="text-end">Opening</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customers as $customer)
                            <tr class="customer-row" data-href="{{ route('customers.show', $customer) }}" tabindex="0">
                                <td class="text-gray-900">
                                    <a href="{{ route('customers.show', $customer) }}" class="customer-open">{{ $customer->displayName() }}</a>
                                </td>
                                <td class="text-gray-900">{{ $customer->name }}</td>
                                <td class="text-gray-900">{{ $customer->ntn ?: '—' }}</td>
                                <td class="text-gray-900">{{ $customer->city ?: '—' }}</td>
                                <td class="text-gray-900">{{ $customer->mobile ?: $customer->phone }}</td>
                                <td class="text-gray-900 text-end">{{ number_format((float) $customer->opening_balance, 2) }}</td>
                                <td class="text-gray-900">{{ $customer->is_active ? 'Active' : 'Inactive' }}</td>
                                <td class="text-end customer-row-actions">
                                    <a href="{{ route('customers.edit', $customer) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <form action="{{ route('customers.destroy', $customer) }}" method="post" class="d-inline">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this customer?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-gray-600">No customers yet.</td>
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
        .customer-row { cursor: pointer; }
        .customer-row:hover { filter: brightness(0.97); }
        .customer-open { font-weight: 600; text-decoration: none; }
    </style>
    <script>
        document.addEventListener('click', function (event) {
            const row = event.target.closest('.customer-row');
            if (!row) return;
            if (event.target.closest('.customer-row-actions, a, button, form')) return;
            window.location.href = row.dataset.href;
        });
    </script>
@endpush
