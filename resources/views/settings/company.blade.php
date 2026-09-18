@extends('template.layout')
@section('title', 'Company & tax')

@section('content')
    <div class="pb-4">
        <div class="py-4">
            <h1 class="h4 mb-1">Company & tax</h1>
            <p class="mb-0 text-muted">Logo, phone, and address appear on invoices, reports, and other print sheets.</p>
        </div>
        <div class="card p-4">
            <form method="post" action="{{ route('settings.company.update') }}" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Company name</label>
                    <input name="company_name" class="form-control @error('company_name') is-invalid @enderror" required value="{{ old('company_name', $settings['company_name'] ?? '') }}">
                    @error('company_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="row">
                    <div class="col-md-5 mb-3">
                        <label class="form-label">Company logo</label>
                        @if($logoUrl)
                            <div class="d-flex align-items-center gap-3 mb-2 p-2 border rounded bg-light">
                                <img src="{{ $logoUrl }}" alt="Company logo" style="height: 56px; width: auto; max-width: 140px; object-fit: contain;">
                                <label class="form-check mb-0">
                                    <input type="checkbox" name="remove_logo" value="1" class="form-check-input">
                                    <span class="form-check-label">Remove logo</span>
                                </label>
                            </div>
                        @endif
                        <input type="file" name="company_logo" accept="image/png,image/jpeg,image/webp" class="form-control @error('company_logo') is-invalid @enderror">
                        @error('company_logo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">PNG, JPG or WebP. Shown on invoices, reports, and print PDFs.</div>
                    </div>
                    <div class="col-md-7 mb-3">
                        <label class="form-label">Phone</label>
                        <input name="company_phone" class="form-control @error('company_phone') is-invalid @enderror" value="{{ old('company_phone', $settings['company_phone'] ?? '') }}" placeholder="+92 300 0000000">
                        @error('company_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">Printed under the company name on every document.</div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Address</label>
                    <textarea name="company_address" class="form-control @error('company_address') is-invalid @enderror" rows="3">{{ old('company_address', $settings['company_address'] ?? '') }}</textarea>
                    @error('company_address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">VAT number</label>
                        <input name="company_vat_number" class="form-control" value="{{ old('company_vat_number', $settings['company_vat_number'] ?? '') }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">CR number</label>
                        <input name="company_cr_number" class="form-control" value="{{ old('company_cr_number', $settings['company_cr_number'] ?? '') }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Default Tax %</label>
                        <input name="default_vat_rate" class="form-control" value="{{ old('default_vat_rate', $settings['default_vat_rate'] ?? 15) }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Company retain %</label>
                        <input name="company_retain_percent" class="form-control" value="{{ old('company_retain_percent', $settings['company_retain_percent'] ?? 50) }}">
                        <div class="form-text">Share of each confirmed salesman invoice the company keeps first. Default 50%.</div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Salesman commission %</label>
                        <input name="salesman_commission_percent" class="form-control" value="{{ old('salesman_commission_percent', $settings['salesman_commission_percent'] ?? 25) }}">
                        <div class="form-text">Percent of the remaining amount after retain. Example: 1000 at 50/25 → retain 500, commission 125. Override per salesman if needed.</div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">WhatsApp</label>
                    <input name="whatsapp" class="form-control" value="{{ old('whatsapp', $settings['whatsapp'] ?? '') }}" placeholder="9665XXXXXXXX">
                    <div class="form-text">Used to send invoices to customers on WhatsApp.</div>
                </div>
                <button class="btn btn-primary" type="submit">Save</button>
            </form>
        </div>
    </div>
@endsection
