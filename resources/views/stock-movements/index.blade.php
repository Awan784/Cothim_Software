@extends('template.layout')
@section('title', 'Stock Movements')

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h4 mb-0">Stock Movements</h1>
                <p class="mb-0">Latest stock in/out/adjust records.</p>
            </div>
            <div>
                <a href="{{ route('stock-movements.create') }}" class="btn btn-sm btn-gray-800">Add Movement</a>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive py-4">
                <table class="table table-flush" data-datatable="true">
                    <thead class="thead-light">
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Item</th>
                            <th>Category</th>
                            <th class="text-end">Qty</th>
                            <th class="text-end">Unit Cost</th>
                            <th>Reference</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($stockMovements as $m)
                            <tr>
                                <td class="text-gray-900">{{ ams_datetime($m->moved_at) }}</td>
                                <td class="text-gray-900 text-uppercase">{{ $m->type }}</td>
                                <td class="text-gray-900">{{ $m->stockItem?->name }}</td>
                                <td class="text-gray-900">{{ $m->stockItem?->stockCategory?->name }}</td>
                                <td class="text-gray-900 text-end">{{ number_format((float) $m->quantity, 3) }}</td>
                                <td class="text-gray-900 text-end">{{ $m->unit_cost !== null ? number_format((float) $m->unit_cost, 2) : '' }}</td>
                                <td class="text-gray-900">{{ $m->reference }}</td>
                                <td class="text-end">
                                    <a href="{{ route('stock-movements.edit', $m) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <form action="{{ route('stock-movements.destroy', $m) }}" method="post" class="d-inline">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this movement?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-gray-600">No movements yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

