@extends('template.layout')
@section('title', 'Stock Items')

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h4 mb-0">Stock Items</h1>
                <p class="mb-0">Manage stock items.</p>
            </div>
            <div>
                <a href="{{ route('stock-items.create') }}" class="btn btn-sm btn-gray-800">Add Item</a>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive py-4">
                <table class="table table-flush" data-datatable="true">
                    <thead class="thead-light">
                        <tr>
                            <th>SKU</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Unit</th>
                            <th class="text-end">Cost</th>
                            <th class="text-end">Sale</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($stockItems as $item)
                            <tr>
                                <td class="text-gray-900">{{ $item->sku }}</td>
                                <td class="text-gray-900">{{ $item->name }}</td>
                                <td class="text-gray-900">{{ $item->stockCategory?->name }}</td>
                                <td class="text-gray-900">{{ $item->unit }}</td>
                                <td class="text-gray-900 text-end">{{ number_format((float) $item->cost_price, 2) }}</td>
                                <td class="text-gray-900 text-end">{{ $item->sale_price !== null ? number_format((float) $item->sale_price, 2) : '' }}</td>
                                <td class="text-gray-900">{{ $item->is_active ? 'Active' : 'Inactive' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('stock-items.edit', $item) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <form action="{{ route('stock-items.destroy', $item) }}" method="post" class="d-inline">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this item?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-gray-600">No stock items yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

