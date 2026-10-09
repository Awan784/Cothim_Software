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
    $lineLots = collect();
    if ($showBatch && ! empty($row['stock_item_id'])) {
        $matched = $stockItems->firstWhere('id', (int) $row['stock_item_id']);
        if ($matched) {
            $lineLots = $matched->lotsCollection();
            if ($selectedLotId === '') {
                $selectedLotId = (string) ($matched->currentLot()?->id ?? '');
            }
            if ($batchLabel === '') {
                $selectedLot = $lineLots->firstWhere('id', (int) $selectedLotId) ?? $matched->currentLot();
                $batchLabel = (string) ($selectedLot?->batchLabel() === '—' ? '' : ($selectedLot?->batchLabel() ?? $matched->batch_no ?? ''));
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
            <select name="lines[{{ $index }}][stock_item_lot_id]" class="form-select form-select-sm line-lot" data-ams-plain="1">
                <option value="">Select batch</option>
                @foreach($lineLots as $lot)
                    <option value="{{ $lot->id }}"
                        data-batch="{{ $lot->batchLabel() === '—' ? '' : $lot->batchLabel() }}"
                        {{ $selectedLotId === (string) $lot->id ? 'selected' : '' }}>
                        {{ $hideStockQty ? $lot->batchLabel() : $lot->dropdownLabel() }}
                    </option>
                @endforeach
            </select>
            <input type="hidden" name="lines[{{ $index }}][batch_no]" class="line-batch-no" value="{{ $batchLabel === '—' ? '' : $batchLabel }}">
        </td>
    @endif
    <td class="line-qty-cell" data-label="Qty">
        <input name="lines[{{ $index }}][quantity]" class="form-control form-control-sm text-end line-qty" value="{{ $row['quantity'] ?? 1 }}" inputmode="decimal" size="6">
    </td>
    <td class="line-price-cell" data-label="Unit price">
        <input name="lines[{{ $index }}][unit_price]" class="form-control form-control-sm text-end line-price" value="{{ $row['unit_price'] ?? 0 }}" inputmode="decimal" size="8">
    </td>
    @if($showDiscount)
        <td class="line-discount-cell" data-label="Disc %">
            <input name="lines[{{ $index }}][discount_rate]" class="form-control form-control-sm text-end line-discount" value="{{ $row['discount_rate'] ?? 0 }}" inputmode="decimal" size="5">
        </td>
    @endif
    @if($showCommission)
        <td class="line-commission-cell" data-label="Comm %">
            <input name="lines[{{ $index }}][commission_rate]" class="form-control form-control-sm text-end line-commission" value="{{ $row['commission_rate'] ?? 0 }}" min="0" max="100" step="0.01" inputmode="decimal" size="5">
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
