@extends('template.layout')
@section('title', $invoice->invoice_no ?: 'Sales invoice')

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <h1 class="h4 mb-1">{{ $invoice->invoice_no ?: 'Sales invoice' }}</h1>
                <p class="mb-0 text-muted">{{ $invoice->customer?->name }} · {{ $invoice->status }}</p>
            </div>
            <div class="d-flex gap-2">
                @if(! $invoice->isDraft())
                    <a href="{{ route('invoices.print', $invoice) }}" class="btn btn-sm btn-primary" target="_blank">Print</a>
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
                            <th class="text-end">Disc %</th>
                            <th class="text-end">Tax %</th>
                            <th class="text-end">Tax</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoice->lines as $line)
                            <tr>
                                <td>{{ $line->description }}</td>
                                <td class="text-end">{{ number_format((float) $line->quantity, 3) }}</td>
                                <td class="text-end">{{ number_format((float) $line->unit_price, 2) }}</td>
                                <td class="text-end">{{ number_format((float) $line->discount_rate, 2) }}%</td>
                                <td class="text-end">{{ number_format((float) $line->vat_rate, 2) }}%</td>
                                <td class="text-end">{{ number_format((float) $line->vat_amount, 2) }}</td>
                                <td class="text-end">{{ number_format((float) $line->line_total, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr><td colspan="6" class="text-end">Subtotal</td><td class="text-end">{{ number_format((float) $invoice->subtotal + (float) $invoice->discount_amount, 2) }}</td></tr>
                        @if((float) $invoice->discount_amount > 0)
                            <tr><td colspan="6" class="text-end">Discount</td><td class="text-end">{{ number_format((float) $invoice->discount_amount, 2) }}</td></tr>
                        @endif
                        <tr><td colspan="6" class="text-end">Tax</td><td class="text-end">{{ number_format((float) $invoice->vat_amount, 2) }}</td></tr>
                        <tr><td colspan="6" class="text-end"><strong>Total</strong></td><td class="text-end"><strong>{{ number_format((float) $invoice->total, 2) }}</strong></td></tr>
                        @if($invoice->salesman_id)
                            <tr><td colspan="6" class="text-end">Salesman</td><td class="text-end">{{ $invoice->salesman?->name ?: '—' }}</td></tr>
                            <tr><td colspan="6" class="text-end">Company retain ({{ number_format((float) $invoice->company_retain_percent, 2) }}%)</td><td class="text-end">{{ number_format((float) $invoice->company_retain_amount, 2) }}</td></tr>
                            <tr><td colspan="6" class="text-end">Commission ({{ number_format((float) $invoice->salesman_commission_percent, 2) }}% of remaining)</td><td class="text-end">{{ number_format((float) $invoice->salesman_commission_amount, 2) }}</td></tr>
                        @endif
                        <tr><td colspan="6" class="text-end">Paid</td><td class="text-end">{{ number_format((float) $invoice->amount_paid, 2) }}</td></tr>
                        <tr><td colspan="6" class="text-end">Due</td><td class="text-end">{{ number_format($invoice->balanceDue(), 2) }}</td></tr>
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
