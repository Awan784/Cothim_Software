<div class="mb-3">
    <label class="form-label">Name</label>
    <input name="name" value="{{ old('name', $vendor->name) }}" class="form-control @error('name') is-invalid @enderror" required>
    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Phone</label>
        <input name="phone" value="{{ old('phone', $vendor->phone) }}" class="form-control @error('phone') is-invalid @enderror">
        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Email</label>
        <input name="email" type="email" value="{{ old('email', $vendor->email) }}" class="form-control @error('email') is-invalid @enderror">
        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">City</label>
        @include('partials.pakistan-city-select', [
            'selectId' => 'vendor_city',
            'selectedCity' => old('city', $vendor->city),
        ])
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Opening balance</label>
        <input name="opening_balance" value="{{ old('opening_balance', $vendor->opening_balance ?? 0) }}" class="form-control @error('opening_balance') is-invalid @enderror">
        @error('opening_balance') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="mb-3">
    <label class="form-label">Address</label>
    <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="3">{{ old('address', $vendor->address) }}</textarea>
    @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="form-check mb-3">
    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
        {{ old('is_active', $vendor->is_active ?? true) ? 'checked' : '' }}>
    <label class="form-check-label" for="is_active">Active</label>
</div>
