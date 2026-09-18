@extends('reports.print')

@section('body')
    <table class="items">
        <thead>
            <tr>
                <th>Summary</th>
                <th class="num">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr><td>Sales invoices</td><td class="num">{{ ams_num($totals['sales']) }}</td></tr>
            <tr><td>Sales orders</td><td class="num">{{ ams_num($totals['orders']) }}</td></tr>
            <tr><td>Purchases</td><td class="num">{{ ams_num($totals['purchases']) }}</td></tr>
            <tr><td>Expenses</td><td class="num">{{ ams_num($totals['expenses']) }}</td></tr>
            <tr><td>Cash in</td><td class="num">{{ ams_num($totals['cash_in']) }}</td></tr>
            <tr><td>Cash out</td><td class="num">{{ ams_num($totals['cash_out']) }}</td></tr>
        </tbody>
        <tfoot>
            <tr>
                <td>Net cash</td>
                <td class="num">{{ ams_num($totals['net_cash']) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="section-title">Daily activity</div>
    <table class="items">
        <thead>
            <tr>
                <th>Date</th>
                <th class="num">Sales invoices</th>
                <th class="num">Sales orders</th>
                <th class="num">Purchases</th>
                <th class="num">Expenses</th>
                <th class="num">Cash in</th>
                <th class="num">Cash out</th>
                <th class="num">Net cash</th>
            </tr>
        </thead>
        <tbody>
            @foreach($days as $day)
                <tr>
                    <td>{{ $day['label'] }}</td>
                    <td class="num">{{ ams_num($day['sales']) }}</td>
                    <td class="num">{{ ams_num($day['orders']) }}</td>
                    <td class="num">{{ ams_num($day['purchases']) }}</td>
                    <td class="num">{{ ams_num($day['expenses']) }}</td>
                    <td class="num">{{ ams_num($day['cash_in']) }}</td>
                    <td class="num">{{ ams_num($day['cash_out']) }}</td>
                    <td class="num">{{ ams_num($day['net_cash']) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td>Totals</td>
                <td class="num">{{ ams_num($totals['sales']) }}</td>
                <td class="num">{{ ams_num($totals['orders']) }}</td>
                <td class="num">{{ ams_num($totals['purchases']) }}</td>
                <td class="num">{{ ams_num($totals['expenses']) }}</td>
                <td class="num">{{ ams_num($totals['cash_in']) }}</td>
                <td class="num">{{ ams_num($totals['cash_out']) }}</td>
                <td class="num">{{ ams_num($totals['net_cash']) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="section-title">Salesmen this month</div>
    <table class="items">
        <thead>
            <tr>
                <th>Salesman</th>
                <th>City</th>
                <th class="num">Invoices</th>
                <th class="num">Sales</th>
                <th class="num">Commission</th>
                <th class="num">Target</th>
                <th class="num">Achievement</th>
            </tr>
        </thead>
        <tbody>
            @forelse($salesmen as $row)
                <tr>
                    <td>{{ $row['name'] }}</td>
                    <td>{{ $row['city'] }}</td>
                    <td class="num">{{ ams_num($row['invoices']) }}</td>
                    <td class="num">{{ ams_num($row['sales']) }}</td>
                    <td class="num">{{ ams_num($row['commission']) }}</td>
                    <td class="num">{{ ams_num($row['target']) }}</td>
                    <td class="num">{{ $row['achievement'] }}</td>
                </tr>
            @empty
                <tr class="empty"><td colspan="7">No salesman sales in this month.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
