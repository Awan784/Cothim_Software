@extends('template.layout')
@section('title', 'Customers')

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h4 mb-0">Customers</h1>
                <p class="mb-0">Manage customers.</p>
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
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th class="text-end">Opening Balance</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customers as $customer)
                            <tr>
                                <td class="text-gray-900">{{ $customer->name }}</td>
                                <td class="text-gray-900">{{ $customer->phone }}</td>
                                <td class="text-gray-900">{{ $customer->email }}</td>
                                <td class="text-gray-900 text-end">{{ number_format((float) $customer->opening_balance, 2) }}</td>
                                <td class="text-gray-900">{{ $customer->is_active ? 'Active' : 'Inactive' }}</td>
                                <td class="text-end">
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
                                <td colspan="6" class="text-center text-gray-600">No customers yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
