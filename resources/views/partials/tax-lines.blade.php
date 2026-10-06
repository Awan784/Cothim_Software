@php
    $rows = $rows ?? [];
    $vatRate = $vatRate ?? 15;
    $stockItems = $stockItems ?? collect();
    $useItemSelect = ! empty($useItemSelect);
    $showDiscount = ! empty($showDiscount);
    $showTax = $showTax ?? true;
    $showBatch = ! empty($showBatch);
    $hideStockQty = ! empty($hideStockQty);
    $showPrintNote = ! empty($showPrintNote);
    $frontCols = 3 + ($showDiscount ? 1 : 0) + ($showBatch ? 1 : 0) + ($showTax ? 1 : 0) + ($showPrintNote ? 1 : 0);
    $hiddenVat = $showTax ? $vatRate : 0;
@endphp
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <strong>Lines</strong>
            @if($showBatch)
                <span class="text-muted small ms-2">Pick the batch you are selling. Add another line to sell a second batch of the same item.</span>
            @endif
        </div>
        <button type="button" class="btn btn-sm btn-outline-primary" id="addTaxLine">Add line</button>
    </div>
    @if($useItemSelect && $stockItems->isEmpty())
        <div class="px-3 pt-3">
            @if(auth('salesman')->check())
                <p class="small text-muted mb-0">No stock items available. Ask admin to add items first.</p>
            @else
                <p class="small text-muted mb-0">No active stock items yet. <a href="{{ route('stock-items.create') }}">Add an item</a> first, then select it here.</p>
            @endif
        </div>
    @endif
    <div class="table-responsive ams-line-table-wrap">
        <table class="table table-flush mb-0" id="taxLinesTable" data-show-discount="{{ $showDiscount ? '1' : '0' }}" data-show-tax="{{ $showTax ? '1' : '0' }}" data-hide-stock-qty="{{ $hideStockQty ? '1' : '0' }}">
            <thead class="thead-light">
                <tr>
                    <th>{{ $useItemSelect ? 'Item' : 'Description' }}</th>
                    @if($showPrintNote)
                        <th>Note <span class="text-muted fw-normal">(optional)</span></th>
                    @endif
                    @if($showBatch)
                        <th>Batch #</th>
                    @endif
                    <th class="text-end">Qty</th>
                    <th class="text-end">Unit price</th>
                    @if($showDiscount)
                        <th class="text-end">Disc %</th>
                    @endif
                    @if($showTax)
                        <th class="text-end">Tax %</th>
                        <th class="text-end">Tax</th>
                    @endif
                    <th class="text-end">Total</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $i => $row)
                    @include('partials.tax-line-row', ['index' => $i, 'row' => $row])
                @empty
                    @include('partials.tax-line-row', ['index' => 0, 'row' => []])
                @endempty
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="{{ $frontCols }}" class="text-end">Subtotal</td>
                    @if($showTax)
                        <td></td>
                    @endif
                    <td class="text-end" id="docSubtotal">0.00</td>
                    <td></td>
                </tr>
                @if($showDiscount)
                    <tr>
                        <td colspan="{{ $frontCols }}" class="text-end">Discount</td>
                        @if($showTax)
                            <td></td>
                        @endif
                        <td class="text-end" id="docDiscount">0.00</td>
                        <td></td>
                    </tr>
                @endif
                @if($showTax)
                    <tr>
                        <td colspan="{{ $frontCols }}" class="text-end">Tax</td>
                        <td></td>
                        <td class="text-end" id="docVat">0.00</td>
                        <td></td>
                    </tr>
                @endif
                <tr>
                    <td colspan="{{ $frontCols }}" class="text-end"><strong>Total</strong></td>
                    @if($showTax)
                        <td></td>
                    @endif
                    <td class="text-end"><strong id="docTotal">0.00</strong></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@push('scripts')
