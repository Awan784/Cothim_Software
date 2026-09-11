@php $c = $customer; @endphp

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Company / shop name</label>
        <input name="company_name" value="{{ old('company_name', $c->company_name) }}" class="form-control @error('company_name') is-invalid @enderror" placeholder="Shop or company name">
        @error('company_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Contact person</label>
        <input name="name" value="{{ old('name', $c->name) }}" class="form-control @error('name') is-invalid @enderror" required>
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Proprietor / owner</label>
        <input name="proprietor_name" value="{{ old('proprietor_name', $c->proprietor_name) }}" class="form-control @error('proprietor_name') is-invalid @enderror">
        @error('proprietor_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Drug / shop license no</label>
        <input name="license_no" value="{{ old('license_no', $c->license_no) }}" class="form-control @error('license_no') is-invalid @enderror">
        @error('license_no') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label">NTN</label>
        <input name="ntn" value="{{ old('ntn', $c->ntn) }}" class="form-control @error('ntn') is-invalid @enderror">
        @error('ntn') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">STRN</label>
        <input name="strn" value="{{ old('strn', $c->strn ?: $c->vat_number) }}" class="form-control @error('strn') is-invalid @enderror">
        @error('strn') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Email</label>
        <input name="email" type="email" value="{{ old('email', $c->email) }}" class="form-control @error('email') is-invalid @enderror">
        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Phone</label>
        <input name="phone" value="{{ old('phone', $c->phone) }}" class="form-control @error('phone') is-invalid @enderror">
        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Mobile</label>
        <input name="mobile" value="{{ old('mobile', $c->mobile) }}" class="form-control @error('mobile') is-invalid @enderror">
        @error('mobile') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">City</label>
        @include('partials.pakistan-city-select', [
            'selectId' => 'customer_city',
            'selectedCity' => old('city', $c->city),
        ])
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Area / market</label>
        <input name="area" value="{{ old('area', $c->area) }}" class="form-control @error('area') is-invalid @enderror">
        @error('area') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="mb-3">
    <label class="form-label">Shop address</label>
    <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="3">{{ old('address', $c->address) }}</textarea>
    @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label class="form-label">Opening balance</label>
    <input name="opening_balance" value="{{ old('opening_balance', $c->opening_balance ?? 0) }}" class="form-control @error('opening_balance') is-invalid @enderror">
    @error('opening_balance') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="form-check mb-3">
    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
        {{ old('is_active', $c->is_active ?? true) ? 'checked' : '' }}>
    <label class="form-check-label" for="is_active">Active</label>
</div>
