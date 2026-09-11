<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cash Register — {{ $companyName }}</title>
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
        .btn-print { background: #3ecf6e; color: #fff; }
        .btn-back { background: #6c757d; color: #fff; }

        .report-wrap {
            max-width: 1100px;
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
            border-bottom: 3px solid #3ecf6e;
        }
        .brand-block { display: flex; align-items: center; gap: 1rem; }
        .brand-block img { height: 52px; width: auto; }
        .company-name { font-size: 1.35rem; font-weight: 700; margin: 0 0 0.15rem; }
        .company-tagline { margin: 0; font-size: 0.8rem; color: #6c757d; }
        .meta-block { text-align: right; font-size: 0.85rem; color: #495057; }
        .meta-block p { margin: 0.2rem 0; }
        .report-title {
            text-align: center;
            margin: 0 0 0.75rem;
            font-size: 1.25rem;
            font-weight: 700;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            color: #2da855;
        }
        .summary-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem 2rem;
            justify-content: center;
            margin-bottom: 1.25rem;
            padding: 0.75rem 1rem;
            background: #f0fdf4;
            border-radius: 6px;
            font-size: 0.9rem;
        }
        .summary-bar span strong { color: #1a1a2e; }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.82rem;
        }
        thead th {
            background: linear-gradient(135deg, #6fdc8c, #3ecf6e);
            color: #fff;
            font-weight: 600;
            text-align: left;
            padding: 0.6rem 0.5rem;
            border: none;
        }
        thead th.num { text-align: right; }
        tbody td {
            padding: 0.45rem 0.5rem;
            border-bottom: 1px solid #e9ecef;
            vertical-align: top;
        }
        tbody tr:nth-child(even) { background: #fafbfc; }
        tbody tr.row-bf { background: #edfdf1; font-style: italic; }
        tbody tr.row-receive td.type-cell { color: #198754; font-weight: 600; }
        tbody tr.row-payment td.type-cell { color: #dc3545; font-weight: 600; }
        tbody tr.row-receive { background: #d1e7dd !important; }
        tbody tr.row-payment { background: #f8d7da !important; }
        tbody tr.row-bank { background: #f8f9fa; }
        tbody tr.row-bank td.amount-cell { color: #6c757d; font-style: italic; }
        tbody td.num {
            text-align: right;
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }
        tbody td.notes-cell {
            max-width: 140px;
            word-break: break-word;
            color: #495057;
            font-size: 0.78rem;
        }
        tfoot td {
            padding: 0.75rem 0.5rem;
            font-weight: 700;
            background: #f1f3f5;
            border-top: 2px solid #dee2e6;
        }
        tfoot td.num { text-align: right; }
        .empty-row td { text-align: center; padding: 1.5rem; color: #868e96; }
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
        @page { margin: 12mm; size: A4 landscape; }
    </style>
</head>
<body>
    <div class="toolbar no-print">
        <span>Cash Register</span>
        <div class="toolbar-actions">
            <button type="button" class="btn btn-print" onclick="window.print()">Print / Save as PDF</button>
            <a href="{{ route('reports.index') }}" class="btn btn-back">Back to Reports</a>
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
                    <p><strong>Report:</strong> Cash Register</p>
                    <p><strong>Print Date:</strong> {{ ams_date($printedAt) }}</p>
                    <p><strong>Print Time:</strong> {{ $printedAt->format('h:i A') }}</p>
                    <p><strong>Printed By:</strong> {{ auth()->user()?->name ?? 'System' }}</p>
                </div>
            </header>

            <h2 class="report-title">Cash Register</h2>

            <div class="summary-bar">
                <span><strong>Period:</strong> {{ ams_date($fromDate) }} — {{ ams_date($toDate) }}</span>
                <span><strong>Total Cash In:</strong> {{ number_format($totalCashIn, 2) }}</span>
                <span><strong>Total Cash Out:</strong> {{ number_format($totalCashOut, 2) }}</span>
                <span><strong>Cash Balance:</strong> {{ number_format($closingBalance, 2) }}</span>
            </div>

            <table>
                <thead>
                    <tr>
                        <th style="width: 88px;">Date</th>
                        <th style="width: 88px;">Voucher</th>
                        <th style="width: 130px;">Transaction</th>
                        <th style="width: 80px;">Party Type</th>
                        <th>Party / Account</th>
                        <th style="width: 110px;">Paid Via</th>
                        <th style="width: 80px;">Reference</th>
                        <th>Notes</th>
                        <th class="num" style="width: 82px;">Amount</th>
                        <th class="num" style="width: 82px;">Cash In</th>
                        <th class="num" style="width: 88px;">Cash Out</th>
                        <th class="num" style="width: 96px;">Balance</th>
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
                            <td class="type-cell">{{ $entry['type_label'] }}</td>
                            <td>{{ $entry['party_type'] }}</td>
                            <td>{{ $entry['party_name'] }}</td>
                            <td>{{ $entry['paid_via'] }}</td>
                            <td>{{ $entry['reference'] }}</td>
                            <td class="notes-cell">{{ $entry['notes'] }}</td>
                            <td class="num amount-cell">{{ isset($entry['amount']) && (float) $entry['amount'] > 0 ? number_format((float) $entry['amount'], 2) : '' }}</td>
                            <td class="num">{{ (float) $entry['cash_in'] > 0 ? number_format((float) $entry['cash_in'], 2) : '' }}</td>
                            <td class="num">{{ (float) $entry['cash_out'] > 0 ? number_format((float) $entry['cash_out'], 2) : '' }}</td>
                            <td class="num">{{ number_format((float) $entry['balance'], 2) }}</td>
                        </tr>
                    @empty
                        <tr class="empty-row">
                            <td colspan="12">No transactions in this period.</td>
                        </tr>
                    @endforelse
                </tbody>
                @if($entries->isNotEmpty())
                    <tfoot>
                        <tr>
                            <td colspan="9">Period Total (cash in / out)</td>
                            <td class="num"></td>
                            <td class="num">{{ number_format($totalCashIn, 2) }}</td>
                            <td class="num">{{ number_format($totalCashOut, 2) }}</td>
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
