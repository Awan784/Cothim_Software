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
            <strong>Items</strong>
            <span class="text-muted small ms-2">Select stock or enter a manual item name</span>
        </div>
        <button type="button" class="btn btn-sm btn-outline-primary" id="addItemBtn">Add Item</button>
    </div>
    <div class="table-responsive">
        <table class="table table-flush mb-0" id="itemsTable">
            <thead class="thead-light">
                <tr>
                    <th style="min-width: 280px;">Stock / Manual Item</th>
                    <th style="min-width: 100px;">Unit</th>
                    <th class="text-end" style="min-width: 120px;">Unit Price</th>
                    <th class="text-end" style="min-width: 100px;">Qty</th>
                    <th style="min-width: 180px;">Note</th>
                    <th class="text-end" style="min-width: 100px;">Line Total</th>
                    <th class="text-end" style="width: 80px;"></th>
                </tr>
            </thead>
            <tbody id="itemsBody">
                @forelse($items as $idx => $row)
                    @include('purchase-orders._row', ['idx' => $idx, 'row' => $row, 'stockItems' => $stockItems])
                @empty
                    @include('purchase-orders._row', ['idx' => 0, 'row' => [], 'stockItems' => $stockItems])
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" class="text-end"><strong>Total</strong></td>
                    <td class="text-end"><strong id="grandTotal">0.00</strong></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

@push('page_scripts')
    <script>
        (function () {
            const items = @json($stockItems->map(fn ($i) => ['id' => $i->id, 'unit' => $i->unit, 'name' => $i->name])->values());
            const unitById = Object.fromEntries(items.map(i => [String(i.id), i.unit || '']));
            const nameById = Object.fromEntries(items.map(i => [String(i.id), i.name || '']));

            const body = document.getElementById('itemsBody');
            const addBtn = document.getElementById('addItemBtn');
            const grandTotalEl = document.getElementById('grandTotal');

            function recalc() {
                let grand = 0;
                body.querySelectorAll('tr[data-row]').forEach((tr) => {
                    const price = parseFloat(tr.querySelector('[data-unit-price]').value || '0');
                    const qty = parseFloat(tr.querySelector('[data-qty]').value || '0');
                    const line = price * qty;
                    tr.querySelector('[data-line-total]').textContent = line.toFixed(2);
                    grand += line;
                });
                grandTotalEl.textContent = grand.toFixed(2);
            }

            function syncManualField(tr) {
                const itemSelect = tr.querySelector('[data-stock-item]');
                const manualInput = tr.querySelector('[data-item-name]');
                const hasStock = !!itemSelect.value;

                if (hasStock) {
                    manualInput.placeholder = 'Optional — overrides stock name';
                    manualInput.removeAttribute('required');
                    if (!manualInput.value.trim()) {
                        manualInput.value = nameById[String(itemSelect.value)] || '';
                    }
                } else {
                    manualInput.placeholder = 'Required — type item name';
                    manualInput.setAttribute('required', 'required');
                }
            }

            function bindRow(tr) {
                const itemSelect = tr.querySelector('[data-stock-item]');
                const unitInput = tr.querySelector('[data-unit]');
                const manualInput = tr.querySelector('[data-item-name]');

                itemSelect.addEventListener('change', () => {
                    const id = itemSelect.value;
                    if (id) {
                        if (!unitInput.value) {
                            unitInput.value = unitById[String(id)] || '';
                        }
                        if (!manualInput.value.trim()) {
                            manualInput.value = nameById[String(id)] || '';
                        }
                    } else {
                        manualInput.value = '';
                    }
                    syncManualField(tr);
                });

                manualInput.addEventListener('input', () => {
                    if (itemSelect.value && manualInput.value.trim() !== (nameById[String(itemSelect.value)] || '')) {
                        // user customized manual name while stock selected — keep both
                    }
                });

                tr.querySelectorAll('input').forEach((i) => {
                    i.addEventListener('input', recalc);
                });

                tr.querySelector('[data-remove]').addEventListener('click', () => {
                    tr.remove();
                    recalc();
                });

                syncManualField(tr);
            }

            body.querySelectorAll('tr[data-row]').forEach(bindRow);
            recalc();

            addBtn.addEventListener('click', () => {
                const idx = body.querySelectorAll('tr[data-row]').length;
                const tpl = document.getElementById('rowTemplate').innerHTML.replaceAll('__INDEX__', String(idx));
                const wrap = document.createElement('tbody');
                wrap.innerHTML = tpl.trim();
                const tr = wrap.querySelector('tr');
                body.appendChild(tr);
                bindRow(tr);
                recalc();
            });
        })();
    </script>

    <script type="text/template" id="rowTemplate">
        @include('purchase-orders._row', ['idx' => '__INDEX__', 'row' => ['stock_item_id' => '', 'item_name' => '', 'unit' => '', 'unit_price' => '', 'quantity' => '', 'note' => ''], 'stockItems' => $stockItems])
    </script>
@endpush
