@extends('template.layout')
@section('title', 'Nominal Accounts')

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h4 mb-0">Nominal A/C (Chart of Accounts)</h1>
                <p class="mb-0">Manage nominal accounts.</p>
            </div>
            <div>
                <a href="{{ route('nominal-accounts.create') }}" class="btn btn-sm btn-gray-800">Add Nominal A/C</a>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive py-4">
                <table class="table table-flush">
                    <thead class="thead-light">
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Parent</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($nominalAccounts as $acc)
                            <tr>
                                <td class="text-gray-900">{{ $acc->code }}</td>
                                <td class="text-gray-900">{{ $acc->name }}</td>
                                <td class="text-gray-900 text-capitalize">{{ $acc->type }}</td>
                                <td class="text-gray-900">{{ $acc->parent?->name }}</td>
                                <td class="text-gray-900">{{ $acc->is_active ? 'Active' : 'Inactive' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('nominal-accounts.edit', $acc) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <form action="{{ route('nominal-accounts.destroy', $acc) }}" method="post" class="d-inline">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this nominal account?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-gray-600">No nominal accounts yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

