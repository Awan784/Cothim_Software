@php
    $selectId = $selectId ?? 'pakistan_city';
    $selectedCity = old('city', $selectedCity ?? null);
    $cities = config('pakistan.cities', []);
    if ($selectedCity && ! in_array($selectedCity, $cities, true)) {
        $cities[] = $selectedCity;
        sort($cities, SORT_NATURAL | SORT_FLAG_CASE);
    }
@endphp
<select name="city" id="{{ $selectId }}" class="form-select @error('city') is-invalid @enderror" {{ ! empty($required) ? 'required' : '' }}>
    <option value="">Search or select city</option>
    @foreach($cities as $city)
        <option value="{{ $city }}" {{ $selectedCity === $city ? 'selected' : '' }}>{{ $city }}</option>
    @endforeach
</select>
@error('city') <div class="invalid-feedback">{{ $message }}</div> @enderror
@push('scripts')
    <script src="{{ asset('vendor/choices.js/public/assets/scripts/choices.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var citySelect = document.getElementById(@json($selectId));
            if (!citySelect || typeof Choices === 'undefined') {
                return;
            }

            new Choices(citySelect, {
                searchEnabled: true,
                searchPlaceholderValue: 'Type to search city',
                placeholder: true,
                placeholderValue: 'Search or select city',
                itemSelectText: '',
                shouldSort: false,
                allowHTML: false,
            });
        });
    </script>
@endpush