<script>
(function () {
    var table = document.getElementById('taxLinesTable');
    if (!table) return;
    function money(n) { return (Math.round(n * 100) / 100).toFixed(2); }
    function parseLots(opt) {
        if (!opt) return [];
        var raw = opt.getAttribute('data-lots') || '[]';
        try {
            var lots = JSON.parse(raw);
            if (Array.isArray(lots)) return lots;
        } catch (e) {}
        try {
            var decoded = raw.replace(/&quot;/g, '"').replace(/&amp;/g, '&');
            var lots = JSON.parse(decoded);
            return Array.isArray(lots) ? lots : [];
        } catch (e2) {
            return [];
        }
    }
    function formatLotQty(qty) {
        if (!isFinite(qty)) return '0';
        return String(Math.round(qty));
    }
    function fillLots(row, selectedId) {
        var lotSelect = row.querySelector('.line-lot');
        if (!lotSelect) return;
        var itemSelect = row.querySelector('.line-item');
        var opt = itemSelect && itemSelect.options[itemSelect.selectedIndex];
        var lots = parseLots(opt);
        var keep = selectedId != null ? String(selectedId) : '';
        lotSelect.innerHTML = '';
        var empty = document.createElement('option');
        empty.value = '';
        lotSelect.appendChild(empty);
        var added = 0;
        lots.forEach(function (lot) {
            var qty = parseFloat(lot.qty);
            if (isNaN(qty)) qty = 0;
            if (qty <= 0 && String(lot.id) !== keep) return;
            var option = document.createElement('option');
            option.value = lot.id;
            option.setAttribute('data-batch', lot.batch || '');
            option.setAttribute('data-qty', String(qty));
            option.textContent = table.getAttribute('data-hide-stock-qty') === '1'
                ? (lot.batch || '—')
                : ((lot.batch || '—') + ' — Qty ' + formatLotQty(qty));
            lotSelect.appendChild(option);
            added += 1;
        });
        var fallbackBatch = opt ? (opt.getAttribute('data-batch') || '') : '';
        if (added) {
            empty.textContent = 'Select batch';
        } else if (fallbackBatch) {
            empty.textContent = fallbackBatch;
        } else {
            empty.textContent = itemSelect && itemSelect.value ? 'No batch in stock' : 'Select item first';
        }
        if (keep && lotSelect.querySelector('option[value="' + keep + '"]')) {
            lotSelect.value = keep;
            return;
        }
        var first = lots.find(function (lot) { return (parseFloat(lot.qty) || 0) > 0; }) || lots[0];
        lotSelect.value = first ? String(first.id) : '';
    }
    function applyItem(select) {
        var opt = select.options[select.selectedIndex];
        var price = opt ? opt.getAttribute('data-price') : '';
        var desc = opt ? opt.getAttribute('data-description') : '';
        var row = select.closest('tr');
        var priceInput = row.querySelector('.line-price');
        var descInput = row.querySelector('.line-description');
        if (priceInput && price !== null && price !== '') priceInput.value = price;
        if (descInput) descInput.value = desc || '';
        fillLots(row, '');
    }
    function recalc() {
        var sub = 0, discTotal = 0, vat = 0;
        table.querySelectorAll('.tax-line').forEach(function (row) {
            var qty = parseFloat(row.querySelector('.line-qty').value) || 0;
            var price = parseFloat(row.querySelector('.line-price').value) || 0;
            var discInput = row.querySelector('.line-discount');
            var discRate = discInput ? (parseFloat(discInput.value) || 0) : 0;
            var rateInput = row.querySelector('.line-rate');
            var rate = rateInput ? (parseFloat(rateInput.value) || 0) : 0;
            var gross = qty * price;
            var disc = gross * discRate / 100;
            var net = gross - disc;
            var v = net * rate / 100;
            var vatCell = row.querySelector('.line-vat');
            if (vatCell) vatCell.textContent = money(v);
            row.querySelector('.line-total').textContent = money(net + v);
            sub += gross; discTotal += disc; vat += v;
        });
        document.getElementById('docSubtotal').textContent = money(sub);
        var discEl = document.getElementById('docDiscount');
        if (discEl) discEl.textContent = money(discTotal);
        var vatEl = document.getElementById('docVat');
        if (vatEl) vatEl.textContent = money(vat);
        document.getElementById('docTotal').textContent = money(sub - discTotal + vat);
    }
    table.addEventListener('input', recalc);
    table.addEventListener('change', function (e) {
        var select = e.target.closest ? e.target.closest('select.line-item') : null;
        if (e.target.classList && e.target.classList.contains('line-item')) {
            select = e.target;
        }
        if (select) applyItem(select);
        recalc();
    });
    table.addEventListener('click', function (e) {
        if (e.target.classList.contains('remove-line')) {
            var rows = table.querySelectorAll('.tax-line');
            if (rows.length > 1) {
                var row = e.target.closest('tr');
                if (window.amsDestroySearchSelects) window.amsDestroySearchSelects(row);
                row.remove();
            }
            recalc();
        }
    });
    document.getElementById('addTaxLine').addEventListener('click', function () {
        var i = table.querySelectorAll('.tax-line').length;
        var tr = table.querySelector('.tax-line').cloneNode(true);
        if (window.amsStripChoicesFromClone) window.amsStripChoicesFromClone(tr);
        tr.querySelectorAll('input, select').forEach(function (input) {
            if (input.classList.contains('ams-combo-search') || input.classList.contains('ams-combo-toggle')) {
                return;
            }
            input.name = input.name.replace(/lines\[\d+]/, 'lines[' + i + ']');
            if (input.classList.contains('line-qty')) input.value = '1';
            else if (input.classList.contains('line-price')) input.value = '0';
            else if (input.classList.contains('line-discount')) input.value = '0';
            else if (input.classList.contains('line-rate')) input.value = '{{ $hiddenVat }}';
            else if (input.classList.contains('line-lot')) input.innerHTML = '<option value="">Select item first</option>';
            else if (input.tagName === 'SELECT') input.selectedIndex = 0;
            else input.value = '';
        });
        table.querySelector('tbody').appendChild(tr);
        if (window.amsInitSearchSelects) window.amsInitSearchSelects(tr);
        recalc();
    });
    table.querySelectorAll('.line-item').forEach(function (select) {
        var opt = select.options[select.selectedIndex];
        var row = select.closest('tr');
        var descInput = row.querySelector('.line-description');
        if (descInput && opt && !descInput.value) {
            descInput.value = opt.getAttribute('data-description') || '';
        }
        var lotSelect = row.querySelector('.line-lot');
        fillLots(row, lotSelect ? lotSelect.value : '');
    });
    recalc();
})();
</script>
@endpush
