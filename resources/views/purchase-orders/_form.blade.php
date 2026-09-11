@php
    $isEdit = isset($purchaseOrder);
    $oldItems = old('items');
    $items = is_array($oldItems)
        ? $oldItems
        : ($isEdit ? $purchaseOrder->items->map(fn ($i) => [
            'stock_item_id' => $i->stock_item_id,
            'unit' => $i->unit,
            'unit_price' => $i->unit_price,
            'quantity' => $i->quantity,
            'note' => $i->note,
        ])->toArray() : []);
@endphp

<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label">PO Date</label>
        <x-ams-date-input
            name="po_date"
            :value="$isEdit ? $purchaseOrder->po_date : now()"
            required
        />
    </div>

    <div class="col-md-8 mb-3">
        <label class="form-label">Supplier</label>
        <select name="supplier_id" class="form-select @error('supplier_id') is-invalid @enderror" required>
            <option value="">Select supplier</option>
            @foreach($suppliers as $s)
                <option value="{{ $s->id }}" {{ (string) old('supplier_id', $isEdit ? $purchaseOrder->supplier_id : '') === (string) $s->id ? 'selected' : '' }}>
                    {{ $s->name }}
                </option>
            @endforeach
        </select>
        @error('supplier_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="mb-3">
    <label class="form-label">Notes</label>
    <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="2">{{ old('notes', $isEdit ? $purchaseOrder->notes : '') }}</textarea>
    @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

@error('items')
    <div class="alert alert-danger ams-alert mb-3">{{ $message }}</div>
@enderror

<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <strong>Inventory items</strong>
            <span class="text-muted small ms-2">Saving a purchase adds quantity to stock</span>
        </div>
        <button type="button" class="btn btn-sm btn-outline-primary" id="addPoLine">Add item</button>
    </div>
    <div class="table-responsive">
        <table class="table table-flush mb-0" id="poLinesTable">
            <thead class="thead-light">
                <tr>
                    <th style="min-width: 280px;">Item</th>
                    <th style="min-width: 90px;">Unit</th>
                    <th class="text-end" style="min-width: 120px;">Cost</th>
                    <th class="text-end" style="min-width: 90px;">Qty</th>
                    <th style="min-width: 160px;">Note</th>
                    <th class="text-end" style="min-width: 100px;">Line total</th>
                    <th class="text-end" style="width: 80px;"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $idx => $row)
                    @include('purchase-orders._row', ['idx' => $idx, 'row' => $row, 'stockItems' => $stockItems])
                @empty
                    @include('purchase-orders._row', ['idx' => 0, 'row' => ['quantity' => '1'], 'stockItems' => $stockItems])
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" class="text-end"><strong>Total</strong></td>
                    <td class="text-end"><strong id="poGrandTotal">0.00</strong></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<script>
(function () {
    var table = document.getElementById('poLinesTable');
    if (!table) return;

    function money(n) {
        return (Math.round(n * 100) / 100).toFixed(2);
    }

    function applyItem(row) {
        var select = row.querySelector('.po-item');
        var opt = select.options[select.selectedIndex];
        var unit = opt ? (opt.getAttribute('data-unit') || '') : '';
        var cost = opt ? (opt.getAttribute('data-cost') || '') : '';
        var qty = opt ? (opt.getAttribute('data-qty') || '0') : '0';
        var onHand = row.querySelector('.po-on-hand');
        var unitInput = row.querySelector('.po-unit');
        var priceInput = row.querySelector('.po-price');

        if (select.value && opt) {
            unitInput.value = unit;
            if (!priceInput.dataset.touched) {
                priceInput.value = cost;
            }
            onHand.textContent = 'On hand: ' + qty + ' ' + String(unit).toUpperCase();
        } else {
            unitInput.value = '';
            onHand.textContent = 'On hand: —';
        }
    }

    function recalc() {
        var grand = 0;
        table.querySelectorAll('.po-line').forEach(function (row) {
            var price = parseFloat(row.querySelector('.po-price').value) || 0;
            var qty = parseFloat(row.querySelector('.po-qty').value) || 0;
            var line = price * qty;
            row.querySelector('.po-line-total').textContent = money(line);
            grand += line;
        });
        document.getElementById('poGrandTotal').textContent = money(grand);
    }

    table.addEventListener('change', function (e) {
        if (!e.target.classList.contains('po-item')) return;
        var row = e.target.closest('.po-line');
        var priceInput = row.querySelector('.po-price');
        priceInput.dataset.touched = '';
        priceInput.value = '';
        applyItem(row);
        recalc();
    });

    table.addEventListener('input', function (e) {
        if (e.target.classList.contains('po-price')) {
            e.target.dataset.touched = '1';
        }
        recalc();
    });

    table.addEventListener('click', function (e) {
        if (!e.target.classList.contains('po-remove')) return;
        var rows = table.querySelectorAll('.po-line');
        if (rows.length > 1) {
            e.target.closest('.po-line').remove();
            recalc();
        }
    });

    document.getElementById('addPoLine').addEventListener('click', function () {
        var i = table.querySelectorAll('.po-line').length;
        var tr = table.querySelector('.po-line').cloneNode(true);
        tr.querySelectorAll('input, select').forEach(function (el) {
            el.name = el.name.replace(/items\[\d+]/, 'items[' + i + ']');
            if (el.classList.contains('po-qty')) el.value = '1';
            else if (el.tagName === 'SELECT') el.selectedIndex = 0;
            else el.value = '';
            delete el.dataset.touched;
        });
        tr.querySelector('.po-on-hand').textContent = 'On hand: —';
        tr.querySelector('.po-line-total').textContent = '0.00';
        var err = tr.querySelector('.invalid-feedback');
        if (err) err.remove();
        tr.querySelector('.po-item').classList.remove('is-invalid');
        table.querySelector('tbody').appendChild(tr);
        recalc();
    });

    table.querySelectorAll('.po-line').forEach(applyItem);
    recalc();
})();
</script>
