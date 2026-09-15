<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $customer->displayName() }} — Customer Ledger</title>
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
            width: 210mm;
            min-height: 297mm;
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
        .parties {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 18px 0 0;
            padding-bottom: 16px;
        }
        .party-label {
            font-size: 10px;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #6b7280;
            margin-bottom: 4px;
        }
        .party-name {
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .party-meta {
            font-size: 12px;
            color: #4b5563;
            line-height: 1.45;
        }
        .items-wrap {
            width: 100%;
            margin-top: 4px;
        }
        table.items {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 12.5px;
            border: 1px solid #000;
        }
        table.items col.col-num { width: 36px; }
        table.items col.col-date { width: 78px; }
        table.items col.col-ref { width: 90px; }
        table.items col.col-desc { width: auto; }
        table.items col.col-debit { width: 95px; }
        table.items col.col-credit { width: 95px; }
        table.items col.col-bal { width: 105px; }
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
        table.items tfoot td {
            font-size: 13px;
            font-weight: 700;
            background: #f3f3f3;
        }
        table.items tr.row-sale td {
            background: #dbeafe;
        }
        table.items tr.row-sales-return td {
            background: #f3e8ff;
        }
        table.items tr.row-cash-receive td {
            background: #d1e7dd;
        }
        table.items tr.row-cash-payment td {
            background: #f8d7da;
        }
        .note { color: #6b7280; font-size: 11px; margin-top: 2px; }
        .legend {
            margin-top: 12px;
            font-size: 11px;
            color: #4b5563;
            display: flex;
            gap: 14px;
            flex-wrap: wrap;
        }
        .legend span {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .swatch {
            width: 12px;
            height: 12px;
            border: 1px solid #9ca3af;
        }
        .swatch-sale { background: #dbeafe; }
        .swatch-return { background: #f3e8ff; }
        .swatch-receive { background: #d1e7dd; }
        .swatch-payment { background: #f8d7da; }
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
            .sheet { box-shadow: none; min-height: 277mm; }
            table.items th, table.items tfoot td,
            table.items tr.row-sale td,
            table.items tr.row-sales-return td,
            table.items tr.row-cash-receive td,
            table.items tr.row-cash-payment td {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
        @page { size: A4; margin: 10mm; }
    </style>
</head>
<body>
    <div class="toolbar">
        <span>Customer Ledger — {{ $customer->displayName() }}</span>
        <div>
            <a class="btn-back" href="{{ route('customers.index') }}">Back</a>
            <a class="btn-back" href="{{ route('customers.edit', $customer) }}">Edit</a>
            <button type="button" class="btn-print" onclick="window.print()">Print</button>
        </div>
    </div>

    <div class="page-wrap">
        <div class="sheet">
            <header class="header">
                <div>
                    <h1 class="company-name">{{ $settings->companyName() }}</h1>
                    <p class="company-meta">
                        {{ $settings->get('company_address') ?: '' }}
                        @if($settings->get('company_vat_number'))
                            <br>NTN {{ $settings->get('company_vat_number') }}
                        @endif
                        @if($settings->get('company_cr_number'))
                            · CR {{ $settings->get('company_cr_number') }}
                        @endif
                    </p>
                </div>
                <div class="doc-meta">
                    <p class="doc-label">Customer ledger</p>
                    <p class="doc-no">{{ ams_num($closingBalance) }}</p>
                    <p class="doc-date">Receivable balance</p>
                    <p class="doc-date">Opening: {{ ams_num($openingBalance) }}</p>
                </div>
            </header>

            <div class="parties">
                <div>
                    <div class="party-label">Company</div>
                    <div class="party-name">{{ $settings->companyName() }}</div>
                    <div class="party-meta">{{ $settings->get('company_address') ?: '—' }}</div>
                </div>
                <div>
                    <div class="party-label">Customer</div>
                    <div class="party-name">{{ $customer->displayName() }}</div>
                    <div class="party-meta">
                        {{ collect([
                            $customer->name !== $customer->displayName() ? $customer->name : null,
                            $customer->proprietor_name,
                            $customer->mobile ?: $customer->phone,
                            $customer->email,
                            $customer->address,
                            $customer->area,
                            $customer->city,
                        ])->filter()->join(' · ') ?: '—' }}
                        @if($customer->ntn)
                            <br>NTN {{ $customer->ntn }}
                        @endif
                        @if($customer->strn ?: $customer->vat_number)
                            · STRN {{ $customer->strn ?: $customer->vat_number }}
                        @endif
                    </div>
                </div>
            </div>

            <div class="items-wrap">
                <table class="items">
                    <colgroup>
                        <col class="col-num">
                        <col class="col-date">
                        <col class="col-ref">
                        <col class="col-desc">
                        <col class="col-debit">
                        <col class="col-credit">
                        <col class="col-bal">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Date</th>
                            <th>Ref</th>
                            <th>Description</th>
                            <th class="num">Debit</th>
                            <th class="num">Credit</th>
                            <th class="num">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($entries as $i => $entry)
                            <tr @class([
                                'row-sale' => ($entry['source'] ?? '') === 'sale',
                                'row-sales-return' => ($entry['source'] ?? '') === 'sales_return',
                                'row-cash-receive' => ($entry['source'] ?? '') === 'cash' && ($entry['tone'] ?? '') === 'receive',
                                'row-cash-payment' => ($entry['source'] ?? '') === 'cash' && ($entry['tone'] ?? '') === 'payment',
                            ])>
                                <td>{{ $i + 1 }}</td>
                                <td>{{ ams_date($entry['date']) }}</td>
                                <td>{{ $entry['ref'] }}</td>
                                <td>
                                    {{ $entry['description'] }}
                                    @if(!empty($entry['notes']))
                                        <div class="note">{{ $entry['notes'] }}</div>
                                    @endif
                                </td>
                                <td class="num">{{ (float) $entry['debit'] > 0 ? ams_num($entry['debit']) : '' }}</td>
                                <td class="num">{{ (float) $entry['credit'] > 0 ? ams_num($entry['credit']) : '' }}</td>
                                <td class="num">{{ ams_num($entry['balance']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="num" style="text-align:center;">No ledger entries yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($entries->isNotEmpty())
                        <tfoot>
                            <tr>
                                <td colspan="4" class="num">Total</td>
                                <td class="num">{{ ams_num($totalDebit) }}</td>
                                <td class="num">{{ ams_num($totalCredit) }}</td>
                                <td class="num">{{ ams_num($closingBalance) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>

            <div class="legend">
                <span><i class="swatch swatch-sale"></i> Sales invoice</span>
                <span><i class="swatch swatch-return"></i> Sales return</span>
                <span><i class="swatch swatch-receive"></i> Cash received</span>
                <span><i class="swatch swatch-payment"></i> Cash paid</span>
            </div>

            <p class="footer">Printed {{ ams_datetime($printedAt) }}</p>
        </div>
    </div>
</body>
</html>
