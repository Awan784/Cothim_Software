@extends('template.layout')
@section('title', $invoice->invoice_no ?: 'Draft invoice')

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <h1 class="h4 mb-1">{{ $invoice->invoice_no ?: 'Draft invoice' }}</h1>
                <p class="mb-0 text-muted">{{ $invoice->customer?->name }} · {{ $invoice->status }} · ZATCA {{ $invoice->zatca_status }}</p>
            </div>
            <div class="d-flex gap-2">
                @if($invoice->isDraft())
                    <a href="{{ route('invoices.edit', $invoice) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                    <form method="post" action="{{ route('invoices.issue', $invoice) }}">
                        @csrf
                        <button class="btn btn-sm btn-primary" type="submit">Issue invoice</button>
                    </form>
                @else
                    <a href="{{ route('invoices.print', $invoice) }}" class="btn btn-sm btn-primary" target="_blank">Print / QR</a>
                    @php
                        $waNumber = $invoice->customer?->phone ?: ($settings->get('whatsapp') ?? config('ams.whatsapp'));
                        $waText = 'Invoice '.($invoice->invoice_no ?: '').' total '.number_format((float) $invoice->total, 2).' SAR. View: '.route('invoices.show', $invoice);
                    @endphp
                    <a class="btn btn-sm btn-success" target="_blank" rel="noopener" href="{{ wafi_whatsapp_url($waText, $waNumber) }}">WhatsApp</a>
                @endif
                <a href="{{ route('invoices.index') }}" class="btn btn-sm btn-secondary">Back</a>
            </div>
        </div>

        <div class="card p-4 mb-3">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th class="text-end">Qty</th>
                            <th class="text-end">Price</th>
                            <th class="text-end">VAT %</th>
                            <th class="text-end">VAT</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoice->lines as $line)
                            <tr>
                                <td>{{ $line->description }}</td>
                                <td class="text-end">{{ number_format((float) $line->quantity, 3) }}</td>
                                <td class="text-end">{{ number_format((float) $line->unit_price, 2) }}</td>
                                <td class="text-end">{{ number_format((float) $line->vat_rate, 2) }}</td>
                                <td class="text-end">{{ number_format((float) $line->vat_amount, 2) }}</td>
                                <td class="text-end">{{ number_format((float) $line->line_total, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr><td colspan="5" class="text-end">Subtotal</td><td class="text-end">{{ number_format((float) $invoice->subtotal, 2) }}</td></tr>
                        <tr><td colspan="5" class="text-end">VAT</td><td class="text-end">{{ number_format((float) $invoice->vat_amount, 2) }}</td></tr>
                        <tr><td colspan="5" class="text-end"><strong>Total</strong></td><td class="text-end"><strong>{{ number_format((float) $invoice->total, 2) }}</strong></td></tr>
                        <tr><td colspan="5" class="text-end">Paid</td><td class="text-end">{{ number_format((float) $invoice->amount_paid, 2) }}</td></tr>
                        <tr><td colspan="5" class="text-end">Due</td><td class="text-end">{{ number_format($invoice->balanceDue(), 2) }}</td></tr>
                    </tfoot>
                </table>
            </div>
        </div>

        @if(! $invoice->isDraft() && $invoice->balanceDue() > 0)
            <div class="card p-4">
                <h2 class="h6">Record payment</h2>
                <form method="post" action="{{ route('invoices.pay', $invoice) }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-3">
                        <label class="form-label">Amount</label>
                        <input name="amount" class="form-control" value="{{ $invoice->balanceDue() }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Method</label>
                        <select name="payment_method" class="form-select">
                            <option value="cash">Cash</option>
                            <option value="bank">Bank</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Bank</label>
                        <select name="bank_account_id" class="form-select">
                            <option value="">—</option>
                            @foreach($banks as $bank)
                                <option value="{{ $bank->id }}">{{ $bank->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary w-100" type="submit">Pay</button>
                    </div>
                </form>
            </div>
        @endif
    </div>
@endsection
