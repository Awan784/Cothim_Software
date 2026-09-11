@if($useItemSelect)
    @php
        $row = $row ?? [];
        $selectedId = (string) ($row['stock_item_id'] ?? '');
        $selectedName = (string) ($selected ?? $row['description'] ?? '');
        if ($selectedId === '' && $selectedName !== '') {
            $match = $stockItems->first(function ($item) use ($selectedName) {
                if ((string) $item->name === $selectedName) {
                    return true;
                }
                if ($item->has_variants && $item->variants->isNotEmpty()) {
                    foreach ($item->variants as $variant) {
                        if ($item->name.' — '.$variant->label() === $selectedName) {
                            return true;
                        }
                    }
                }
                return false;
            });
            $selectedId = $match ? (string) $match->id : '';
        }
    @endphp
    <select name="lines[{{ $index }}][stock_item_id]" class="form-select form-select-sm line-item" required>
        <option value="">Select item</option>
        @foreach($stockItems as $item)
            @if($item->has_variants && $item->variants->isNotEmpty())
                @foreach($item->variants as $variant)
                    @php $label = $item->name.' — '.$variant->label(); @endphp
                    <option value="{{ $item->id }}"
                        data-description="{{ $label }}"
                        data-price="{{ number_format((float) $variant->price, 2, '.', '') }}"
                        data-qty="{{ number_format((float) $item->quantity, 2, '.', '') }}"
                        {{ $selectedId === (string) $item->id && $selectedName === $label ? 'selected' : ($selectedId === (string) $item->id && $selectedName === '' ? 'selected' : '') }}>
                        {{ $label }} — qty {{ number_format((float) $item->quantity, 2) }}
                    </option>
                @endforeach
            @else
                <option value="{{ $item->id }}"
                    data-description="{{ $item->name }}"
                    data-price="{{ $item->sale_price !== null ? number_format((float) $item->sale_price, 2, '.', '') : '' }}"
                    data-qty="{{ number_format((float) $item->quantity, 2, '.', '') }}"
                    {{ $selectedId === (string) $item->id ? 'selected' : '' }}>
                    {{ $item->name }} — qty {{ number_format((float) $item->quantity, 2) }}
                </option>
            @endif
        @endforeach
    </select>
    <input type="hidden" name="lines[{{ $index }}][description]" class="line-description" value="{{ $selectedName }}">
@else
    <input name="lines[{{ $index }}][description]" class="form-control form-control-sm" value="{{ $selected ?? '' }}" required>
@endif
