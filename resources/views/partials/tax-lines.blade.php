@php
    $rows = $rows ?? [];
    $vatRate = $vatRate ?? 15;
    $stockItems = $stockItems ?? collect();
    $useItemSelect = ! empty($useItemSelect);
@endphp
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong>Lines</strong>
        <button type="button" class="btn btn-sm btn-outline-primary" id="addTaxLine">Add line</button>
    </div>
    @if($useItemSelect && $stockItems->isEmpty())
        <div class="px-3 pt-3">
            <p class="small text-muted mb-0">No active stock items yet. <a href="{{ route('stock-items.create') }}">Add an item</a> first, then select it here.</p>
        </div>
    @endif
    <div class="table-responsive">
        <table class="table table-flush mb-0" id="taxLinesTable">
            <thead class="thead-light">
                <tr>
                    <th>{{ $useItemSelect ? 'Item' : 'Description' }}</th>
                    <th class="text-end">Qty</th>
                    <th class="text-end">Unit price</th>
                    <th class="text-end">VAT %</th>
                    <th class="text-end">VAT</th>
                    <th class="text-end">Total</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $i => $row)
                    <tr class="tax-line">
                        <td>
                            @include('partials.tax-line-description', ['index' => $i, 'selected' => $row['description'] ?? '', 'useItemSelect' => $useItemSelect, 'stockItems' => $stockItems])
                        </td>
                        <td><input name="lines[{{ $i }}][quantity]" class="form-control form-control-sm text-end line-qty" value="{{ $row['quantity'] ?? 1 }}"></td>
                        <td><input name="lines[{{ $i }}][unit_price]" class="form-control form-control-sm text-end line-price" value="{{ $row['unit_price'] ?? 0 }}"></td>
                        <td><input name="lines[{{ $i }}][vat_rate]" class="form-control form-control-sm text-end line-rate" value="{{ $row['vat_rate'] ?? $vatRate }}"></td>
                        <td class="text-end line-vat">0.00</td>
                        <td class="text-end line-total">0.00</td>
                        <td><button type="button" class="btn btn-sm btn-outline-danger remove-line">×</button></td>
                    </tr>
                @empty
                    <tr class="tax-line">
                        <td>
                            @include('partials.tax-line-description', ['index' => 0, 'selected' => '', 'useItemSelect' => $useItemSelect, 'stockItems' => $stockItems])
                        </td>
                        <td><input name="lines[0][quantity]" class="form-control form-control-sm text-end line-qty" value="1"></td>
                        <td><input name="lines[0][unit_price]" class="form-control form-control-sm text-end line-price" value="0"></td>
                        <td><input name="lines[0][vat_rate]" class="form-control form-control-sm text-end line-rate" value="{{ $vatRate }}"></td>
                        <td class="text-end line-vat">0.00</td>
                        <td class="text-end line-total">0.00</td>
                        <td><button type="button" class="btn btn-sm btn-outline-danger remove-line">×</button></td>
                    </tr>
                @endempty
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="text-end">Subtotal</td>
                    <td></td>
                    <td class="text-end" id="docSubtotal">0.00</td>
                    <td></td>
                </tr>
                <tr>
                    <td colspan="4" class="text-end">VAT</td>
                    <td></td>
                    <td class="text-end" id="docVat">0.00</td>
                    <td></td>
                </tr>
                <tr>
                    <td colspan="4" class="text-end"><strong>Total</strong></td>
                    <td></td>
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
    function applyItemPrice(select) {
        var opt = select.options[select.selectedIndex];
        var price = opt ? opt.getAttribute('data-price') : '';
        var priceInput = select.closest('tr').querySelector('.line-price');
        if (priceInput && price !== null && price !== '') priceInput.value = price;
    }
    function recalc() {
        var sub = 0, vat = 0;
        table.querySelectorAll('.tax-line').forEach(function (row) {
            var qty = parseFloat(row.querySelector('.line-qty').value) || 0;
            var price = parseFloat(row.querySelector('.line-price').value) || 0;
            var rate = parseFloat(row.querySelector('.line-rate').value) || 0;
            var net = qty * price;
            var v = net * rate / 100;
            row.querySelector('.line-vat').textContent = money(v);
            row.querySelector('.line-total').textContent = money(net + v);
            sub += net; vat += v;
        });
        document.getElementById('docSubtotal').textContent = money(sub);
        document.getElementById('docVat').textContent = money(vat);
        document.getElementById('docTotal').textContent = money(sub + vat);
    }
    table.addEventListener('input', recalc);
    table.addEventListener('change', function (e) {
        if (e.target.classList.contains('line-item')) applyItemPrice(e.target);
        recalc();
    });
    table.addEventListener('click', function (e) {
        if (e.target.classList.contains('remove-line')) {
            var rows = table.querySelectorAll('.tax-line');
            if (rows.length > 1) e.target.closest('tr').remove();
            recalc();
        }
    });
    document.getElementById('addTaxLine').addEventListener('click', function () {
        var i = table.querySelectorAll('.tax-line').length;
        var tr = table.querySelector('.tax-line').cloneNode(true);
        tr.querySelectorAll('input, select').forEach(function (input) {
            input.name = input.name.replace(/lines\[\d+]/, 'lines[' + i + ']');
            if (input.classList.contains('line-qty')) input.value = '1';
            else if (input.classList.contains('line-price')) input.value = '0';
            else if (input.classList.contains('line-rate')) input.value = '{{ $vatRate }}';
            else if (input.tagName === 'SELECT') input.selectedIndex = 0;
            else input.value = '';
        });
        table.querySelector('tbody').appendChild(tr);
        recalc();
    });
    recalc();
})();
</script>
@endpush
