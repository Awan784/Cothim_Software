@extends('reports.print')

@section('body')
    <table class="items">
        <thead>
            <tr>
                <th>Period</th>
                <th class="num">Cash in</th>
                <th class="num">Cash out</th>
                <th class="num">Cash balance</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ ams_date($fromDate) }} to {{ ams_date($toDate) }}</td>
                <td class="num">{{ ams_num($totalCashIn) }}</td>
                <td class="num">{{ ams_num($totalCashOut) }}</td>
                <td class="num">{{ ams_num($closingBalance) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="section-title">Register</div>
    <table class="items">
        <thead>
            <tr>
                <th>Date</th>
                <th>Voucher</th>
                <th>Transaction</th>
                <th>Party type</th>
                <th>Party / Account</th>
                <th>Paid via</th>
                <th>Reference</th>
                <th>Notes</th>
                <th class="num">Amount</th>
                <th class="num">Cash in</th>
                <th class="num">Cash out</th>
                <th class="num">Balance</th>
            </tr>
        </thead>
        <tbody>
            @forelse($entries as $entry)
                <tr @class([
                    'row-bf' => !empty($entry['is_brought_forward']),
                    'row-receive' => ($entry['type'] ?? '') === 'receive',
                    'row-payment' => ($entry['type'] ?? '') === 'payment',
                    'row-bank' => empty($entry['affects_cash_balance']) && empty($entry['is_brought_forward']),
                ])>
                    <td>{{ $entry['date'] instanceof \Carbon\Carbon ? ams_date($entry['date']) : $entry['date'] }}</td>
                    <td>{{ $entry['voucher_no'] }}</td>
                    <td>{{ $entry['type_label'] }}</td>
                    <td>{{ $entry['party_type'] }}</td>
                    <td>{{ $entry['party_name'] }}</td>
                    <td>{{ $entry['paid_via'] }}</td>
                    <td>{{ $entry['reference'] }}</td>
                    <td>{{ $entry['notes'] }}</td>
                    <td class="num">{{ isset($entry['amount']) && (float) $entry['amount'] > 0 ? ams_num($entry['amount']) : '' }}</td>
                    <td class="num">{{ (float) $entry['cash_in'] > 0 ? ams_num($entry['cash_in']) : '' }}</td>
                    <td class="num">{{ (float) $entry['cash_out'] > 0 ? ams_num($entry['cash_out']) : '' }}</td>
                    <td class="num">{{ ams_num($entry['balance']) }}</td>
                </tr>
            @empty
                <tr class="empty">
                    <td colspan="12">No transactions in this period.</td>
                </tr>
            @endforelse
        </tbody>
        @if($entries->isNotEmpty())
            <tfoot>
                <tr>
                    <td colspan="9">Period total</td>
                    <td class="num">{{ ams_num($totalCashIn) }}</td>
                    <td class="num">{{ ams_num($totalCashOut) }}</td>
                    <td class="num">{{ ams_num($closingBalance) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>
@endsection
