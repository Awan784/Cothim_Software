@php
    $index = $index ?? 0;
    $row = $row ?? [];
    $showDiscount = $showDiscount ?? false;
    $showTax = $showTax ?? true;
    $showBatch = $showBatch ?? false;
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
    <td>
        @include('partials.tax-line-description', ['index' => $index, 'row' => $row, 'selected' => $row['description'] ?? '', 'useItemSelect' => $useItemSelect, 'stockItems' => $stockItems])
        @unless($showTax)
            <input type="hidden" name="lines[{{ $index }}][vat_rate]" class="line-rate" value="{{ $hiddenVat }}">
        @endunless
    </td>
    @if($showBatch)
        <td class="line-batch">
            <select name="lines[{{ $index }}][stock_item_lot_id]" class="form-select form-select-sm line-lot" data-ams-plain="1">
                <option value="">{{ $lotOptions->isNotEmpty() ? 'Select batch' : 'Select item first' }}</option>
                @foreach($lotOptions as $lot)
                    <option value="{{ $lot->id }}"
                        data-batch="{{ $lot->batch_no }}"
                        data-qty="{{ number_format((float) $lot->quantity, 2, '.', '') }}"
                        {{ $selectedLotId === (string) $lot->id ? 'selected' : '' }}>
                        {{ $lot->batchLabel() }} · {{ number_format((float) $lot->quantity, 2) }}
                    </option>
                @endforeach
            </select>
        </td>
    @endif
    <td><input name="lines[{{ $index }}][quantity]" class="form-control form-control-sm text-end line-qty" value="{{ $row['quantity'] ?? 1 }}"></td>
    <td><input name="lines[{{ $index }}][unit_price]" class="form-control form-control-sm text-end line-price" value="{{ $row['unit_price'] ?? 0 }}"></td>
    @if($showDiscount)
        <td><input name="lines[{{ $index }}][discount_rate]" class="form-control form-control-sm text-end line-discount" value="{{ $row['discount_rate'] ?? 0 }}"></td>
    @endif
    @if($showTax)
        <td><input name="lines[{{ $index }}][vat_rate]" class="form-control form-control-sm text-end line-rate" value="{{ $row['vat_rate'] ?? $vatRate }}"></td>
        <td class="text-end line-vat">0.00</td>
    @endif
    <td class="text-end line-total">0.00</td>
    <td><button type="button" class="btn btn-sm btn-outline-danger remove-line">×</button></td>
</tr>
