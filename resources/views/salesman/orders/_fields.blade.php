@php
    $isEdit = isset($order) && $order;
    $rows = old('lines');
    if (! is_array($rows) && $isEdit) {
        $rows = $order->lines->map(fn ($l) => [
            'stock_item_id' => $l->stock_item_id,
            'stock_item_lot_id' => $l->stock_item_lot_id,
            'batch_no' => $l->batch_no,
            'description' => $l->description,
            'quantity' => $l->quantity,
            'unit_price' => $l->unit_price,
            'discount_rate' => $l->discount_rate,
            'vat_rate' => $l->vat_rate,
        ])->all();
    }
@endphp
<div class="card p-4 mb-3">
    <div class="row">
        <div class="col-md-8 mb-3">
            <label class="form-label">Customer</label>
            <select name="customer_id" class="form-select ams-search-select" data-ams-search="1" data-search-placeholder="Search customer" required>
                <option value="">Select customer</option>
                @foreach($customers as $customer)
                    <option value="{{ $customer->id }}" {{ (string) old('customer_id', $isEdit ? $order->customer_id : '') === (string) $customer->id ? 'selected' : '' }}>
                        {{ $customer->displayName() }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Order date</label>
            <x-ams-date-input name="order_date" :value="$isEdit ? $order->order_date : now()" required />
        </div>
    </div>
    <div class="mb-0">
        <label class="form-label">Notes</label>
        <textarea name="notes" class="form-control" rows="2">{{ old('notes', $isEdit ? $order->notes : '') }}</textarea>
    </div>
</div>
@include('partials.tax-lines', [
    'rows' => $rows ?? [],
    'vatRate' => $vatRate,
    'stockItems' => $stockItems ?? collect(),
    'useItemSelect' => true,
    'showDiscount' => true,
    'showTax' => false,
    'showBatch' => true,
    'hideStockQty' => true,
])
<div class="card p-4 mb-3">
    <div class="mb-0">
        <label class="form-label">Mode <span class="text-muted fw-normal">(optional)</span></label>
        <input name="mode" class="form-control" maxlength="50" value="{{ old('mode', $isEdit ? $order->mode : '') }}" placeholder="CO">
        <div class="form-text">Printed on the sale invoice as Mode. Leave blank if not needed.</div>
    </div>
</div>
