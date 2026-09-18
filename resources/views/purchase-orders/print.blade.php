<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $purchaseOrder->po_no }} — Purchase Order</title>
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
        .parties {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 18px 0 0;
            padding-bottom: 16px;
        }
        .items-wrap {
            width: 100%;
            margin-top: 4px;
            clear: both;
        }
        table.items {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 12.5px;
            border: 1px solid #000;
        }
        table.items col.col-num { width: 36px; }
        table.items col.col-item { width: auto; }
        table.items col.col-unit { width: 70px; }
        table.items col.col-qty { width: 80px; }
        table.items col.col-rate { width: 100px; }
        table.items col.col-amt { width: 110px; }
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
        .note { color: #6b7280; font-size: 11px; margin-top: 2px; }
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
        .words {
            margin-top: 16px;
            font-size: 12px;
            color: #374151;
        }
        .po-notes {
            margin-top: 10px;
            font-size: 12px;
            color: #374151;
        }
        .bottom {
            margin-top: auto;
            padding-top: 36px;
        }
        .sigs {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 24px;
            font-size: 11px;
            color: #4b5563;
        }
        .sig-line {
            border-top: 1px solid #111827;
            padding-top: 6px;
            text-align: center;
        }
        .footer {
            margin-top: 28px;
            font-size: 10px;
            color: #9ca3af;
            text-align: center;
        }
        @media print {
            body { background: #fff; }
            .toolbar { display: none !important; }
            .page-wrap { padding: 0; }
            .sheet { box-shadow: none; min-height: 277mm; }
            table.items th, table.items tfoot td {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
        @page { size: A4; margin: 10mm; }
    </style>
</head>
<body>
    <div class="toolbar">
        <span>Purchase Order {{ $purchaseOrder->po_no }}</span>
        <div>
            <a class="btn-back" href="{{ route('purchase-orders.index') }}">Back</a>
            <button type="button" class="btn-print" onclick="window.print()">Print</button>
        </div>
    </div>

    <div class="page-wrap">
        <div class="sheet">
            <header class="header">
                <x-print-company-brand :settings="$settings" />
                <div class="doc-meta">
                    <p class="doc-label">Purchase order</p>
                    <p class="doc-no">{{ $purchaseOrder->po_no }}</p>
                    <p class="doc-date">Date: {{ ams_date($purchaseOrder->po_date) }}</p>
                </div>
            </header>

            <div class="parties">
                <div>
                    <div class="party-label">From</div>
                    <div class="party-name">{{ $settings->companyName() }}</div>
                    <div class="party-meta">{{ $settings->contactLine() }}</div>
                </div>
                <div>
                    <div class="party-label">Supplier</div>
                    <div class="party-name">{{ $purchaseOrder->supplier?->name ?: '—' }}</div>
                    <div class="party-meta">
                        {{ collect([
                            $purchaseOrder->supplier?->phone,
                            $purchaseOrder->supplier?->email,
                            $purchaseOrder->supplier?->address,
                            $purchaseOrder->supplier?->city,
                        ])->filter()->join(' · ') ?: '—' }}
                    </div>
                </div>
            </div>

            <div class="items-wrap">
                <table class="items">
                    <colgroup>
                        <col class="col-num">
                        <col class="col-item">
                        <col class="col-unit">
                        <col class="col-qty">
                        <col class="col-rate">
                        <col class="col-amt">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Item</th>
                            <th>Unit</th>
                            <th class="num">Qty</th>
                            <th class="num">Rate</th>
                            <th class="num">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($purchaseOrder->items as $i => $item)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>
                                    {{ $item->displayName() }}
                                    @if($item->note)
                                        <div class="note">{{ $item->note }}</div>
                                    @endif
                                </td>
                                <td>{{ strtoupper($item->unit ?: 'PCS') }}</td>
                                <td class="num">{{ ams_num($item->quantity) }}</td>
                                <td class="num">{{ ams_num($item->unit_price) }}</td>
                                <td class="num">{{ ams_num($item->line_total) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="grand">
                            <td colspan="5" class="num">Total</td>
                            <td class="num">{{ ams_num($purchaseOrder->total_amount) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <p class="words"><strong>Amount in words:</strong> {{ $amountInWords }}</p>

            @if($purchaseOrder->notes)
                <p class="po-notes"><strong>Notes:</strong> {{ $purchaseOrder->notes }}</p>
            @endif

            <div class="bottom">
                <div class="sigs">
                    <div class="sig-line">Prepared by</div>
                    <div class="sig-line">Supplier</div>
                    <div class="sig-line">Authorized by</div>
                </div>
                <p class="footer">Printed {{ ams_datetime($printedAt) }}</p>
            </div>
        </div>
    </div>
</body>
</html>
