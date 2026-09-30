@php
    $isEdit = isset($invoice) && $invoice;
    $rows = old('lines');
    if (! is_array($rows) && $isEdit) {
        $rows = $invoice->lines->map(fn ($l) => [
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
        <div class="col-md-4 mb-3">
            <label class="form-label">Customer</label>
            <select name="customer_id" class="form-select ams-search-select" data-ams-search="1" data-search-placeholder="Search customer" required>
                <option value="">Select customer</option>
                @foreach($customers as $customer)
                    <option value="{{ $customer->id }}" {{ (string) old('customer_id', $isEdit ? $invoice->customer_id : '') === (string) $customer->id ? 'selected' : '' }}>
                        {{ $customer->displayName() }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Invoice date</label>
            <x-ams-date-input name="invoice_date" :value="$isEdit ? $invoice->invoice_date : now()" required />
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Salesman</label>
            <select name="salesman_id" class="form-select ams-search-select" data-ams-search="1" data-search-placeholder="Search salesman">
                <option value="">Select salesman</option>
                @foreach($salesmen ?? [] as $salesman)
                    <option value="{{ $salesman->id }}" {{ (string) old('salesman_id', $isEdit ? $invoice->salesman_id : '') === (string) $salesman->id ? 'selected' : '' }}>
                        {{ $salesman->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Builty Postal</label>
            <input name="builty_postal" class="form-control" maxlength="100" value="{{ old('builty_postal', $isEdit ? $invoice->builty_postal : '') }}" placeholder="e.g. 23299048">
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Builty Exp</label>
            <input name="builty_exp" type="number" min="0" step="0.01" class="form-control" value="{{ old('builty_exp', $isEdit ? $invoice->builty_exp : '') }}" placeholder="0.00">
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Mode <span class="text-muted fw-normal">(optional)</span></label>
            <input name="mode" class="form-control" maxlength="50" value="{{ old('mode', $isEdit ? $invoice->mode : '') }}" placeholder="CO">
        </div>
    </div>
    <div class="mb-0">
        <label class="form-label">Notes</label>
        <textarea name="notes" class="form-control" rows="2">{{ old('notes', $isEdit ? $invoice->notes : '') }}</textarea>
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
    ])
