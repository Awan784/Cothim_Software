@extends('template.layout')
@section('title', 'Company & tax')

@section('content')
    <div class="pb-4">
        <div class="py-4">
            <h1 class="h4 mb-1">Company & tax</h1>
            <p class="mb-0 text-muted">Used on tax invoices and the ZATCA Phase 1 QR.</p>
        </div>
        <div class="card p-4">
            <form method="post" action="{{ route('settings.company.update') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Company name</label>
                    <input name="company_name" class="form-control" required value="{{ old('company_name', $settings['company_name'] ?? '') }}">
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
                    <label class="form-label">Address</label>
                    <textarea name="company_address" class="form-control" rows="3">{{ old('company_address', $settings['company_address'] ?? '') }}</textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">ZATCA environment</label>
                    <select name="zatca_environment" class="form-select">
                        <option value="sandbox" {{ ($settings['zatca_environment'] ?? '') === 'sandbox' ? 'selected' : '' }}>Sandbox</option>
                        <option value="production" {{ ($settings['zatca_environment'] ?? '') === 'production' ? 'selected' : '' }}>Production (Phase 2 later)</option>
                    </select>
                    <div class="form-text">Phase 1 QR is generated on issue. Fatoora clearance/reporting can be connected next using this environment.</div>
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
