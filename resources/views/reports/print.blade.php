<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} — {{ $companyName }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Segoe UI", system-ui, -apple-system, sans-serif;
            background: #eef1f4;
            color: #111827;
        }
        .toolbar {
            position: sticky;
            top: 0;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.7rem 1.25rem;
            background: #111827;
            color: #fff;
        }
        .toolbar a, .toolbar button {
            padding: 0.4rem 0.95rem;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
            border: none;
            cursor: pointer;
        }
        .btn-print { background: #5b5ce2; color: #fff; }
        .btn-back { background: #6b7280; color: #fff; margin-right: 0.5rem; }
        .page-wrap { display: flex; justify-content: center; padding: 1.25rem; }
        .sheet {
            width: {{ !empty($wide) ? '297mm' : '210mm' }};
            min-height: {{ !empty($wide) ? '210mm' : '297mm' }};
            background: #fff;
            padding: 16mm 16mm 14mm;
            box-shadow: 0 8px 30px rgba(15, 23, 42, 0.12);
            display: flex;
            flex-direction: column;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            padding-bottom: 12px;
            border-bottom: 2px solid #111827;
        }
        .company-name {
            margin: 0 0 4px;
            font-size: 22px;
            font-weight: 700;
            letter-spacing: 0.01em;
        }
        .company-meta {
            margin: 0;
            font-size: 12px;
            color: #4b5563;
            line-height: 1.45;
        }
        .doc-meta { text-align: right; }
        .doc-label {
            margin: 0 0 4px;
            font-size: 11px;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: #6b7280;
        }
        .doc-no {
            margin: 0;
            font-size: 22px;
            font-weight: 700;
        }
        .doc-date {
            margin: 6px 0 0;
            font-size: 13px;
            color: #374151;
        }
        .items-wrap {
            width: 100%;
            margin-top: 16px;
        }
        .section-title {
            margin: 18px 0 8px;
            font-size: 11px;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #6b7280;
            font-weight: 700;
        }
        table.items {
            width: 100%;
            border-collapse: collapse;
            font-size: 12.5px;
            border: 1px solid #000;
        }
        table.items + .section-title,
        table.items + table.items,
        .section-title + table.items {
            margin-top: 16px;
        }
        table.items th,
        table.items td {
            border: 1px solid #000;
            padding: 7px 8px;
            vertical-align: middle;
        }
        table.items th {
            background: #f3f3f3;
            color: #111;
            font-weight: 700;
            text-align: left;
        }
        table.items .num {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }
        table.items tfoot td,
        table.items tr.subtotal td {
            font-size: 13px;
            font-weight: 700;
            background: #f3f3f3;
        }
        table.items tr.group-head td {
            background: #f3f3f3;
            font-weight: 700;
        }
        table.items tr.empty td {
            text-align: center;
            color: #6b7280;
            padding: 14px 8px;
        }
        table.items tr.row-bf td { background: #f3f3f3; font-style: italic; }
        table.items tr.row-receive td { background: #d1e7dd; }
        table.items tr.row-payment td { background: #f8d7da; }
        table.items tr.row-bank td { background: #f9fafb; }
        .party-type {
            display: block;
            font-size: 11px;
            color: #6b7280;
            margin-top: 2px;
        }
        .note { color: #6b7280; font-size: 11px; margin-top: 2px; }
        .voucher-block { margin-bottom: 14px; }
        .footer {
            margin-top: auto;
            padding-top: 28px;
            font-size: 10px;
            color: #9ca3af;
            text-align: center;
        }
        @media print {
            body { background: #fff; }
            .toolbar { display: none !important; }
            .page-wrap { padding: 0; }
            .sheet { box-shadow: none; min-height: auto; }
            table.items th,
            table.items tfoot td,
            table.items tr.subtotal td,
            table.items tr.group-head td,
            table.items tr.row-bf td,
            table.items tr.row-receive td,
            table.items tr.row-payment td {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
        @page {
            size: {{ !empty($wide) ? 'A4 landscape' : 'A4' }};
            margin: 10mm;
        }
    </style>
</head>
<body>
    @php
        $settings = $settings ?? app(\App\Services\SettingsService::class);
        $companyName = $companyName ?? $settings->companyName();
        $period = $period ?? (isset($fromDate, $toDate) ? ams_date($fromDate).' to '.ams_date($toDate) : null);
        $backUrl = $backUrl ?? route('reports.index');
        $backLabel = $backLabel ?? 'Back';
    @endphp
    <div class="toolbar">
        <span>{{ $title }}</span>
        <div>
            <a class="btn-back" href="{{ $backUrl }}">{{ $backLabel }}</a>
            <button type="button" class="btn-print" onclick="window.print()">Print</button>
        </div>
    </div>

    <div class="page-wrap">
        <div class="sheet">
            <header class="header">
                <x-print-company-brand :settings="$settings" />
                <div class="doc-meta">
                    <p class="doc-label">Report</p>
                    <p class="doc-no">{{ $title }}</p>
                    @if(!empty($period))
                        <p class="doc-date">{{ $period }}</p>
                    @endif
                    @if(!empty($subtitle))
                        <p class="doc-date">{{ $subtitle }}</p>
                    @endif
                    <p class="doc-date">Printed {{ ams_datetime($printedAt) }}</p>
                </div>
            </header>

            <div class="items-wrap">
                @yield('body')
            </div>

            <p class="footer">Printed {{ ams_datetime($printedAt) }}</p>
        </div>
    </div>
</body>
</html>
