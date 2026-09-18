@extends('reports.print')

@section('body')
    <table class="items">
        <thead>
            <tr>
                <th>Date</th>
                <th>Invoice</th>
                <th>Salesman</th>
                <th>Customer</th>
                <th class="num">Total</th>
                <th class="num">Retain</th>
                <th class="num">Commission</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoices as $invoice)
                <tr>
                    <td>{{ ams_date($invoice->invoice_date) }}</td>
                    <td>{{ $invoice->invoice_no }}</td>
                    <td>{{ $invoice->salesman?->name ?: '—' }}</td>
                    <td>{{ $invoice->customer?->displayName() ?: '—' }}</td>
                    <td class="num">{{ ams_num($invoice->total) }}</td>
                    <td class="num">{{ ams_num($invoice->company_retain_amount) }}</td>
                    <td class="num">{{ ams_num($invoice->salesman_commission_amount) }}</td>
                </tr>
            @empty
                <tr class="empty"><td colspan="7">No salesman invoices in this period.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4">Totals</td>
                <td class="num">{{ ams_num($totalSales) }}</td>
                <td class="num">{{ ams_num($totalRetain) }}</td>
                <td class="num">{{ ams_num($totalCommission) }}</td>
            </tr>
        </tfoot>
    </table>
@endsection
