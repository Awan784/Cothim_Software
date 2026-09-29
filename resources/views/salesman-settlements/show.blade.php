@extends('template.layout')
@section('title', $settlement->settlement_no)

@section('content')
    <div class="pb-4">
        <div class="py-4 d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <h1 class="h4 mb-1">{{ $settlement->settlement_no }}</h1>
                <p class="mb-0 text-muted">
                    {{ $settlement->salesman?->name }} · {{ ams_date($settlement->settlement_date) }}
                    · {{ strtoupper($settlement->payment_method) }}
                </p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('salesman-settlements.index') }}" class="btn btn-sm btn-secondary">Back</a>
                <form method="post" action="{{ route('salesman-settlements.destroy', $settlement) }}" onsubmit="return confirm('Delete this settlement? Invoice payments and the cash voucher will be reversed.');">
                    @csrf
                    @method('delete')
                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                </form>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <div class="card p-3">
                    <div class="small text-muted">Cash received</div>
                    <div class="fs-5 fw-semibold">{{ number_format((float) $settlement->cash_received, 2) }}</div>
                    @if($settlement->cashVoucher)
                        <a class="small" href="{{ route('cash-vouchers.print', $settlement->cashVoucher) }}" target="_blank">{{ $settlement->cashVoucher->voucher_no }}</a>
                    @endif
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-3">
                    <div class="small text-muted">Allocated to customers</div>
                    <div class="fs-5 fw-semibold">{{ number_format((float) $settlement->allocated_amount, 2) }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-3">
                    <div class="small text-muted">Opening advance</div>
                    <div class="fs-5 fw-semibold">{{ number_format((float) $settlement->opening_advance, 2) }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-3">
                    <div class="small text-muted">Advance after this settlement</div>
                    <div class="fs-5 fw-semibold">{{ number_format((float) $settlement->closing_advance, 2) }}</div>
                </div>
            </div>
        </div>

        @if($settlement->notes)
            <p class="text-muted">{{ $settlement->notes }}</p>
        @endif

        <div class="card">
            <div class="card-body border-bottom">
                <h2 class="h6 mb-0">Customer receive payments</h2>
                <p class="small text-muted mb-0">These vouchers verify each customer receipt. They do not add cash a second time.</p>
            </div>
            <div class="table-responsive py-3">
                <table class="table table-flush mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Invoice</th>
                            <th>Customer</th>
                            <th>Voucher</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($settlement->allocations as $allocation)
                            <tr>
                                <td>
                                    @if($allocation->invoice)
                                        <a href="{{ route('invoices.show', $allocation->invoice) }}">{{ $allocation->invoice->invoice_no }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ $allocation->customer?->displayName() ?: '—' }}</td>
                                <td>
                                    @if($allocation->cashVoucher)
                                        <a href="{{ route('cash-vouchers.print', $allocation->cashVoucher) }}" target="_blank">{{ $allocation->cashVoucher->voucher_no }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="text-end">{{ number_format((float) $allocation->amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">No customer allocations — the full cash received is held as salesman advance.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
