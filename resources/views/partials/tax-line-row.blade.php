@php
    $index = $index ?? 0;
    $row = $row ?? [];
    $showDiscount = $showDiscount ?? false;
    $showCommission = $showCommission ?? false;
    $showTax = $showTax ?? true;
    $showBatch = $showBatch ?? false;
    $hideStockQty = $hideStockQty ?? false;
    $showPrintNote = $showPrintNote ?? false;
    $hiddenVat = $showTax ? ($row['vat_rate'] ?? $vatRate) : 0;
    $selectedLotId = (string) ($row['stock_item_lot_id'] ?? '');
    $batchLabel = trim((string) ($row['batch_no'] ?? ''));
    if ($showBatch && $batchLabel === '' && ! empty($row['stock_item_id'])) {
        $matched = $stockItems->firstWhere('id', (int) $row['stock_item_id']);
        if ($matched) {
            $batchLabel = (string) ($matched->batch_no ?: '');
            if ($selectedLotId === '') {
                $selectedLotId = (string) ($matched->currentLot()?->id ?? '');
            }
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
            <input type="hidden" name="lines[{{ $index }}][stock_item_lot_id]" class="line-lot-id" value="{{ $selectedLotId }}">
            <input type="hidden" name="lines[{{ $index }}][batch_no]" class="line-batch-no" value="{{ $batchLabel }}">
            <span class="line-batch-label stock-batch-no">{{ $batchLabel !== '' ? $batchLabel : '—' }}</span>
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
    @if($showCommission)
        <td class="line-commission-cell" data-label="Commission">
            <input name="lines[{{ $index }}][commission_amount]" class="form-control form-control-sm text-end line-commission" value="{{ $row['commission_amount'] ?? 0 }}" min="0" step="0.01">
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
