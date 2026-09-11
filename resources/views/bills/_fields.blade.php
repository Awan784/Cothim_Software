@php
    $isEdit = isset($bill) && $bill;
    $rows = old('lines');
    if (! is_array($rows) && $isEdit) {
        $rows = $bill->lines->map(fn ($l) => [
            'description' => $l->description,
            'quantity' => $l->quantity,
            'unit_price' => $l->unit_price,
            'vat_rate' => $l->vat_rate,
        ])->all();
    }
@endphp
<div class="card p-4 mb-3">
    <div class="row">
        <div class="col-md-5 mb-3">
            <label class="form-label">Supplier</label>
            <select name="supplier_id" class="form-select" required>
                <option value="">Select supplier</option>
                @foreach($suppliers as $supplier)
                    <option value="{{ $supplier->id }}" {{ (string) old('supplier_id', $isEdit ? $bill->supplier_id : '') === (string) $supplier->id ? 'selected' : '' }}>{{ $supplier->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 mb-3">
            <label class="form-label">Bill date</label>
            <x-ams-date-input name="bill_date" :value="$isEdit ? $bill->bill_date : now()" required />
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Due date</label>
            <x-ams-date-input name="due_date" :value="$isEdit ? $bill->due_date : null" />
        </div>
    </div>
    <textarea name="notes" class="form-control" rows="2" placeholder="Notes">{{ old('notes', $isEdit ? $bill->notes : '') }}</textarea>
</div>
@include('partials.tax-lines', ['rows' => $rows ?? [], 'vatRate' => $vatRate])
