<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Account Receivable — {{ $companyName }}</title>
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
        .toolbar-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
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
        .btn-print:hover { background: #138496; }
        .btn-back { background: #6c757d; color: #fff; }
        .btn-back:hover { color: #fff; }

        .report-wrap {
            max-width: 900px;
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
            margin-bottom: 1.25rem;
            border-bottom: 3px solid #e8924f;
        }
        .brand-block {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .brand-block img {
            height: 52px;
            width: auto;
        }
        .company-name {
            font-size: 1.35rem;
            font-weight: 700;
            margin: 0 0 0.15rem;
            color: #1a1a2e;
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
        .report-title {
            text-align: center;
            margin: 0 0 1.25rem;
            font-size: 1.25rem;
            font-weight: 700;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            color: #e8924f;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }
        thead th {
            background: linear-gradient(135deg, #f5a962, #e8924f);
            color: #fff;
            font-weight: 600;
            text-align: left;
            padding: 0.65rem 0.75rem;
            border: none;
        }
        thead th.num { text-align: right; }
        tbody td {
            padding: 0.55rem 0.75rem;
            border-bottom: 1px solid #e9ecef;
        }
        tbody tr:nth-child(even) { background: #fafbfc; }
        tbody td.num {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }
        .party-type {
            display: block;
            font-size: 0.7rem;
            color: #868e96;
            margin-top: 0.1rem;
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
            .report-sheet {
                box-shadow: none;
                border-radius: 0;
                padding: 0;
            }
            thead th {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
        @page {
            margin: 15mm;
            size: A4;
        }
    </style>
</head>
<body>
    <div class="toolbar no-print">
        <span>Account Receivable</span>
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
                    <p><strong>Report:</strong> Account Receivable</p>
                    <p><strong>Print Date:</strong> {{ ams_date($printedAt) }}</p>
                    <p><strong>Print Time:</strong> {{ $printedAt->format('h:i A') }}</p>
                    <p><strong>Printed By:</strong> {{ auth()->user()?->name ?? 'System' }}</p>
                </div>
            </header>

            <h2 class="report-title">Account Receivable</h2>

            <table>
                <thead>
                    <tr>
                        <th style="width: 72px;">Code</th>
                        <th>Party Name</th>
                        <th class="num" style="width: 110px;">Debit</th>
                        <th class="num" style="width: 110px;">Credit</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td>{{ $row['code'] }}</td>
                            <td>
                                {{ $row['party_name'] }}
                                <span class="party-type">{{ $row['party_type'] }}</span>
                            </td>
                            <td class="num">{{ (float) $row['debit'] > 0 ? number_format((float) $row['debit'], 2) : '0.00' }}</td>
                            <td class="num">{{ (float) $row['credit'] > 0 ? number_format((float) $row['credit'], 2) : '0.00' }}</td>
                        </tr>
                    @empty
                        <tr class="empty-row">
                            <td colspan="4">No customer or supplier balances to show.</td>
                        </tr>
                    @endforelse
                </tbody>
                @if($rows->isNotEmpty())
                    <tfoot>
                        <tr>
                            <td colspan="2">Total</td>
                            <td class="num">{{ number_format($totalDebit, 2) }}</td>
                            <td class="num">{{ number_format($totalCredit, 2) }}</td>
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
