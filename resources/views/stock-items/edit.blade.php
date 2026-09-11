@extends('template.layout')
@section('title', 'Edit Stock Item')

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h4 mb-0">Edit Stock Item</h1>
                <p class="mb-0">Update stock item details.</p>
            </div>
            <div>
                <a href="{{ route('stock-items.index') }}" class="btn btn-sm btn-secondary">Back</a>
            </div>
        </div>

        <div class="card p-4">
            <form method="post" action="{{ route('stock-items.update', $stockItem) }}">
                @csrf
                @method('put')

                <div class="mb-3">
                    <label class="form-label">Category</label>
                    <select name="stock_category_id" class="form-select @error('stock_category_id') is-invalid @enderror" required>
                        <option value="">Select category</option>
                        @foreach($stockCategories as $cat)
                            <option value="{{ $cat->id }}"
                                {{ (string) old('stock_category_id', $stockItem->stock_category_id) === (string) $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('stock_category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">SKU</label>
                        <input name="sku" value="{{ old('sku', $stockItem->sku) }}" class="form-control @error('sku') is-invalid @enderror">
                        @error('sku') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Unit</label>
                        <input name="unit" value="{{ old('unit', $stockItem->unit) }}" class="form-control @error('unit') is-invalid @enderror">
                        @error('unit') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input name="name" value="{{ old('name', $stockItem->name) }}" class="form-control @error('name') is-invalid @enderror" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Cost Price</label>
                        <input name="cost_price" value="{{ old('cost_price', $stockItem->cost_price) }}" class="form-control @error('cost_price') is-invalid @enderror" required>
                        @error('cost_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Sale Price</label>
                        <input name="sale_price" value="{{ old('sale_price', $stockItem->sale_price) }}" class="form-control @error('sale_price') is-invalid @enderror">
                        @error('sale_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Reorder Level</label>
                    <input name="reorder_level" value="{{ old('reorder_level', $stockItem->reorder_level) }}" class="form-control @error('reorder_level') is-invalid @enderror">
                    @error('reorder_level') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
                        {{ old('is_active', $stockItem->is_active) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>

                <button class="btn btn-gray-800" type="submit">Update</button>
            </form>
        </div>
    </div>
@endsection

