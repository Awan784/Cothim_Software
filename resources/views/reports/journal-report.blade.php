<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Journal Report — {{ $companyName }}</title>
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
        .btn-print { background: #e85d82; color: #fff; }
        .btn-back { background: #6c757d; color: #fff; }

        .report-wrap {
            max-width: 1050px;
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
            border-bottom: 3px solid #e85d82;
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
            color: #e85d82;
        }
        .summary-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem 2rem;
            justify-content: center;
            margin-bottom: 1.25rem;
            padding: 0.75rem 1rem;
            background: #fdf2f5;
            border-radius: 6px;
            font-size: 0.9rem;
        }
        .voucher-block {
            margin-bottom: 1.25rem;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            overflow: hidden;
        }
        .voucher-head {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem 1.5rem;
            align-items: center;
            padding: 0.6rem 0.85rem;
            background: linear-gradient(135deg, #f07a9a, #e85d82);
            color: #fff;
            font-size: 0.88rem;
            font-weight: 600;
        }
        .voucher-head .notes {
            flex: 1 1 100%;
            font-weight: 400;
            font-size: 0.8rem;
            opacity: 0.95;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
        }
        thead th {
            background: #f8f9fa;
            font-weight: 600;
            text-align: left;
            padding: 0.5rem 0.75rem;
            border-bottom: 1px solid #dee2e6;
            color: #495057;
        }
        thead th.num { text-align: right; }
        tbody td {
            padding: 0.45rem 0.75rem;
            border-bottom: 1px solid #f1f3f5;
        }
        tbody tr:nth-child(even) { background: #fafbfc; }
        tbody td.num {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }
        tfoot td {
            padding: 0.55rem 0.75rem;
            font-weight: 700;
            background: #f1f3f5;
            border-top: 1px solid #dee2e6;
        }
        tfoot td.num { text-align: right; }
        .grand-total {
            margin-top: 1rem;
            border: 2px solid #e85d82;
            border-radius: 6px;
            overflow: hidden;
        }
        .grand-total table { margin: 0; }
        .grand-total tfoot td {
            background: linear-gradient(135deg, #f07a9a, #e85d82);
            color: #fff;
            font-size: 0.95rem;
        }
        .empty-state {
            text-align: center;
            padding: 2rem;
            color: #868e96;
            background: #f8f9fa;
            border-radius: 6px;
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
            .voucher-block { break-inside: avoid; }
            .voucher-head, .grand-total tfoot td {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
        @page { margin: 12mm; size: A4; }
    </style>
</head>
<body>
    <div class="toolbar no-print">
        <span>Journal Report</span>
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
                    <p><strong>Report:</strong> General Journal</p>
                    <p><strong>Print Date:</strong> {{ ams_date($printedAt) }}</p>
                    <p><strong>Print Time:</strong> {{ $printedAt->format('h:i A') }}</p>
                    <p><strong>Printed By:</strong> {{ auth()->user()?->name ?? 'System' }}</p>
                </div>
            </header>

            <h2 class="report-title">Journal Report</h2>

            <div class="summary-bar">
                <span><strong>Period:</strong> {{ ams_date($fromDate) }} — {{ ams_date($toDate) }}</span>
                <span><strong>Vouchers:</strong> {{ $voucherCount }}</span>
                <span><strong>Lines:</strong> {{ $lineCount }}</span>
                <span><strong>Total Debit:</strong> {{ number_format($grandTotalDebit, 2) }}</span>
                <span><strong>Total Credit:</strong> {{ number_format($grandTotalCredit, 2) }}</span>
            </div>

            @forelse($vouchers as $voucher)
                <div class="voucher-block">
                    <div class="voucher-head">
                        <span>Date: {{ $voucher['date'] instanceof \Carbon\Carbon ? ams_date($voucher['date']) : $voucher['date'] }}</span>
                        <span>Voucher: {{ $voucher['voucher_no'] }}</span>
                        <span>Debit: {{ number_format($voucher['total_debit'], 2) }}</span>
                        <span>Credit: {{ number_format($voucher['total_credit'], 2) }}</span>
                        @if(!empty($voucher['notes']))
                            <span class="notes">Notes: {{ $voucher['notes'] }}</span>
                        @endif
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 110px;">Account Type</th>
                                <th>Account Name</th>
                                <th>Narration</th>
                                <th class="num" style="width: 100px;">Debit</th>
                                <th class="num" style="width: 100px;">Credit</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($voucher['lines'] as $line)
                                <tr>
                                    <td>{{ $line['account_type'] }}</td>
                                    <td>{{ $line['account_name'] }}</td>
                                    <td>{{ $line['narration'] }}</td>
                                    <td class="num">{{ $line['debit'] > 0 ? number_format($line['debit'], 2) : '' }}</td>
                                    <td class="num">{{ $line['credit'] > 0 ? number_format($line['credit'], 2) : '' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3">Voucher Total</td>
                                <td class="num">{{ number_format($voucher['total_debit'], 2) }}</td>
                                <td class="num">{{ number_format($voucher['total_credit'], 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @empty
                <div class="empty-state">No journal vouchers in this period.</div>
            @endforelse

            @if($vouchers->isNotEmpty())
                <div class="grand-total">
                    <table>
                        <tfoot>
                            <tr>
                                <td colspan="3" style="width: 70%; padding: 0.75rem 1rem;">Grand Total ({{ $voucherCount }} voucher{{ $voucherCount === 1 ? '' : 's' }})</td>
                                <td class="num" style="width: 100px;">{{ number_format($grandTotalDebit, 2) }}</td>
                                <td class="num" style="width: 100px;">{{ number_format($grandTotalCredit, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif

            <footer class="report-footer">
                Generated by {{ $companyName }} · {{ ams_datetime($printedAt) }}
            </footer>
        </div>
    </div>
</body>
</html>
