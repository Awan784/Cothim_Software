@php
    $isEdit = isset($purchaseOrder);
    $oldItems = old('items');
    $items = is_array($oldItems)
        ? $oldItems
        : ($isEdit ? $purchaseOrder->items->map(fn ($i) => [
            'stock_item_id' => $i->stock_item_id,
            'item_name' => $i->item_name,
            'unit' => $i->unit,
            'unit_price' => $i->unit_price,
            'quantity' => $i->quantity,
            'batch_no' => $i->batch_no,
            'manufactured_at' => $i->manufactured_at,
            'expiry_date' => $i->expiry_date,
            'note' => $i->note,
        ])->toArray() : []);
    $partyType = old('party_type', $isEdit ? ($purchaseOrder->party_type ?: 'supplier') : ($suppliers->isEmpty() && $vendors->isNotEmpty() ? 'vendor' : 'supplier'));
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

    <div class="col-md-3 mb-3">
        <label class="form-label">Purchase from</label>
        <select name="party_type" id="po_party_type" class="form-select @error('party_type') is-invalid @enderror" required>
            <option value="supplier" {{ $partyType === 'supplier' ? 'selected' : '' }}>Supplier</option>
            <option value="vendor" {{ $partyType === 'vendor' ? 'selected' : '' }}>Vendor</option>
        </select>
        @error('party_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-5 mb-3" id="po_supplier_wrap">
        <label class="form-label">Supplier</label>
        <select name="supplier_id" id="po_supplier_id" class="form-select @error('supplier_id') is-invalid @enderror">
            <option value="">Select supplier</option>
            @foreach($suppliers as $s)
                <option value="{{ $s->id }}" {{ (string) old('supplier_id', $isEdit ? $purchaseOrder->supplier_id : '') === (string) $s->id ? 'selected' : '' }}>
                    {{ $s->name }}
                </option>
            @endforeach
        </select>
        @error('supplier_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div class="form-text">Adds quantity to inventory.</div>
    </div>
    <div class="col-md-5 mb-3 d-none" id="po_vendor_wrap">
        <label class="form-label">Vendor</label>
        <select name="vendor_id" id="po_vendor_id" class="form-select @error('vendor_id') is-invalid @enderror">
            <option value="">Select vendor</option>
            @foreach($vendors as $v)
                <option value="{{ $v->id }}" {{ (string) old('vendor_id', $isEdit ? $purchaseOrder->vendor_id : '') === (string) $v->id ? 'selected' : '' }}>
                    {{ $v->name }}
                </option>
            @endforeach
        </select>
        @error('vendor_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div class="form-text">Ledger only. Stock is not changed. <a href="{{ route('vendors.create') }}">Add vendor</a></div>
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
            <strong id="poLinesTitle">Inventory items</strong>
            <span class="text-muted small ms-2" id="poLinesHint">A new batch number creates a new lot. The same batch adds qty to that lot.</span>
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
                    <th class="po-lot-col" style="min-width: 110px;">Batch #</th>
                    <th class="po-lot-col" style="min-width: 140px;">Manufacture</th>
                    <th class="po-lot-col" style="min-width: 140px;">Expiry</th>
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
                    <td colspan="8" class="text-end po-total-label"><strong>Total</strong></td>
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

    function partyType() {
        var select = document.getElementById('po_party_type');
        return select ? select.value : 'supplier';
    }

    function setPartyMode() {
        var vendor = partyType() === 'vendor';
        document.getElementById('po_supplier_wrap').classList.toggle('d-none', vendor);
        document.getElementById('po_vendor_wrap').classList.toggle('d-none', !vendor);
        var supplierSelect = document.getElementById('po_supplier_id');
        var vendorSelect = document.getElementById('po_vendor_id');
        supplierSelect.required = !vendor;
        supplierSelect.disabled = vendor;
        vendorSelect.required = vendor;
        vendorSelect.disabled = !vendor;
        document.getElementById('poLinesTitle').textContent = vendor ? 'Vendor items' : 'Inventory items';
        document.getElementById('poLinesHint').textContent = vendor
            ? 'Type the item name. This purchase updates the vendor ledger and does not add stock.'
            : 'A new batch number creates a new lot. The same batch adds qty to that lot.';
        table.querySelectorAll('.po-lot-col').forEach(function (cell) {
            cell.classList.toggle('d-none', vendor);
        });
        table.querySelectorAll('.po-item').forEach(function (el) {
            el.required = !vendor;
            el.disabled = vendor;
            el.classList.toggle('d-none', vendor);
        });
        table.querySelectorAll('.po-on-hand').forEach(function (el) {
            el.classList.toggle('d-none', vendor);
        });
        table.querySelectorAll('.po-manual-name').forEach(function (el) {
            el.classList.toggle('d-none', !vendor);
            el.required = vendor;
            el.disabled = !vendor;
        });
        table.querySelectorAll('.po-unit').forEach(function (el) {
            el.readOnly = !vendor;
        });
        var totalLabel = table.querySelector('.po-total-label');
        if (totalLabel) totalLabel.colSpan = vendor ? 5 : 8;
    }

    function applyItem(row) {
        if (partyType() === 'vendor') return;
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
            var lots = opt.getAttribute('data-lots-summary') || '';
            onHand.textContent = 'On hand: ' + qty + ' ' + String(unit).toUpperCase() + (lots ? ' · ' + lots : '');
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
            var row = e.target.closest('.po-line');
            if (window.amsDestroySearchSelects) window.amsDestroySearchSelects(row);
            row.remove();
            recalc();
        }
    });

    document.getElementById('addPoLine').addEventListener('click', function () {
        var i = table.querySelectorAll('.po-line').length;
        var tr = table.querySelector('.po-line').cloneNode(true);
        if (window.amsStripChoicesFromClone) window.amsStripChoicesFromClone(tr);
        tr.querySelectorAll('input, select').forEach(function (el) {
            if (el.classList.contains('ams-combo-search') || el.classList.contains('ams-combo-toggle')) {
                return;
            }
            el.name = el.name.replace(/items\[\d+]/, 'items[' + i + ']');
            if (el.classList.contains('po-qty')) el.value = '1';
            else if (el.tagName === 'SELECT') el.selectedIndex = 0;
            else el.value = '';
            delete el.dataset.touched;
            delete el.dataset.fpInit;
            if (el._flatpickr) {
                el._flatpickr.destroy();
            }
        });
        tr.querySelector('.po-on-hand').textContent = 'On hand: —';
        tr.querySelector('.po-line-total').textContent = '0.00';
        var err = tr.querySelector('.invalid-feedback');
        if (err) err.remove();
        tr.querySelector('.po-item').classList.remove('is-invalid');
        table.querySelector('tbody').appendChild(tr);
        if (window.amsInitSearchSelects) window.amsInitSearchSelects(tr);
        if (window.amsInitDatePickers) window.amsInitDatePickers(tr);
        setPartyMode();
        recalc();
    });

    document.getElementById('po_party_type').addEventListener('change', setPartyMode);
    table.querySelectorAll('.po-line').forEach(applyItem);
    setPartyMode();
    recalc();
})();
</script>
