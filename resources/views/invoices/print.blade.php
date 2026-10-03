<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $invoice->invoice_no }} — Sale Invoice</title>
    @php
        $company = strtoupper($settings->companyName());
        $printTexts = $settings->invoicePrintTexts($company);
        $address = $settings->address();
        $phone = $settings->phone();
        $logoPath = $settings->publicLogoPath();
        $customer = $invoice->customer;
        $salesman = $invoice->salesman;
        $mode = trim((string) $invoice->mode);
        if ($mode === '') {
            $mode = $invoice->isPaid() ? 'Cash' : 'CO';
        }
        $lines = $invoice->lines;
        $itemCount = $lines->count();
        $qtyTotal = (float) $lines->sum('quantity');
        $grossTotal = (float) $lines->sum(fn ($line) => round((float) $line->quantity * (float) $line->unit_price, 2));
        $discTotal = (float) $lines->sum('discount_amount');
        $netTotal = (float) $lines->sum('line_net');
        $printStamp = $printedAt->format('n/j/Y g:i:s A');
        $issueDate = optional($invoice->issued_at ?? $invoice->invoice_date)?->format('d-M-y');
    @endphp
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #eef1f4;
            color: #000;
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
            width: 210mm;
            min-height: 297mm;
            background: #fff;
            padding: 10mm 12mm 10mm;
            box-shadow: 0 8px 30px rgba(15, 23, 42, 0.12);
            display: flex;
            flex-direction: column;
        }
        .brand {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            gap: 10px;
            text-align: left;
            margin-bottom: 6px;
        }
        .brand img {
            height: 52px;
            width: auto;
            max-width: 86px;
            object-fit: contain;
        }
        .brand-text { max-width: 520px; }
        .brand-name {
            margin: 0;
            font-size: 20px;
            font-weight: 800;
            letter-spacing: 0.02em;
            line-height: 1.1;
            text-transform: uppercase;
        }
        .brand-meta {
            margin: 3px 0 0;
            font-size: 11px;
            line-height: 1.35;
            font-weight: 700;
        }
        .doc-title {
            margin: 10px 0 12px;
            text-align: center;
            font-size: 16px;
            font-weight: 800;
            text-decoration: underline;
            letter-spacing: 0.04em;
        }
        .meta {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px 24px;
            margin-bottom: 26px;
            font-size: 12.5px;
            font-weight: 700;
        }
        .meta .right { text-align: right; }
        .meta p { margin: 3px 0; }
        table.items {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 15.5px;
            border: 1px solid #000;
        }
        table.items col.col-sr { width: 5%; }
        table.items col.col-product { width: 22%; }
        table.items col.col-bat { width: 8%; }
        table.items col.col-exp { width: 11%; }
        table.items col.col-qty { width: 9%; }
        table.items col.col-rate { width: 8%; }
        table.items col.col-amt { width: 10%; }
        table.items col.col-dis { width: 8%; }
        table.items col.col-disamt { width: 9%; }
        table.items col.col-net { width: 10%; }
        table.items th,
        table.items td {
            border: 1px solid #000;
            padding: 7px 5px;
            vertical-align: top;
        }
        table.items th {
            background: #cfcfcf;
            font-weight: 800;
            text-align: center;
            vertical-align: middle;
            line-height: 1.15;
            font-size: 14px;
        }
        table.items td.num,
        table.items th.num {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }
        table.items td.center { text-align: center; }
        table.items tbody td { font-weight: 600; }
        .product-name { text-align: left; overflow-wrap: break-word; word-break: normal; }
        .batch { text-align: center; }
        .totals td {
            font-weight: 800;
            background: #fff;
        }
        .totals .note {
            text-align: left;
            font-weight: 700;
            font-size: 15px;
        }
        .lined { border-top: 2px solid #000 !important; }
        .bill td {
            font-weight: 800;
            border: 1px solid #000;
        }
        .bill-label {
            background: #cfcfcf;
            text-align: right;
            padding-right: 8px !important;
        }
        .bill-amt {
            background: #cfcfcf;
            text-align: right;
        }
        .legal {
            margin-top: auto;
            padding-top: 48px;
            font-size: 11.5px;
            line-height: 1.45;
        }
        .legal h3 {
            margin: 0 0 4px;
            font-size: 12px;
            font-weight: 800;
            text-decoration: underline;
            text-transform: uppercase;
        }
        .legal p { margin: 0 0 8px; white-space: pre-wrap; }
        .sign {
            margin-top: 18px;
            font-weight: 800;
            font-size: 12.5px;
        }
        .sign-line {
            margin-top: 22px;
            border-top: 1px solid #000;
            width: 100%;
        }
        .sheet-foot {
            display: flex;
            justify-content: space-between;
            margin-top: 8px;
            font-size: 10px;
            color: #444;
        }
        @media print {
            body { background: #fff; }
            .toolbar { display: none !important; }
            .page-wrap { padding: 0; }
            .sheet { box-shadow: none; min-height: 277mm; }
            table.items th, .bill-label, .bill-amt {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
        @page { size: A4; margin: 8mm; }
    </style>
</head>
<body>
    <div class="toolbar">
        <span>Sale Invoice {{ $invoice->invoice_no }}</span>
        <div>
            <a class="btn-back" href="{{ route('invoices.show', $invoice) }}">Back</a>
            <button type="button" class="btn-print" onclick="window.print()">Print</button>
        </div>
    </div>

    <div class="page-wrap">
        <div class="sheet">
            <div class="brand">
                @if($logoPath)
                    <img src="/{{ ltrim($logoPath, '/') }}" alt="">
                @endif
                <div class="brand-text">
                    <h1 class="brand-name">{{ $company }}</h1>
                    <p class="brand-meta">
                        @if($address !== '')
                            {{ $address }}@if($phone !== '') - Mob:{{ $phone }}@endif
                        @elseif($phone !== '')
                            Mob:{{ $phone }}
                        @endif
                    </p>
                </div>
            </div>

            <h2 class="doc-title">SALE INVOICE</h2>

            <div class="meta">
                <div>
                    <p>Invoice #: {{ $invoice->invoice_no }}</p>
                    <p>Name: {{ $customer?->displayName() ?: '—' }}</p>
                    <p>City : {{ $customer?->city ?: '—' }}</p>
                </div>
                <div class="right">
                    <p>Issue Date: {{ $issueDate ?: '—' }}</p>
                    <p>Salesman: {{ $salesman?->name ?: '—' }}</p>
                    <p>Mode: {{ $mode }}</p>
                </div>
            </div>

            <table class="items">
                <colgroup>
                    <col class="col-sr">
                    <col class="col-product">
                    <col class="col-bat">
                    <col class="col-exp">
                    <col class="col-qty">
                    <col class="col-rate">
                    <col class="col-amt">
                    <col class="col-dis">
                    <col class="col-disamt">
                    <col class="col-net">
                </colgroup>
                <thead>
                    <tr>
                        <th>Sr#</th>
                        <th>Products</th>
                        <th>Bat#</th>
                        <th>Exp Date</th>
                        <th>Quanti<br>ty</th>
                        <th>Rate</th>
                        <th>Amount</th>
                        <th>Dis%</th>
                        <th>Dis Amt</th>
                        <th>Net.<br>Amt</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($lines as $i => $line)
                        @php
                            $qty = (float) $line->quantity;
                            $rate = (float) $line->unit_price;
                            $gross = round($qty * $rate, 2);
                            $item = $line->stockItem;
                        @endphp
                        <tr>
                            <td class="center">{{ $i + 1 }}</td>
                            <td class="product-name">{{ $line->description }}</td>
                            <td class="batch">{{ $line->batch_no ?: $line->lot?->batchLabel() ?: ($item?->batch_no ?: '') }}</td>
                            <td class="center">{{ ($line->lot?->expiry_date ?? $item?->expiry_date) ? ($line->lot?->expiry_date ?? $item->expiry_date)->format('d-M-y') : '' }}</td>
                            <td class="num">{{ ams_num($qty) }}</td>
                            <td class="num">{{ ams_num($rate) }}</td>
                            <td class="num">{{ ams_num($gross) }}</td>
                            <td class="num">{{ number_format((float) $line->discount_rate, 2) }}%</td>
                            <td class="num">{{ ams_num($line->discount_amount) }}</td>
                            <td class="num">{{ ams_num($line->line_net) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="totals">
                        <td colspan="4" class="note">Items {{ $itemCount }}, Printed on: {{ $printStamp }}</td>
                        <td class="num">{{ ams_num($qtyTotal) }}</td>
                        <td></td>
                        <td class="num lined">{{ ams_num($grossTotal) }}</td>
                        <td></td>
                        <td class="num lined">{{ ams_num($discTotal) }}</td>
                        <td class="num lined">{{ ams_num($netTotal) }}</td>
                    </tr>
                    <tr class="bill">
                        <td colspan="7"></td>
                        <td colspan="2" class="bill-label">Bill Amount:</td>
                        <td class="num bill-amt">{{ ams_num($netTotal) }}</td>
                    </tr>
                </tfoot>
            </table>

            <div class="legal">
                @if($printTexts['warranty'] !== '')
                    <h3>General Warranty</h3>
                    @foreach(preg_split('/\n\s*\n/', $printTexts['warranty']) as $para)
                        <p>{{ $para }}</p>
                    @endforeach
                @endif

                @if($printTexts['note'] !== '')
                    <h3>Note:</h3>
                    <p>{{ $printTexts['note'] }}</p>
                @endif

                @if($printTexts['on_behalf'] !== '')
                    <div class="sign">{{ $printTexts['on_behalf'] }}</div>
                    <div class="sign-line"></div>
                @endif
            </div>

            <div class="sheet-foot">
                <span>{{ $printTexts['developed_by'] }}</span>
                <span>Page: 1/1</span>
            </div>
        </div>
    </div>
</body>
</html>
