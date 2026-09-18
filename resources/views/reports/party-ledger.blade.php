@extends('reports.print')

@section('body')
    @php
        $balanceLabel = match ($accountType ?? '') {
            'supplier' => 'Payable balance',
            'customer' => 'Receivable balance',
            default => 'Closing balance',
        };
    @endphp

    <table class="items">
        <thead>
            <tr>
                <th>Party</th>
                <th>Type</th>
                <th>Code</th>
                <th class="num">Opening</th>
                <th class="num">{{ $balanceLabel }}</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $partyName }}</td>
                <td>{{ $accountTypeLabel }}</td>
                <td>{{ $partyCode }}</td>
                <td class="num">{{ ams_num($openingBalance) }}</td>
                <td class="num">{{ ams_num($closingBalance) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="section-title">Ledger</div>
    <table class="items">
        <thead>
            <tr>
                <th>Date</th>
                <th>Ref</th>
                <th>Description</th>
                <th>Notes</th>
                <th class="num">Debit</th>
                <th class="num">Credit</th>
                <th class="num">Balance</th>
            </tr>
        </thead>
        <tbody>
            @forelse($entries as $entry)
                <tr @class([
                    'row-bf' => !empty($entry['is_brought_forward']) || !empty($entry['is_opening']),
                    'row-receive' => empty($entry['is_brought_forward']) && ($entry['tone'] ?? '') === 'receive',
                    'row-payment' => empty($entry['is_brought_forward']) && ($entry['tone'] ?? '') === 'payment',
                ])>
                    <td>{{ $entry['date'] instanceof \Carbon\Carbon ? ams_date($entry['date']) : $entry['date'] }}</td>
                    <td>{{ $entry['ref'] }}</td>
                    <td>{{ $entry['description'] }}</td>
                    <td>{{ $entry['notes'] ?? '' }}</td>
                    <td class="num">{{ (float) $entry['debit'] > 0 ? ams_num($entry['debit']) : '' }}</td>
                    <td class="num">{{ (float) $entry['credit'] > 0 ? ams_num($entry['credit']) : '' }}</td>
                    <td class="num">{{ ams_num($entry['balance']) }}</td>
                </tr>
            @empty
                <tr class="empty">
                    <td colspan="7">No transactions in this period.</td>
                </tr>
            @endforelse
        </tbody>
        @if($entries->isNotEmpty())
            <tfoot>
                <tr>
                    <td colspan="4">Period total</td>
                    <td class="num">{{ ams_num($totalDebit) }}</td>
                    <td class="num">{{ ams_num($totalCredit) }}</td>
                    <td class="num">{{ ams_num($closingBalance) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>
@endsection
