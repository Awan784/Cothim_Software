@extends('reports.print')

@section('body')
    <table class="items">
        <thead>
            <tr>
                <th style="width: 72px;">Code</th>
                <th>Salesman</th>
                <th class="num" style="width: 110px;">Unpaid</th>
                <th class="num" style="width: 120px;">Commission</th>
                <th class="num" style="width: 110px;">Debit</th>
                <th class="num" style="width: 110px;">Credit</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row['code'] }}</td>
                    <td>
                        {{ $row['salesman'] }}
                        @if($row['city'] !== '')
                            <span class="party-type">{{ $row['city'] }}</span>
                        @endif
                    </td>
                    <td class="num">{{ ams_num($row['sales']) }}</td>
                    <td class="num">{{ ams_num($row['commission']) }}</td>
                    <td class="num">{{ (float) $row['debit'] > 0 ? ams_num($row['debit']) : '0' }}</td>
                    <td class="num">{{ (float) $row['credit'] > 0 ? ams_num($row['credit']) : '0' }}</td>
                </tr>
            @empty
                <tr class="empty">
                    <td colspan="6">No salesmen to show.</td>
                </tr>
            @endforelse
        </tbody>
        @if($rows->isNotEmpty())
            <tfoot>
                <tr>
                    <td colspan="2">Total</td>
                    <td class="num">{{ ams_num($totalSales) }}</td>
                    <td class="num">{{ ams_num($totalCommission) }}</td>
                    <td class="num">{{ ams_num($totalDebit) }}</td>
                    <td class="num">{{ ams_num($totalCredit) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>
@endsection
