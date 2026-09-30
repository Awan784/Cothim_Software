@php
    $selectId = $selectId ?? 'pakistan_city';
    $selectedCity = old('city', $selectedCity ?? null);
    $cities = config('pakistan.cities', []);
    if ($selectedCity && ! in_array($selectedCity, $cities, true)) {
        $cities[] = $selectedCity;
        sort($cities, SORT_NATURAL | SORT_FLAG_CASE);
    }
@endphp
<select name="city" id="{{ $selectId }}" class="form-select ams-search-select @error('city') is-invalid @enderror" data-ams-search="1" data-search-placeholder="Search city" {{ ! empty($required) ? 'required' : '' }}>
    <option value="">Search or select city</option>
    @foreach($cities as $city)
        <option value="{{ $city }}" {{ $selectedCity === $city ? 'selected' : '' }}>{{ $city }}</option>
    @endforeach
</select>
@error('city') <div class="invalid-feedback">{{ $message }}</div> @enderror
