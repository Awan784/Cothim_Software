@extends('template.layout')
@section('title', $bill->bill_no ?: 'Draft bill')

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <h1 class="h4 mb-1">{{ $bill->bill_no ?: 'Draft bill' }}</h1>
                <p class="mb-0 text-muted">{{ $bill->supplier?->name }} · {{ $bill->status }}</p>
            </div>
            <div class="d-flex gap-2">
                @if($bill->isDraft())
                    <a href="{{ route('bills.edit', $bill) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                    <form method="post" action="{{ route('bills.post', $bill) }}">@csrf<button class="btn btn-sm btn-primary">Post bill</button></form>
                @endif
                <a href="{{ route('bills.index') }}" class="btn btn-sm btn-secondary">Back</a>
            </div>
        </div>
        <div class="card p-4 mb-3">
            <table class="table">
                <thead><tr><th>Description</th><th class="text-end">Qty</th><th class="text-end">Tax</th><th class="text-end">Total</th></tr></thead>
                <tbody>
                    @foreach($bill->lines as $line)
                        <tr>
                            <td>{{ $line->description }}</td>
                            <td class="text-end">{{ number_format((float) $line->quantity, 3) }}</td>
                            <td class="text-end">{{ number_format((float) $line->vat_amount, 2) }}</td>
                            <td class="text-end">{{ number_format((float) $line->line_total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr><td colspan="3" class="text-end">Subtotal</td><td class="text-end">{{ number_format((float) $bill->subtotal, 2) }}</td></tr>
                    <tr><td colspan="3" class="text-end">Tax</td><td class="text-end">{{ number_format((float) $bill->vat_amount, 2) }}</td></tr>
                    <tr><td colspan="3" class="text-end"><strong>Total</strong></td><td class="text-end"><strong>{{ number_format((float) $bill->total, 2) }}</strong></td></tr>
                    <tr><td colspan="3" class="text-end">Due</td><td class="text-end">{{ number_format($bill->balanceDue(), 2) }}</td></tr>
                </tfoot>
            </table>
        </div>
        @if(! $bill->isDraft() && $bill->balanceDue() > 0)
            <div class="card p-4">
                <h2 class="h6">Record payment</h2>
                <form method="post" action="{{ route('bills.pay', $bill) }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-3"><label class="form-label">Amount</label><input name="amount" class="form-control" value="{{ $bill->balanceDue() }}" required></div>
                    <div class="col-md-3"><label class="form-label">Method</label><select name="payment_method" class="form-select"><option value="cash">Cash</option><option value="bank">Bank</option></select></div>
                    <div class="col-md-4"><label class="form-label">Bank</label><select name="bank_account_id" class="form-select"><option value="">—</option>@foreach($banks as $bank)<option value="{{ $bank->id }}">{{ $bank->name }}</option>@endforeach</select></div>
                    <div class="col-md-2"><button class="btn btn-primary w-100">Pay</button></div>
                </form>
            </div>
        @endif
    </div>
@endsection
