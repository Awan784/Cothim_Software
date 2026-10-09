@php
    $stockId = is_array($row) ? (string) ($row['stock_item_id'] ?? '') : '';
    $itemName = is_array($row) ? ($row['item_name'] ?? '') : '';
    $unit = is_array($row) ? ($row['unit'] ?? '') : '';
    $unitPrice = is_array($row) ? ($row['unit_price'] ?? '') : '';
    $qty = is_array($row) ? ($row['quantity'] ?? '1') : '1';
    $batchNo = is_array($row) ? ($row['batch_no'] ?? '') : '';
    $manufacturedAt = is_array($row) ? ($row['manufactured_at'] ?? '') : '';
    $expiryDate = is_array($row) ? ($row['expiry_date'] ?? '') : '';
    $note = is_array($row) ? ($row['note'] ?? '') : '';
    $itemError = is_numeric($idx) ? $errors->first('items.'.$idx.'.stock_item_id') : null;
    $groupedItems = $stockItems->groupBy(fn ($item) => $item->stockCategory?->name ?: 'Inventory');
@endphp

<tr class="po-line">
    <td>
        <select name="items[{{ $idx }}][stock_item_id]" class="form-select po-item{{ $itemError ? ' is-invalid' : '' }}" required>
            <option value="">Select inventory item</option>
            @foreach($groupedItems as $category => $group)
                <optgroup label="{{ $category }}">
                    @foreach($group as $it)
                        <option value="{{ $it->id }}"
                            data-unit="{{ $it->unit }}"
                            data-cost="{{ number_format((float) $it->cost_price, 2, '.', '') }}"
                            data-qty="{{ number_format((float) $it->quantity, 2, '.', '') }}"
                            data-batch="{{ $it->batch_no }}"
                            data-lots-summary="{{ $it->lotsSummary() !== '—' ? $it->lotsSummary() : '' }}"
                            data-name="{{ $it->purchaseLabel() }}"
                            {{ $stockId === (string) $it->id ? 'selected' : '' }}>
                            {{ $it->purchaseLabel() }} — {{ number_format((float) $it->quantity, 2) }} {{ strtoupper($it->unit ?: 'PCS') }}
                        </option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>
        <div class="form-text po-on-hand">On hand: —</div>
        @if($itemError)
            <div class="invalid-feedback d-block">{{ $itemError }}</div>
        @endif
        <input name="items[{{ $idx }}][item_name]" class="form-control po-manual-name d-none mt-1" value="{{ $itemName }}" placeholder="Item name" maxlength="255">
    </td>
    <td>
        <input name="items[{{ $idx }}][unit]" class="form-control po-unit" value="{{ $unit }}" placeholder="pcs" readonly>
    </td>
    <td>
        <input name="items[{{ $idx }}][unit_price]" class="form-control text-end po-price" value="{{ $unitPrice }}" inputmode="decimal" required>
    </td>
    <td>
        <input name="items[{{ $idx }}][quantity]" class="form-control text-end po-qty" value="{{ $qty }}" inputmode="decimal" required>
    </td>
    <td class="po-lot-col">
        <input name="items[{{ $idx }}][batch_no]" class="form-control po-batch" value="{{ $batchNo }}" placeholder="e.g. 002" maxlength="100">
    </td>
    <td class="po-lot-col">
        <input type="text" name="items[{{ $idx }}][manufactured_at]" class="form-control ams-date-input po-mfg" value="{{ ams_date_input($manufacturedAt) }}" placeholder="dd-mm-yyyy" autocomplete="off">
    </td>
    <td class="po-lot-col">
        <input type="text" name="items[{{ $idx }}][expiry_date]" class="form-control ams-date-input po-expiry" value="{{ ams_date_input($expiryDate) }}" placeholder="dd-mm-yyyy" autocomplete="off">
    </td>
    <td>
        <input name="items[{{ $idx }}][note]" class="form-control po-note" value="{{ $note }}" placeholder="Note">
    </td>
    <td class="text-end text-gray-900">
        <span class="po-line-total">0.00</span>
    </td>
    <td class="text-end">
        <button type="button" class="btn btn-sm btn-outline-danger po-remove">Remove</button>
    </td>
</tr>
