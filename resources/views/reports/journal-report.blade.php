@extends('reports.print')

@section('body')
    <table class="items">
        <thead>
            <tr>
                <th>Period</th>
                <th class="num">Vouchers</th>
                <th class="num">Lines</th>
                <th class="num">Total debit</th>
                <th class="num">Total credit</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ ams_date($fromDate) }} to {{ ams_date($toDate) }}</td>
                <td class="num">{{ ams_num($voucherCount) }}</td>
                <td class="num">{{ ams_num($lineCount) }}</td>
                <td class="num">{{ ams_num($grandTotalDebit) }}</td>
                <td class="num">{{ ams_num($grandTotalCredit) }}</td>
            </tr>
        </tbody>
    </table>

    @forelse($vouchers as $voucher)
        <div class="section-title">
            {{ $voucher['date'] instanceof \Carbon\Carbon ? ams_date($voucher['date']) : $voucher['date'] }}
            · {{ $voucher['voucher_no'] }}
            @if(!empty($voucher['notes']))
                · {{ $voucher['notes'] }}
            @endif
        </div>
        <table class="items">
            <thead>
                <tr>
                    <th>Account type</th>
                    <th>Account name</th>
                    <th>Narration</th>
                    <th class="num">Debit</th>
                    <th class="num">Credit</th>
                </tr>
            </thead>
            <tbody>
                @foreach($voucher['lines'] as $line)
                    <tr>
                        <td>{{ $line['account_type'] }}</td>
                        <td>{{ $line['account_name'] }}</td>
                        <td>{{ $line['narration'] }}</td>
                        <td class="num">{{ $line['debit'] > 0 ? ams_num($line['debit']) : '' }}</td>
                        <td class="num">{{ $line['credit'] > 0 ? ams_num($line['credit']) : '' }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3">Voucher total</td>
                    <td class="num">{{ ams_num($voucher['total_debit']) }}</td>
                    <td class="num">{{ ams_num($voucher['total_credit']) }}</td>
                </tr>
            </tfoot>
        </table>
    @empty
        <table class="items">
            <tbody>
                <tr class="empty"><td>No journal vouchers in this period.</td></tr>
            </tbody>
        </table>
    @endforelse

    @if($vouchers->isNotEmpty())
        <div class="section-title">Grand total</div>
        <table class="items">
            <thead>
                <tr>
                    <th>Vouchers</th>
                    <th class="num">Debit</th>
                    <th class="num">Credit</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $voucherCount }} voucher{{ $voucherCount === 1 ? '' : 's' }}</td>
                    <td class="num">{{ ams_num($grandTotalDebit) }}</td>
                    <td class="num">{{ ams_num($grandTotalCredit) }}</td>
                </tr>
            </tbody>
        </table>
    @endif
@endsection
