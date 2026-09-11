@extends('template.layout')
@section('title', 'Inventory Categories')

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h4 mb-0">Categories</h1>
                <p class="mb-0">Create categories first, then add medicine items.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('stock-items.index') }}" class="btn btn-sm btn-outline-secondary">Items</a>
                <a href="{{ route('stock-categories.create') }}" class="btn btn-sm btn-gray-800">Add Category</a>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive py-4">
                <table class="table table-flush">
                    <thead class="thead-light">
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th>Items</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($stockCategories as $category)
                            <tr>
                                <td class="text-gray-900">{{ $category->name }}</td>
                                <td class="text-gray-900">{{ $category->kindLabel() }}</td>
                                <td class="text-gray-900">{{ $category->description }}</td>
                                <td class="text-gray-900">{{ $category->stock_items_count }}</td>
                                <td class="text-gray-900">{{ $category->is_active ? 'Active' : 'Inactive' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('stock-items.create', ['stock_category_id' => $category->id]) }}" class="btn btn-sm btn-outline-secondary">Add item</a>
                                    <a href="{{ route('stock-categories.edit', $category) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <form action="{{ route('stock-categories.destroy', $category) }}" method="post" class="d-inline">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this category?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-gray-600">No categories yet. Add one to start inventory.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
