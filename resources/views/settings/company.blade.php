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
                        <label class="form-label">Invoice series</label>
                        <input name="invoice_series" type="number" min="0" step="1" class="form-control @error('invoice_series') is-invalid @enderror" value="{{ old('invoice_series', $settings['invoice_series'] ?? 1000) }}">
                        @error('invoice_series') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">Next invoice number is this plus one. Example: 1000 → Invoice # 1001, 1002, 1003…</div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">WhatsApp</label>
                    <input name="whatsapp" class="form-control" value="{{ old('whatsapp', $settings['whatsapp'] ?? '') }}" placeholder="9665XXXXXXXX">
                    <div class="form-text">Used to send invoices to customers on WhatsApp.</div>
                </div>
                <hr class="my-4">
                <h2 class="h5 mb-1">Invoice print text</h2>
                <p class="text-muted mb-3">Shown at the bottom of sale invoices. Use <code>{company}</code> to insert the company name automatically.</p>
                <div class="mb-3">
                    <label class="form-label">General Warranty</label>
                    <textarea name="print_warranty" rows="5" class="form-control @error('print_warranty') is-invalid @enderror">{{ old('print_warranty', $settings['print_warranty'] ?? '') }}</textarea>
                    @error('print_warranty') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Note</label>
                    <textarea name="print_note" rows="3" class="form-control @error('print_note') is-invalid @enderror">{{ old('print_note', $settings['print_note'] ?? '') }}</textarea>
                    @error('print_note') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">On Behalf</label>
                    <input name="print_on_behalf" class="form-control @error('print_on_behalf') is-invalid @enderror" value="{{ old('print_on_behalf', $settings['print_on_behalf'] ?? '') }}">
                    @error('print_on_behalf') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Print footer</label>
                    <input name="print_developed_by" class="form-control @error('print_developed_by') is-invalid @enderror" value="{{ old('print_developed_by', $settings['print_developed_by'] ?? '') }}">
                    @error('print_developed_by') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div class="form-text">Replaces the old “User: …” line at the bottom of the sale invoice.</div>
                </div>
                <button class="btn btn-primary" type="submit">Save</button>
            </form>
        </div>
    </div>
@endsection
