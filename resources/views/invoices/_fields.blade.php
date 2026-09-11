@php
    $isEdit = isset($invoice) && $invoice;
    $rows = old('lines');
    if (! is_array($rows) && $isEdit) {
        $rows = $invoice->lines->map(fn ($l) => [
            'stock_item_id' => $l->stock_item_id,
            'description' => $l->description,
            'quantity' => $l->quantity,
            'unit_price' => $l->unit_price,
            'vat_rate' => $l->vat_rate,
        ])->all();
    }
@endphp
<div class="card p-4 mb-3">
    <div class="row">
        <div class="col-md-4 mb-3">
            <label class="form-label">Customer</label>
            <select name="customer_id" class="form-select" required>
                <option value="">Select customer</option>
                @foreach($customers as $customer)
                    <option value="{{ $customer->id }}" {{ (string) old('customer_id', $isEdit ? $invoice->customer_id : '') === (string) $customer->id ? 'selected' : '' }}>
                        {{ $customer->displayName() }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 mb-3">
            <label class="form-label">Invoice date</label>
            <x-ams-date-input name="invoice_date" :value="$isEdit ? $invoice->invoice_date : now()" required />
        </div>
        <div class="col-md-3 mb-3">
            <label class="form-label">Due date</label>
            <x-ams-date-input name="due_date" :value="$isEdit ? $invoice->due_date : null" />
        </div>
        <div class="col-md-2 mb-3">
            <label class="form-label">Type</label>
            <select name="type" class="form-select">
                <option value="simplified" {{ old('type', $isEdit ? $invoice->type : 'simplified') === 'simplified' ? 'selected' : '' }}>Simplified (B2C)</option>
                <option value="standard" {{ old('type', $isEdit ? $invoice->type : '') === 'standard' ? 'selected' : '' }}>Standard (B2B)</option>
            </select>
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
    ])
