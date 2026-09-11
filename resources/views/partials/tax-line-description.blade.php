@if($useItemSelect)
    @php
        $selected = (string) ($selected ?? '');
        $itemNames = $stockItems->pluck('name')->all();
    @endphp
    <select name="lines[{{ $index }}][description]" class="form-select form-select-sm line-item" required>
        <option value="">Select item</option>
        @foreach($stockItems as $item)
            <option value="{{ $item->name }}"
                data-price="{{ $item->sale_price !== null ? number_format((float) $item->sale_price, 2, '.', '') : '' }}"
                {{ $selected === (string) $item->name ? 'selected' : '' }}>
                {{ $item->name }}@if($item->sku) ({{ $item->sku }})@endif
            </option>
        @endforeach
        @if($selected !== '' && ! in_array($selected, $itemNames, true))
            <option value="{{ $selected }}" selected>{{ $selected }}</option>
        @endif
    </select>
@else
    <input name="lines[{{ $index }}][description]" class="form-control form-control-sm" value="{{ $selected ?? '' }}" required>
@endif
