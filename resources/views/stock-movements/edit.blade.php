@extends('template.layout')
@section('title', 'Edit Stock Movement')

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h4 mb-0">Edit Stock Movement</h1>
                <p class="mb-0">Update stock movement record.</p>
            </div>
            <div>
                <a href="{{ route('stock-movements.index') }}" class="btn btn-sm btn-secondary">Back</a>
            </div>
        </div>

        <div class="card p-4">
            <form method="post" action="{{ route('stock-movements.update', $stockMovement) }}">
                @csrf
                @method('put')

                <div class="mb-3">
                    <label class="form-label">Stock Item</label>
                    <select name="stock_item_id" class="form-select @error('stock_item_id') is-invalid @enderror" required>
                        <option value="">Select item</option>
                        @foreach($stockItems as $item)
                            <option value="{{ $item->id }}"
                                {{ (string) old('stock_item_id', $stockMovement->stock_item_id) === (string) $item->id ? 'selected' : '' }}>
                                {{ $item->name }} {{ $item->stockCategory ? '('.$item->stockCategory->name.')' : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('stock_item_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Type</label>
                        <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                            @foreach(['in' => 'IN', 'out' => 'OUT', 'adjust' => 'ADJUST'] as $k => $label)
                                <option value="{{ $k }}" {{ old('type', $stockMovement->type) === $k ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Quantity</label>
                        <input name="quantity" value="{{ old('quantity', $stockMovement->quantity) }}" class="form-control @error('quantity') is-invalid @enderror" required>
                        @error('quantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Unit Cost</label>
                        <input name="unit_cost" value="{{ old('unit_cost', $stockMovement->unit_cost) }}" class="form-control @error('unit_cost') is-invalid @enderror">
                        @error('unit_cost') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Moved At</label>
                        <x-ams-datetime-input name="moved_at" :value="$stockMovement->moved_at" required />
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Reference</label>
                        <input name="reference" value="{{ old('reference', $stockMovement->reference) }}" class="form-control @error('reference') is-invalid @enderror">
                        @error('reference') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="3">{{ old('notes', $stockMovement->notes) }}</textarea>
                    @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <button class="btn btn-gray-800" type="submit">Update</button>
            </form>
        </div>
    </div>
@endsection

