<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Commission Report — {{ $salesman->name }}</title>
    @php
        $company = strtoupper($settings->companyName());
        $address = $settings->address();
        $phone = $settings->phone();
        $logoPath = $settings->publicLogoPath();
        $titleDate = $toDate->format('d-m-y');
        $region = strtoupper(trim($salesman->citiesLabel()));
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
            padding: 8mm 8mm 10mm;
            box-shadow: 0 8px 30px rgba(15, 23, 42, 0.12);
        }
        .brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-align: center;
        }
        .brand img { height: 46px; width: auto; max-width: 90px; object-fit: contain; }
        .brand-name {
            margin: 0;
            font-size: 20px;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }
        .brand-meta {
            margin: 2px 0 0;
            font-size: 11px;
            font-weight: 700;
        }
        .doc-title {
            margin: 8px 0 6px;
            padding: 4px 6px;
            border: 1px solid #000;
            text-align: center;
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            background: #e5e5e5;
        }
        table.items {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5px;
        }
        table.items th, table.items td {
            border: 1px solid #000;
            padding: 3px 4px;
            vertical-align: middle;
        }
        table.items th {
            background: #d9d9d9;
            font-weight: 800;
            text-align: center;
            text-transform: uppercase;
        }
        table.items td.num, table.items th.num { text-align: right; font-variant-numeric: tabular-nums; }
        table.items td.ctr { text-align: center; }
        table.items tfoot td { font-weight: 700; }
        table.items tfoot tr.total td { background: #e5e5e5; }
        table.items tfoot tr.less td.lbl { text-align: right; font-weight: 800; }
        table.items tfoot tr.net td { background: #f3f3f3; font-weight: 800; }
        .empty { text-align: center; padding: 14px 8px; color: #444; }
        .footer {
            margin-top: 18px;
            padding-top: 10px;
            border-top: 1px solid #d1d5db;
            font-size: 10px;
            color: #6b7280;
            text-align: center;
        }
        .footer .print-credit {
            font-size: 11px;
            color: #111;
            margin-bottom: 4px;
        }
        @media print {
            body { background: #fff; }
            .toolbar { display: none !important; }
            .page-wrap { padding: 0; }
            .sheet { box-shadow: none; min-height: 277mm; }
            table.items th, table.items tfoot tr.total td, table.items tfoot tr.net td, .doc-title {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
        @page { size: A4; margin: 8mm; }
    </style>
</head>
<body>
    <div class="toolbar">
        <span>Commission Report — {{ $salesman->name }}</span>
        <div>
            <a class="btn-back" href="{{ route('reports.index') }}">Back</a>
            <button type="button" class="btn-print" onclick="window.print()">Print</button>
        </div>
    </div>

    <div class="page-wrap">
        <div class="sheet">
            <div class="brand">
                @if($logoPath)
                    <img src="/{{ ltrim($logoPath, '/') }}" alt="">
                @endif
                <div>
                    <h1 class="brand-name">{{ $company }} ®</h1>
                    <p class="brand-meta">
                        @if($address !== '')
                            {{ $address }}@if($phone !== '') , Cell #: {{ $phone }}@endif
                        @elseif($phone !== '')
                            Cell #: {{ $phone }}
                        @endif
                    </p>
                </div>
            </div>

            <h2 class="doc-title">
                Commission Report of {{ strtoupper($salesman->name) }}
                {{ $titleDate }}
                @if($region !== '') {{ $region }}@endif
            </h2>
            <p style="margin:0 0 6px;font-size:11px;text-align:center;">
                {{ $fromDate->format('d-m-y') }} to {{ $toDate->format('d-m-y') }}
            </p>

            <table class="items">
                <thead>
                    <tr>
                        <th>Sr</th>
                        <th>Date</th>
                        <th>Bill#</th>
                        <th>Builty<br>Postal</th>
                        <th>Builty<br>Exp</th>
                        <th>Party Names</th>
                        <th>City<br>Names</th>
                        <th class="num">Amount</th>
                        <th class="num">Commission</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $i => $row)
                        <tr>
                            <td class="ctr">{{ $i + 1 }}</td>
                            <td class="ctr">{{ optional($row['date'])->format('j/n/Y') }}</td>
                            <td class="ctr">{{ $row['bill_no'] }}</td>
                            <td class="ctr">{{ $row['builty_postal'] ?: '' }}</td>
                            <td class="num">{{ ((float) $row['builty_exp']) > 0 ? ams_num($row['builty_exp']) : '' }}</td>
                            <td>{{ $row['party'] }}</td>
                            <td class="ctr">{{ $row['city'] }}</td>
                            <td class="num">{{ ams_num($row['amount']) }}</td>
                            <td class="num">{{ ams_num($row['commission']) }}</td>
                        </tr>
                    @empty
                        <tr><td class="empty" colspan="9">No invoices for this salesman in the selected dates.</td></tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="total">
                        <td colspan="4" class="ctr">TOTAL</td>
                        <td class="num">{{ $builtyExpenses > 0 ? ams_num($builtyExpenses) : '' }}</td>
                        <td colspan="2"></td>
                        <td class="num">{{ ams_num($totalSales) }}</td>
                        <td class="num">{{ ams_num($totalCommission) }}</td>
                    </tr>
                    <tr class="less">
                        <td colspan="7" class="lbl">LESS {{ $percentLabel }}% COMMISSION</td>
                        <td class="num">({{ ams_num($totalCommission) }})</td>
                        <td></td>
                    </tr>
                    <tr class="net">
                        <td colspan="7" class="lbl" style="text-align:right;">NET RECEIVABLE</td>
                        <td class="num">{{ ams_num($afterCommission) }}</td>
                        <td></td>
                    </tr>
                    <tr class="less">
                        <td colspan="7" class="lbl">LESS BUILTY EXPENSES</td>
                        <td class="num">({{ ams_num($builtyExpenses) }})</td>
                        <td></td>
                    </tr>
                    <tr class="net">
                        <td colspan="7" class="lbl" style="text-align:right;">NET RECEIVABLE</td>
                        <td class="num">{{ ams_num($afterBuilty) }}</td>
                        <td></td>
                    </tr>
                    <tr class="less">
                        <td colspan="7" class="lbl">LESS PREVIOUS ADVANCE</td>
                        <td class="num">({{ ams_num($previousAdvance) }})</td>
                        <td></td>
                    </tr>
                    <tr class="net">
                        <td colspan="7" class="lbl" style="text-align:right;">NET RECEIVABLE</td>
                        <td class="num">{{ ams_num($netReceivable) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
            @include('partials.print-footer')
        </div>
    </div>
</body>
</html>
