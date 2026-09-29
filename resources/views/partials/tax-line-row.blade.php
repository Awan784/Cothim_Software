@php
    $index = $index ?? 0;
    $row = $row ?? [];
    $showDiscount = $showDiscount ?? false;
    $showTax = $showTax ?? true;
    $showBatch = $showBatch ?? false;
    $hiddenVat = $showTax ? ($row['vat_rate'] ?? $vatRate) : 0;
    $batchNo = '';
    if ($showBatch && ! empty($row['stock_item_id'])) {
        $matched = $stockItems->firstWhere('id', (int) $row['stock_item_id']);
        $batchNo = $matched?->batch_no ?: '';
    }
@endphp
<tr class="tax-line">
    <td>
        @include('partials.tax-line-description', ['index' => $index, 'row' => $row, 'selected' => $row['description'] ?? '', 'useItemSelect' => $useItemSelect, 'stockItems' => $stockItems])
        @unless($showTax)
            <input type="hidden" name="lines[{{ $index }}][vat_rate]" class="line-rate" value="{{ $hiddenVat }}">
        @endunless
    </td>
    <td><input name="lines[{{ $index }}][quantity]" class="form-control form-control-sm text-end line-qty" value="{{ $row['quantity'] ?? 1 }}"></td>
    <td><input name="lines[{{ $index }}][unit_price]" class="form-control form-control-sm text-end line-price" value="{{ $row['unit_price'] ?? 0 }}"></td>
    @if($showDiscount)
        <td><input name="lines[{{ $index }}][discount_rate]" class="form-control form-control-sm text-end line-discount" value="{{ $row['discount_rate'] ?? 0 }}"></td>
    @endif
    @if($showBatch)
        <td class="line-batch text-nowrap">{{ $batchNo !== '' ? $batchNo : '—' }}</td>
    @endif
    @if($showTax)
        <td><input name="lines[{{ $index }}][vat_rate]" class="form-control form-control-sm text-end line-rate" value="{{ $row['vat_rate'] ?? $vatRate }}"></td>
        <td class="text-end line-vat">0.00</td>
    @endif
    <td class="text-end line-total">0.00</td>
    <td><button type="button" class="btn btn-sm btn-outline-danger remove-line">×</button></td>
</tr>
