@php $s = $salesman; @endphp

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Name</label>
        <input name="name" value="{{ old('name', $s->name) }}" class="form-control @error('name') is-invalid @enderror" required>
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">City</label>
        @include('partials.pakistan-city-select', [
            'selectId' => 'salesman_city',
            'selectedCity' => old('city', $s->city),
            'required' => false,
        ])
        @error('city') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label">Username</label>
        <input name="username" value="{{ old('username', $s->username) }}" class="form-control @error('username') is-invalid @enderror" required autocomplete="off">
        @error('username') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Password</label>
        <input name="password" type="password" class="form-control @error('password') is-invalid @enderror" {{ $s->exists ? '' : 'required' }} autocomplete="new-password">
        @if($s->exists)
            <div class="form-text">Leave blank to keep the current password.</div>
        @endif
        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Confirm password</label>
        <input name="password_confirmation" type="password" class="form-control" {{ $s->exists ? '' : 'required' }} autocomplete="new-password">
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label">Phone</label>
        <input name="phone" value="{{ old('phone', $s->phone) }}" class="form-control @error('phone') is-invalid @enderror">
        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Mobile</label>
        <input name="mobile" value="{{ old('mobile', $s->mobile) }}" class="form-control @error('mobile') is-invalid @enderror">
        @error('mobile') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Email</label>
        <input name="email" type="email" value="{{ old('email', $s->email) }}" class="form-control @error('email') is-invalid @enderror">
        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Monthly target</label>
        <input name="monthly_target" value="{{ old('monthly_target', $s->monthly_target ?? 0) }}" class="form-control @error('monthly_target') is-invalid @enderror">
        @error('monthly_target') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Commission % override</label>
        <input name="commission_percent" value="{{ old('commission_percent', $s->commission_percent) }}" class="form-control @error('commission_percent') is-invalid @enderror" placeholder="Company default">
        <div class="form-text">Leave blank to use the company salesman commission %. Applied to the remaining amount after company retain.</div>
        @error('commission_percent') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="form-check mb-3">
    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
        {{ old('is_active', $s->is_active ?? true) ? 'checked' : '' }}>
    <label class="form-check-label" for="is_active">Active</label>
</div>
