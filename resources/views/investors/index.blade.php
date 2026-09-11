@extends('template.layout')
@section('title', 'Investors')

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h4 mb-0">Investors</h1>
                <p class="mb-0">Manage investors.</p>
            </div>
            <div>
                <a href="{{ route('investors.create') }}" class="btn btn-sm btn-gray-800">Add Investor</a>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive py-4">
                <table class="table table-flush">
                    <thead class="thead-light">
                        <tr>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th class="text-end">Opening Investment</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($investors as $inv)
                            <tr>
                                <td class="text-gray-900">
                                    <a href="{{ route('investors.show', $inv) }}">{{ $inv->name }}</a>
                                </td>
                                <td class="text-gray-900">{{ $inv->phone }}</td>
                                <td class="text-gray-900">{{ $inv->email }}</td>
                                <td class="text-gray-900 text-end">{{ number_format((float) $inv->opening_investment, 2) }}</td>
                                <td class="text-gray-900">{{ $inv->is_active ? 'Active' : 'Inactive' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('investors.edit', $inv) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <form action="{{ route('investors.destroy', $inv) }}" method="post" class="d-inline">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this investor?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-gray-600">No investors yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

