@extends('template.layout')
@section('title', 'Salesmen')

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h4 mb-0">Salesmen</h1>
                <p class="mb-0">Assign a city, username, password, and monthly target.</p>
            </div>
            <div>
                <a href="{{ route('salesmen.create') }}" class="btn btn-sm btn-gray-800">Add Salesman</a>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive py-4">
                <table class="table table-flush" data-datatable="true">
                    <thead class="thead-light">
                        <tr>
                            <th>Name</th>
                            <th>Username</th>
                            <th>City</th>
                            <th>Phone</th>
                            <th class="text-end">Shops in city</th>
                            <th class="text-end">Monthly target</th>
                            <th class="text-end">This month</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($salesmen as $salesman)
                            @php
                                $shops = (int) ($shopsByCity[$salesman->city] ?? 0);
                                $achieved = (float) ($monthSalesByCity[$salesman->city] ?? 0);
                            @endphp
                            <tr>
                                <td class="text-gray-900">{{ $salesman->name }}</td>
                                <td class="text-gray-900">{{ $salesman->username }}</td>
                                <td class="text-gray-900">{{ $salesman->city ?: '—' }}</td>
                                <td class="text-gray-900">{{ $salesman->mobile ?: $salesman->phone ?: '—' }}</td>
                                <td class="text-gray-900 text-end">{{ $shops }}</td>
                                <td class="text-gray-900 text-end">{{ number_format((float) $salesman->monthly_target, 2) }}</td>
                                <td class="text-gray-900 text-end">{{ number_format($achieved, 2) }}</td>
                                <td class="text-gray-900">{{ $salesman->is_active ? 'Active' : 'Inactive' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('salesmen.edit', $salesman) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <form action="{{ route('salesmen.destroy', $salesman) }}" method="post" class="d-inline">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this salesman?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-gray-600">No salesmen yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
