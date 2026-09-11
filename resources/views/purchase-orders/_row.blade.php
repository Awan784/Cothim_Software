@php
    $stockId = is_array($row) ? ($row['stock_item_id'] ?? '') : '';
    $itemName = is_array($row) ? ($row['item_name'] ?? '') : '';
    $unit = is_array($row) ? ($row['unit'] ?? '') : '';
    $unitPrice = is_array($row) ? ($row['unit_price'] ?? '') : '';
    $qty = is_array($row) ? ($row['quantity'] ?? '') : '';
    $note = is_array($row) ? ($row['note'] ?? '') : '';
@endphp

<tr data-row="1">
    <td>
        <label class="form-label small text-muted mb-1">Stock (optional)</label>
        <select name="items[{{ $idx }}][stock_item_id]" class="form-select mb-2" data-stock-item>
            <option value="">— Manual item —</option>
            @foreach($stockItems as $it)
                <option value="{{ $it->id }}" {{ (string) $stockId === (string) $it->id ? 'selected' : '' }}>
                    {{ $it->name }}
                </option>
            @endforeach
        </select>
        <label class="form-label small text-muted mb-1">Manual item name</label>
        <input name="items[{{ $idx }}][item_name]" class="form-control" value="{{ $itemName }}"
            placeholder="Type item name if not from stock" data-item-name maxlength="255">
    </td>
    <td>
        <input name="items[{{ $idx }}][unit]" class="form-control" value="{{ $unit }}" placeholder="e.g. pcs" data-unit>
    </td>
    <td>
        <input name="items[{{ $idx }}][unit_price]" class="form-control text-end" value="{{ $unitPrice }}" required data-unit-price>
    </td>
    <td>
        <input name="items[{{ $idx }}][quantity]" class="form-control text-end" value="{{ $qty }}" required data-qty>
    </td>
    <td>
        <input name="items[{{ $idx }}][note]" class="form-control" value="{{ $note }}" placeholder="Note">
    </td>
    <td class="text-end text-gray-900">
        <span data-line-total>0.00</span>
    </td>
    <td class="text-end">
        <button type="button" class="btn btn-sm btn-outline-danger" data-remove>Remove</button>
    </td>
</tr>
