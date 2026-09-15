@php
    $stockId = is_array($row) ? (string) ($row['stock_item_id'] ?? '') : '';
    $unit = is_array($row) ? ($row['unit'] ?? '') : '';
    $unitPrice = is_array($row) ? ($row['unit_price'] ?? '') : '';
    $qty = is_array($row) ? ($row['quantity'] ?? '1') : '1';
    $note = is_array($row) ? ($row['note'] ?? '') : '';
    $itemError = is_numeric($idx) ? $errors->first('items.'.$idx.'.stock_item_id') : null;
    $groupedItems = $stockItems->groupBy(fn ($item) => $item->stockCategory?->name ?: 'Inventory');
@endphp

<tr class="sr-line">
    <td>
        <select name="items[{{ $idx }}][stock_item_id]" class="form-select sr-item{{ $itemError ? ' is-invalid' : '' }}" required>
            <option value="">Select inventory item</option>
            @foreach($groupedItems as $category => $group)
                <optgroup label="{{ $category }}">
                    @foreach($group as $it)
                        <option value="{{ $it->id }}"
                            data-unit="{{ $it->unit }}"
                            data-price="{{ number_format((float) ($it->sale_price ?: $it->cost_price), 2, '.', '') }}"
                            data-qty="{{ number_format((float) $it->quantity, 2, '.', '') }}"
                            {{ $stockId === (string) $it->id ? 'selected' : '' }}>
                            {{ $it->purchaseLabel() }} — {{ ams_num($it->quantity) }} {{ strtoupper($it->unit ?: 'PCS') }}
                        </option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>
        <div class="form-text sr-on-hand">On hand: —</div>
        @if($itemError)
            <div class="invalid-feedback d-block">{{ $itemError }}</div>
        @endif
    </td>
    <td>
        <input name="items[{{ $idx }}][unit]" class="form-control sr-unit" value="{{ $unit }}" placeholder="pcs" readonly>
    </td>
    <td>
        <input name="items[{{ $idx }}][unit_price]" class="form-control text-end sr-price" value="{{ $unitPrice }}" inputmode="decimal" required>
    </td>
    <td>
        <input name="items[{{ $idx }}][quantity]" class="form-control text-end sr-qty" value="{{ $qty }}" inputmode="decimal" required>
    </td>
    <td>
        <input name="items[{{ $idx }}][note]" class="form-control sr-note" value="{{ $note }}" placeholder="Reason">
    </td>
    <td class="text-end text-gray-900">
        <span class="sr-line-total">0</span>
    </td>
    <td class="text-end">
        <button type="button" class="btn btn-sm btn-outline-danger sr-remove">Remove</button>
    </td>
</tr>
