@php
    $item = $stockItem;
    $hasVariants = (bool) old('has_variants', $item->has_variants);
    $variantRows = old('variants', $item->relationLoaded('variants') || $item->exists
        ? $item->variants->map(fn ($v) => ['id' => $v->id, 'name' => $v->name, 'size' => $v->size, 'price' => $v->price])->all()
        : []);
    if ($hasVariants && $variantRows === []) {
        $variantRows = [['id' => '', 'name' => '', 'size' => '', 'price' => '']];
    }
@endphp

<div class="mb-3">
    <label class="form-label">Category</label>
    <select name="stock_category_id" class="form-select @error('stock_category_id') is-invalid @enderror" required>
        <option value="">Select category first</option>
        @foreach($stockCategories as $cat)
            <option value="{{ $cat->id }}" {{ (string) old('stock_category_id', $item->stock_category_id) === (string) $cat->id ? 'selected' : '' }}>
                {{ $cat->name }}@if($cat->kind) ({{ $cat->kindLabel() }})@endif
            </option>
        @endforeach
    </select>
    @error('stock_category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Name</label>
        <input name="name" value="{{ old('name', $item->name) }}" class="form-control @error('name') is-invalid @enderror" required placeholder="e.g. Arnica Montana">
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">SKU</label>
        <input name="sku" value="{{ old('sku', $item->sku) }}" class="form-control @error('sku') is-invalid @enderror">
        @error('sku') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Batch no</label>
        <input name="batch_no" value="{{ old('batch_no', $item->batch_no) }}" class="form-control @error('batch_no') is-invalid @enderror">
        @error('batch_no') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="row">
    <div class="col-md-3 mb-3">
        <label class="form-label">Unit</label>
        <select name="unit" class="form-select @error('unit') is-invalid @enderror" required>
            @foreach(\App\Models\StockItem::UNITS as $value => $label)
                <option value="{{ $value }}" {{ old('unit', $item->unit ?: 'pcs') === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        @error('unit') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Quantity</label>
        <input name="quantity" value="{{ old('quantity', $item->quantity ?? 0) }}" class="form-control text-end @error('quantity') is-invalid @enderror" required>
        @error('quantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Potency</label>
        <input name="potency" list="potency-list" value="{{ old('potency', $item->potency) }}" class="form-control @error('potency') is-invalid @enderror" placeholder="30C, 200C, Q…">
        <datalist id="potency-list">
            @foreach(\App\Models\StockItem::POTENCIES as $potency)
                <option value="{{ $potency }}"></option>
            @endforeach
        </datalist>
        @error('potency') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Pack size</label>
        <input name="pack_size" value="{{ old('pack_size', $item->pack_size) }}" class="form-control @error('pack_size') is-invalid @enderror" placeholder="10ml, 30ml, 25g">
        @error('pack_size') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Cost price</label>
        <input name="cost_price" value="{{ old('cost_price', $item->cost_price ?? 0) }}" class="form-control ams-amount-input text-end @error('cost_price') is-invalid @enderror" required>
        @error('cost_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Sale price</label>
        <input name="sale_price" value="{{ old('sale_price', $item->sale_price) }}" class="form-control ams-amount-input text-end @error('sale_price') is-invalid @enderror">
        @error('sale_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div class="form-text">Used when the item has no variants.</div>
    </div>
</div>

<div class="form-check mb-3">
    <input class="form-check-input" type="checkbox" name="has_variants" value="1" id="has_variants" {{ $hasVariants ? 'checked' : '' }}>
    <label class="form-check-label" for="has_variants">This item has variants (name, size, price)</label>
</div>

<div id="variantsWrap" class="{{ $hasVariants ? '' : 'd-none' }} mb-4">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <strong>Variants</strong>
        <button type="button" class="btn btn-sm btn-outline-primary" id="addVariant">Add variant</button>
    </div>
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead class="thead-light">
                <tr>
                    <th>Name</th>
                    <th>Size</th>
                    <th class="text-end">Price</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="variantRows">
                @foreach($variantRows as $i => $row)
                    <tr class="variant-row">
                        <td>
                            <input type="hidden" name="variants[{{ $i }}][id]" value="{{ $row['id'] ?? '' }}">
                            <input name="variants[{{ $i }}][name]" value="{{ $row['name'] ?? '' }}" class="form-control form-control-sm" placeholder="e.g. Glass bottle">
                        </td>
                        <td>
                            <input name="variants[{{ $i }}][size]" value="{{ $row['size'] ?? '' }}" class="form-control form-control-sm" placeholder="10ml / 30ml">
                        </td>
                        <td>
                            <input name="variants[{{ $i }}][price]" value="{{ $row['price'] ?? '' }}" class="form-control form-control-sm text-end ams-amount-input" placeholder="0.00">
                        </td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-outline-danger remove-variant">×</button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @error('variants') <div class="text-danger small mt-2">{{ $message }}</div> @enderror
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Manufacturer</label>
        <input name="manufacturer" value="{{ old('manufacturer', $item->manufacturer) }}" class="form-control @error('manufacturer') is-invalid @enderror" placeholder="e.g. Schwabe, Reckeweg">
        @error('manufacturer') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Expiry date</label>
        <x-ams-date-input name="expiry_date" :value="old('expiry_date', $item->expiry_date)" />
    </div>
    <div class="col-md-3 mb-3">
        <label class="form-label">Barcode</label>
        <input name="barcode" value="{{ old('barcode', $item->barcode) }}" class="form-control @error('barcode') is-invalid @enderror">
        @error('barcode') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">HS / HS code</label>
        <input name="hs_code" value="{{ old('hs_code', $item->hs_code) }}" class="form-control @error('hs_code') is-invalid @enderror">
        @error('hs_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="mb-3">
    <label class="form-label">Composition</label>
    <textarea name="composition" rows="2" class="form-control @error('composition') is-invalid @enderror" placeholder="Active ingredients / dilution base">{{ old('composition', $item->composition) }}</textarea>
    @error('composition') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label class="form-label">Description</label>
    <textarea name="description" rows="3" class="form-control @error('description') is-invalid @enderror">{{ old('description', $item->description) }}</textarea>
    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Storage note</label>
        <input name="storage_note" value="{{ old('storage_note', $item->storage_note) }}" class="form-control @error('storage_note') is-invalid @enderror" placeholder="Store in a cool, dry place">
        @error('storage_note') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Reorder level</label>
        <input name="reorder_level" type="number" min="0" value="{{ old('reorder_level', $item->reorder_level) }}" class="form-control @error('reorder_level') is-invalid @enderror">
        @error('reorder_level') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="form-check mb-3">
    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
        {{ old('is_active', $item->is_active ?? true) ? 'checked' : '' }}>
    <label class="form-check-label" for="is_active">Active</label>
</div>
