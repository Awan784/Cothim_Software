<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Party Ledger — {{ $partyName }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Segoe UI", system-ui, -apple-system, sans-serif;
            background: #eef1f5;
            color: #1a1a2e;
        }
        .toolbar {
            position: sticky;
            top: 0;
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.75rem 1.5rem;
            background: #1a1a2e;
            color: #fff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }
        .toolbar-actions { display: flex; gap: 0.5rem; flex-wrap: wrap; }
        .btn {
            display: inline-flex;
            align-items: center;
            padding: 0.45rem 1rem;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
            border: none;
            cursor: pointer;
        }
        .btn-print { background: #17a2b8; color: #fff; }
        .btn-back { background: #6c757d; color: #fff; }

        .report-wrap {
            max-width: 1000px;
            margin: 1.5rem auto 2rem;
            padding: 0 1rem;
        }
        .report-sheet {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
            padding: 2rem 2.25rem;
        }
        .report-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1.5rem;
            padding-bottom: 1.25rem;
            margin-bottom: 1rem;
            border-bottom: 3px solid #17a2b8;
        }
        .brand-block { display: flex; align-items: center; gap: 1rem; }
        .brand-block img { height: 52px; width: auto; }
        .company-name {
            font-size: 1.35rem;
            font-weight: 700;
            margin: 0 0 0.15rem;
        }
        .company-tagline {
            margin: 0;
            font-size: 0.8rem;
            color: #6c757d;
        }
        .meta-block {
            text-align: right;
            font-size: 0.85rem;
            color: #495057;
        }
        .meta-block p { margin: 0.2rem 0; }
        .report-company-title {
            text-align: center;
            margin: 0 0 0.25rem;
            font-size: 1.5rem;
            font-weight: 700;
            color: #1a1a2e;
            letter-spacing: 0.02em;
        }
        .report-title {
            text-align: center;
            margin: 0 0 0.75rem;
            font-size: 1.25rem;
            font-weight: 700;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            color: #17a2b8;
        }
        .party-summary {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem 2rem;
            justify-content: center;
            margin-bottom: 1.25rem;
            padding: 0.75rem 1rem;
            background: #f8f9fa;
            border-radius: 6px;
            font-size: 0.9rem;
        }
        .party-summary span.balance-highlight {
            background: #fff;
            border: 1px solid #17a2b8;
            border-radius: 6px;
            padding: 0.35rem 0.7rem;
        }
        .party-summary span.balance-highlight strong { color: #0f6f80; }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.88rem;
        }
        thead th {
            background: linear-gradient(135deg, #3dbdd4, #17a2b8);
            color: #fff;
            font-weight: 600;
            text-align: left;
            padding: 0.65rem 0.75rem;
            border: none;
        }
        thead th.num { text-align: right; }
        tbody td {
            padding: 0.5rem 0.75rem;
            border-bottom: 1px solid #e9ecef;
        }
        tbody tr:nth-child(even) { background: #fafbfc; }
        tbody tr.row-bf { background: #e8f6f8; font-style: italic; }
        tbody tr.row-receive { background: #d1e7dd !important; }
        tbody tr.row-payment { background: #f8d7da !important; }
        tbody td.num {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }
        tfoot td {
            padding: 0.75rem;
            font-weight: 700;
            background: #f1f3f5;
            border-top: 2px solid #dee2e6;
        }
        tfoot td.num { text-align: right; }
        .empty-row td {
            text-align: center;
            padding: 1.5rem;
            color: #868e96;
        }
        .report-footer {
            margin-top: 1.5rem;
            padding-top: 0.75rem;
            border-top: 1px dashed #ced4da;
            font-size: 0.75rem;
            color: #868e96;
            text-align: center;
        }

        @media print {
            body { background: #fff; }
            .toolbar { display: none !important; }
            .report-wrap { margin: 0; max-width: none; padding: 0; }
            .report-sheet { box-shadow: none; border-radius: 0; padding: 0; }
            thead th {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
        @page { margin: 15mm; size: A4 landscape; }
    </style>
</head>
<body>
    <div class="toolbar no-print">
        <span>Party Ledger — {{ $partyName }}</span>
        <div class="toolbar-actions">
            <button type="button" class="btn btn-print" onclick="window.print()">Print / Save as PDF</button>
            <a href="{{ $backUrl }}" class="btn btn-back">{{ $backLabel }}</a>
        </div>
    </div>

    <div class="report-wrap">
        <div class="report-sheet">
            <header class="report-header">
                <div class="brand-block">
                    <img src="{{ asset('assets/img/brand/dark.svg') }}" alt="{{ $companyName }}">
                    <div>
                        <h1 class="company-name">{{ $companyName }}</h1>
                    </div>
                </div>
                <div class="meta-block">
                    <p><strong>Report:</strong> Party Ledger</p>
                    <p><strong>Print Date:</strong> {{ ams_date($printedAt) }}</p>
                    <p><strong>Print Time:</strong> {{ $printedAt->format('h:i A') }}</p>
                    <p><strong>Printed By:</strong> {{ auth()->user()?->name ?? 'System' }}</p>
                </div>
            </header>

            <h2 class="report-company-title">{{ $companyName }}</h2>
            <h3 class="report-title">Party Ledger</h3>

            @php
                $balanceLabel = match ($accountType ?? '') {
                    'supplier' => 'Payable Balance',
                    'customer' => 'Receivable Balance',
                    default => 'Closing Balance',
                };
            @endphp
            <div class="party-summary">
                <span><strong>Code:</strong> {{ $partyCode }}</span>
                <span><strong>Party:</strong> {{ $partyName }}</span>
                <span><strong>Type:</strong> {{ $accountTypeLabel }}</span>
                <span><strong>Period:</strong> {{ ams_date($fromDate) }} — {{ ams_date($toDate) }}</span>
                <span><strong>Opening Balance:</strong> {{ number_format((float) $openingBalance, 2) }}</span>
                <span class="balance-highlight"><strong>{{ $balanceLabel }}:</strong> {{ number_format((float) $closingBalance, 2) }}</span>
            </div>

            <table>
                <thead>
                    <tr>
                        <th style="width: 90px;">Date</th>
                        <th style="width: 90px;">Ref</th>
                        <th>Description</th>
                        <th>Notes</th>
                        <th class="num" style="width: 90px;">Debit</th>
                        <th class="num" style="width: 90px;">Credit</th>
                        <th class="num" style="width: 100px;">Balance</th>
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
                            <td class="num">{{ (float) $entry['debit'] > 0 ? number_format((float) $entry['debit'], 2) : '' }}</td>
                            <td class="num">{{ (float) $entry['credit'] > 0 ? number_format((float) $entry['credit'], 2) : '' }}</td>
                            <td class="num">{{ number_format((float) $entry['balance'], 2) }}</td>
                        </tr>
                    @empty
                        <tr class="empty-row">
                            <td colspan="7">No transactions in this period.</td>
                        </tr>
                    @endforelse
                </tbody>
                @if($entries->isNotEmpty())
                    <tfoot>
                        <tr>
                            <td colspan="4">Period Total</td>
                            <td class="num">{{ number_format($totalDebit, 2) }}</td>
                            <td class="num">{{ number_format($totalCredit, 2) }}</td>
                            <td class="num">{{ number_format($closingBalance, 2) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>

            <footer class="report-footer">
                Generated by {{ $companyName }} · {{ ams_datetime($printedAt) }}
            </footer>
        </div>
    </div>
</body>
</html>
