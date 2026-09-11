@php $category = $stockCategory; @endphp

<div class="mb-3">
    <label class="form-label">Name</label>
    <input name="name" value="{{ old('name', $category->name) }}" class="form-control @error('name') is-invalid @enderror" required placeholder="e.g. Dilutions, Biochemic, Syrups">
    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label class="form-label">Type</label>
    <select name="kind" class="form-select @error('kind') is-invalid @enderror">
        <option value="">Select type</option>
        @foreach(\App\Models\StockCategory::KINDS as $value => $label)
            <option value="{{ $value }}" {{ old('kind', $category->kind) === $value ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>
    @error('kind') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label class="form-label">Description</label>
    <input name="description" value="{{ old('description', $category->description) }}" class="form-control @error('description') is-invalid @enderror">
    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="form-check mb-3">
    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
        {{ old('is_active', $category->is_active ?? true) ? 'checked' : '' }}>
    <label class="form-check-label" for="is_active">Active</label>
</div>
