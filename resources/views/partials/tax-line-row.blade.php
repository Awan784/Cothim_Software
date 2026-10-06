@php
    $index = $index ?? 0;
    $row = $row ?? [];
    $showDiscount = $showDiscount ?? false;
    $showTax = $showTax ?? true;
    $showBatch = $showBatch ?? false;
    $hideStockQty = $hideStockQty ?? false;
    $showPrintNote = $showPrintNote ?? false;
    $hiddenVat = $showTax ? ($row['vat_rate'] ?? $vatRate) : 0;
    $selectedLotId = (string) ($row['stock_item_lot_id'] ?? '');
    $lotOptions = collect();
    if ($showBatch && ! empty($row['stock_item_id'])) {
        $matched = $stockItems->firstWhere('id', (int) $row['stock_item_id']);
        if ($matched) {
            $lotOptions = $matched->relationLoaded('lots')
                ? $matched->lots
                : $matched->lots()->orderBy('received_at')->orderBy('id')->get();
        }
    }
@endphp
<tr class="tax-line">
    <td class="line-item-cell" data-label="{{ $useItemSelect ? 'Item' : 'Description' }}">
        @include('partials.tax-line-description', ['index' => $index, 'row' => $row, 'selected' => $row['description'] ?? '', 'useItemSelect' => $useItemSelect, 'stockItems' => $stockItems])
        @unless($showTax)
            <input type="hidden" name="lines[{{ $index }}][vat_rate]" class="line-rate" value="{{ $hiddenVat }}">
        @endunless
    </td>
    @if($showPrintNote)
        <td class="line-print-note-cell" data-label="Note">
            <input name="lines[{{ $index }}][print_note]" class="form-control form-control-sm line-print-note" maxlength="100" value="{{ $row['print_note'] ?? '' }}" placeholder="e.g. bonus">
        </td>
    @endif
    @if($showBatch)
        <td class="line-batch" data-label="Batch #">
            <select name="lines[{{ $index }}][stock_item_lot_id]" class="form-select form-select-sm line-lot" data-ams-plain="1">
                @php
                    $availableLots = $lotOptions->filter(fn ($lot) => (float) $lot->quantity > 0 || $selectedLotId === (string) $lot->id);
                @endphp
                <option value="">{{ $availableLots->isNotEmpty() ? 'Select batch' : (! empty($row['stock_item_id']) ? 'No batch in stock' : 'Select item first') }}</option>
                @foreach($availableLots as $lot)
                    <option value="{{ $lot->id }}"
                        data-batch="{{ $lot->batch_no }}"
                        data-qty="{{ number_format((float) $lot->quantity, 2, '.', '') }}"
                        {{ $selectedLotId === (string) $lot->id ? 'selected' : '' }}>
                        {{ $lot->dropdownLabel($hideStockQty) }}
                    </option>
                @endforeach
            </select>
        </td>
    @endif
    <td class="line-qty-cell" data-label="Qty">
        <input name="lines[{{ $index }}][quantity]" class="form-control form-control-sm text-end line-qty" value="{{ $row['quantity'] ?? 1 }}">
    </td>
    <td class="line-price-cell" data-label="Unit price">
        <input name="lines[{{ $index }}][unit_price]" class="form-control form-control-sm text-end line-price" value="{{ $row['unit_price'] ?? 0 }}">
    </td>
    @if($showDiscount)
        <td class="line-discount-cell" data-label="Disc %">
            <input name="lines[{{ $index }}][discount_rate]" class="form-control form-control-sm text-end line-discount" value="{{ $row['discount_rate'] ?? 0 }}">
        </td>
    @endif
    @if($showTax)
        <td class="line-tax-cell" data-label="Tax %">
            <input name="lines[{{ $index }}][vat_rate]" class="form-control form-control-sm text-end line-rate" value="{{ $row['vat_rate'] ?? $vatRate }}">
        </td>
        <td class="text-end line-vat" data-label="Tax">0.00</td>
    @endif
    <td class="text-end line-total" data-label="Total">0.00</td>
    <td class="line-remove">
        <button type="button" class="btn btn-sm btn-outline-danger remove-line">Remove</button>
    </td>
</tr>
