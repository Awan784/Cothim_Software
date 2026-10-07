@php $s = $salesman; @endphp

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Name</label>
        <input name="name" value="{{ old('name', $s->name) }}" class="form-control @error('name') is-invalid @enderror" required>
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Cities</label>
        @php
            $selectedCities = old('cities', $s->cityList());
            if (! is_array($selectedCities)) {
                $selectedCities = filled($selectedCities) ? [(string) $selectedCities] : [];
            }
            $cityOptions = config('pakistan.cities', []);
            foreach ($selectedCities as $selectedCity) {
                if ($selectedCity && ! in_array($selectedCity, $cityOptions, true)) {
                    $cityOptions[] = $selectedCity;
                }
            }
            sort($cityOptions, SORT_NATURAL | SORT_FLAG_CASE);
        @endphp
        <div id="salesman-city-list" class="salesman-city-list mb-2">
            @foreach($selectedCities as $selectedCity)
                @continue(trim((string) $selectedCity) === '')
                <span class="salesman-city-chip">
                    {{ $selectedCity }}
                    <input type="hidden" name="cities[]" value="{{ $selectedCity }}">
                    <button type="button" class="salesman-city-remove" aria-label="Remove">&times;</button>
                </span>
            @endforeach
        </div>
        <select id="salesman_city_add" class="form-select ams-search-select" data-ams-search="1" data-search-placeholder="Search and add city">
            <option value="">Search and add city</option>
            @foreach($cityOptions as $cityOption)
                <option value="{{ $cityOption }}">{{ $cityOption }}</option>
            @endforeach
        </select>
        <div class="form-text">Add every city this salesman can sell in. They will see customers from all of these cities.</div>
        @error('cities') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        @error('cities.*') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
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
@push('scripts')
<script>
(function () {
    var picker = document.getElementById('salesman_city_add');
    var list = document.getElementById('salesman-city-list');
    if (!picker || !list) return;

    function hasCity(value) {
        return Array.prototype.some.call(list.querySelectorAll('input[name="cities[]"]'), function (input) {
            return input.value === value;
        });
    }

    function addCity(value) {
        if (!value || hasCity(value)) return;
        var chip = document.createElement('span');
        chip.className = 'salesman-city-chip';
        chip.appendChild(document.createTextNode(value + ' '));
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'cities[]';
        input.value = value;
        chip.appendChild(input);
        var remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'salesman-city-remove';
        remove.setAttribute('aria-label', 'Remove');
        remove.innerHTML = '&times;';
        chip.appendChild(remove);
        list.appendChild(chip);
    }

    function resetPicker() {
        picker.selectedIndex = 0;
        var combo = picker.closest('.ams-combo');
        if (!combo) return;
        var toggle = combo.querySelector('.ams-combo-toggle');
        var empty = picker.options[0] ? (picker.options[0].textContent || '').trim() : 'Search and add city';
        if (toggle) {
            toggle.textContent = empty;
            toggle.classList.add('is-placeholder');
        }
    }

    picker.addEventListener('change', function () {
        addCity(picker.value);
        resetPicker();
    });

    list.addEventListener('click', function (e) {
        var btn = e.target.closest('.salesman-city-remove');
        if (btn) {
            var chip = btn.closest('.salesman-city-chip');
            if (chip) chip.remove();
        }
    });
})();
</script>
@endpush
