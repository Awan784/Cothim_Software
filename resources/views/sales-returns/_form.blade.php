@php
    $isEdit = isset($salesReturn);
    $oldItems = old('items');
    $items = is_array($oldItems)
        ? $oldItems
        : ($isEdit ? $salesReturn->items->map(fn ($i) => [
            'stock_item_id' => $i->stock_item_id,
            'unit' => $i->unit,
            'unit_price' => $i->unit_price,
            'quantity' => $i->quantity,
            'note' => $i->note,
        ])->toArray() : []);
@endphp

<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label">Return date</label>
        <x-ams-date-input
            name="return_date"
            :value="$isEdit ? $salesReturn->return_date : now()"
            required
        />
    </div>

    <div class="col-md-8 mb-3">
        <label class="form-label">Customer</label>
        <select name="customer_id" class="form-select @error('customer_id') is-invalid @enderror" required>
            <option value="">Select customer</option>
            @foreach($customers as $c)
                <option value="{{ $c->id }}" {{ (string) old('customer_id', $isEdit ? $salesReturn->customer_id : '') === (string) $c->id ? 'selected' : '' }}>
                    {{ $c->displayName() }}
                </option>
            @endforeach
        </select>
        @error('customer_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="mb-3">
    <label class="form-label">Notes</label>
    <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="2">{{ old('notes', $isEdit ? $salesReturn->notes : '') }}</textarea>
    @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

@error('items')
    <div class="alert alert-danger ams-alert mb-3">{{ $message }}</div>
@enderror

<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <strong>Returned items</strong>
            <span class="text-muted small ms-2">Saving a return adds quantity back to stock</span>
        </div>
        <button type="button" class="btn btn-sm btn-outline-primary" id="addSrLine">Add item</button>
    </div>
    <div class="table-responsive">
        <table class="table table-flush mb-0" id="srLinesTable">
            <thead class="thead-light">
                <tr>
                    <th style="min-width: 280px;">Item</th>
                    <th style="min-width: 90px;">Unit</th>
                    <th class="text-end" style="min-width: 120px;">Rate</th>
                    <th class="text-end" style="min-width: 90px;">Qty</th>
                    <th style="min-width: 160px;">Reason</th>
                    <th class="text-end" style="min-width: 100px;">Line total</th>
                    <th class="text-end" style="width: 80px;"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $idx => $row)
                    @include('sales-returns._row', ['idx' => $idx, 'row' => $row, 'stockItems' => $stockItems])
                @empty
                    @include('sales-returns._row', ['idx' => 0, 'row' => ['quantity' => '1'], 'stockItems' => $stockItems])
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" class="text-end"><strong>Total</strong></td>
                    <td class="text-end"><strong id="srGrandTotal">0</strong></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<script>
(function () {
    var table = document.getElementById('srLinesTable');
    if (!table) return;

    function money(n) {
        var rounded = Math.round(n * 100) / 100;
        return Number.isInteger(rounded) ? String(rounded) : rounded.toFixed(2).replace(/0+$/, '').replace(/\.$/, '');
    }

    function applyItem(row) {
        var select = row.querySelector('.sr-item');
        var opt = select.options[select.selectedIndex];
        var unit = opt ? (opt.getAttribute('data-unit') || '') : '';
        var price = opt ? (opt.getAttribute('data-price') || '') : '';
        var qty = opt ? (opt.getAttribute('data-qty') || '0') : '0';
        var onHand = row.querySelector('.sr-on-hand');
        var unitInput = row.querySelector('.sr-unit');
        var priceInput = row.querySelector('.sr-price');

        if (select.value && opt) {
            unitInput.value = unit;
            if (!priceInput.dataset.touched) {
                priceInput.value = price;
            }
            onHand.textContent = 'On hand: ' + qty + ' ' + String(unit).toUpperCase();
        } else {
            unitInput.value = '';
            onHand.textContent = 'On hand: —';
        }
    }

    function recalc() {
        var grand = 0;
        table.querySelectorAll('.sr-line').forEach(function (row) {
            var price = parseFloat(row.querySelector('.sr-price').value) || 0;
            var qty = parseFloat(row.querySelector('.sr-qty').value) || 0;
            var line = price * qty;
            row.querySelector('.sr-line-total').textContent = money(line);
            grand += line;
        });
        document.getElementById('srGrandTotal').textContent = money(grand);
    }

    table.addEventListener('change', function (e) {
        if (!e.target.classList.contains('sr-item')) return;
        var row = e.target.closest('.sr-line');
        var priceInput = row.querySelector('.sr-price');
        priceInput.dataset.touched = '';
        priceInput.value = '';
        applyItem(row);
        recalc();
    });

    table.addEventListener('input', function (e) {
        if (e.target.classList.contains('sr-price')) {
            e.target.dataset.touched = '1';
        }
        recalc();
    });

    table.addEventListener('click', function (e) {
        if (!e.target.classList.contains('sr-remove')) return;
        if (table.querySelectorAll('.sr-line').length > 1) {
            e.target.closest('.sr-line').remove();
            recalc();
        }
    });

    document.getElementById('addSrLine').addEventListener('click', function () {
        var i = table.querySelectorAll('.sr-line').length;
        var tr = table.querySelector('.sr-line').cloneNode(true);
        tr.querySelectorAll('input, select').forEach(function (el) {
            el.name = el.name.replace(/items\[\d+]/, 'items[' + i + ']');
            if (el.classList.contains('sr-qty')) el.value = '1';
            else if (el.tagName === 'SELECT') el.selectedIndex = 0;
            else el.value = '';
            delete el.dataset.touched;
        });
        tr.querySelector('.sr-on-hand').textContent = 'On hand: —';
        tr.querySelector('.sr-line-total').textContent = '0';
        var err = tr.querySelector('.invalid-feedback');
        if (err) err.remove();
        tr.querySelector('.sr-item').classList.remove('is-invalid');
        table.querySelector('tbody').appendChild(tr);
        recalc();
    });

    table.querySelectorAll('.sr-line').forEach(applyItem);
    recalc();
})();
</script>
