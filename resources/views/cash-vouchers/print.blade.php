<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $isReceive ? 'Cash Receipt' : 'Cash Payment' }} — {{ $voucher->voucher_no }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Segoe UI", system-ui, -apple-system, sans-serif;
            background: #e9ecef;
            color: #1a1a2e;
        }
        .toolbar {
            position: sticky;
            top: 0;
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.65rem 1.25rem;
            background: #1a1a2e;
            color: #fff;
        }
        .toolbar a, .toolbar button {
            padding: 0.4rem 0.9rem;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
            border: none;
            cursor: pointer;
        }
        .btn-print { background: {{ $isReceive ? '#198754' : '#dc3545' }}; color: #fff; }
        .btn-back { background: #6c757d; color: #fff; margin-right: 0.5rem; }

        .page-wrap {
            display: flex;
            justify-content: center;
            padding: 1rem;
        }
        /* Half A4: 210mm × 148.5mm */
        .receipt {
            width: 210mm;
            min-height: 148.5mm;
            max-height: 148.5mm;
            background: #fff;
            border: 2px solid {{ $isReceive ? '#198754' : '#dc3545' }};
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12);
            padding: 8mm 10mm;
            display: flex;
            flex-direction: column;
        }
        .receipt-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            padding-bottom: 4mm;
            border-bottom: 2px solid {{ $isReceive ? '#198754' : '#dc3545' }};
        }
        .brand img { height: 14mm; width: auto; }
        .company-name {
            font-size: 14pt;
            font-weight: 700;
            margin: 0;
            line-height: 1.2;
        }
        .company-sub { font-size: 8pt; color: #6c757d; margin: 0; }
        .voucher-meta {
            text-align: right;
            font-size: 8.5pt;
            line-height: 1.45;
        }
        .voucher-meta strong { display: inline-block; min-width: 72px; }
        .form-title {
            text-align: center;
            margin: 4mm 0 3mm;
            padding: 2.5mm 0;
            font-size: 13pt;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #fff;
            background: {{ $isReceive ? 'linear-gradient(135deg, #28a745, #198754)' : 'linear-gradient(135deg, #e4606d, #dc3545)' }};
            border-radius: 4px;
        }
        .body-grid {
            flex: 1;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3mm 6mm;
            font-size: 9pt;
            margin-bottom: 3mm;
        }
        .field { margin: 0; }
        .field-label {
            font-size: 7.5pt;
            text-transform: uppercase;
            color: #6c757d;
            letter-spacing: 0.04em;
            margin-bottom: 0.5mm;
        }
        .field-value {
            font-size: 10pt;
            font-weight: 600;
            border-bottom: 1px dotted #adb5bd;
            padding-bottom: 1mm;
            min-height: 5mm;
        }
        .field.full { grid-column: 1 / -1; }
        .amount-box {
            grid-column: 1 / -1;
            margin: 2mm 0;
            padding: 4mm 6mm;
            text-align: center;
            border: 2px dashed {{ $isReceive ? '#198754' : '#dc3545' }};
            border-radius: 6px;
            background: {{ $isReceive ? '#f0fdf4' : '#fff5f5' }};
        }
        .amount-label {
            font-size: 8pt;
            text-transform: uppercase;
            color: #495057;
            margin-bottom: 1mm;
        }
        .amount-value {
            font-size: 22pt;
            font-weight: 700;
            color: {{ $isReceive ? '#198754' : '#dc3545' }};
            font-variant-numeric: tabular-nums;
        }
        .amount-words {
            margin-top: 2mm;
            font-size: 8.5pt;
            font-style: italic;
            color: #495057;
        }
        .signatures {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 4mm;
            margin-top: auto;
            padding-top: 3mm;
            border-top: 1px solid #dee2e6;
            font-size: 8pt;
        }
        .sig-line {
            border-top: 1px solid #212529;
            margin-top: 10mm;
            padding-top: 1mm;
            text-align: center;
            color: #495057;
        }

        @media print {
            body { background: #fff; }
            .toolbar { display: none !important; }
            .page-wrap { padding: 0; }
            .receipt {
                box-shadow: none;
                max-height: none;
                page-break-after: avoid;
            }
            .form-title, .receipt-header {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
        @page {
            size: 210mm 148.5mm;
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="toolbar no-print">
        <span>{{ $isReceive ? 'Cash Receipt' : 'Cash Payment' }} — {{ $voucher->voucher_no }}</span>
        <div>
            <a href="{{ route('cash-vouchers.index') }}" class="btn-back">Back</a>
            <button type="button" class="btn-print" onclick="window.print()">Print</button>
        </div>
    </div>

    <div class="page-wrap">
        <div class="receipt">
            <header class="receipt-header">
                <div class="brand">
                    <img src="{{ asset('assets/img/brand/dark.svg') }}" alt="">
                    <p class="company-name">{{ $companyName }}</p>
                    <p class="company-sub">Cash Voucher</p>
                </div>
                <div class="voucher-meta">
                    <div><strong>Voucher No:</strong> {{ $voucher->voucher_no }}</div>
                    <div><strong>Date:</strong> {{ ams_date($voucher->voucher_date) }}</div>
                    <div><strong>Printed:</strong> {{ ams_datetime($printedAt) }}</div>
                </div>
            </header>

            <div class="form-title">
                {{ $isReceive ? 'Cash Receiving Voucher' : 'Cash Payment Voucher' }}
            </div>

            <div class="body-grid">
                <div class="field full">
                    <div class="field-label">{{ $isReceive ? 'Received From' : 'Paid To' }}</div>
                    <div class="field-value">{{ $partyName }}</div>
                </div>
                <div class="field">
                    <div class="field-label">Account Type</div>
                    <div class="field-value">{{ $partyTypeLabel }}</div>
                </div>
                <div class="field">
                    <div class="field-label">Payment Method</div>
                    <div class="field-value">{{ $paymentLabel }}</div>
                </div>
                @if($voucher->reference)
                    <div class="field">
                        <div class="field-label">Reference</div>
                        <div class="field-value">{{ $voucher->reference }}</div>
                    </div>
                @endif
                <div class="field {{ $voucher->reference ? '' : 'full' }}">
                    <div class="field-label">Transaction Type</div>
                    <div class="field-value">{{ $isReceive ? 'Cash Receive' : 'Cash Payment' }}</div>
                </div>
                @if($voucher->notes)
                    <div class="field full">
                        <div class="field-label">Remarks / Notes</div>
                        <div class="field-value">{{ $voucher->notes }}</div>
                    </div>
                @endif

                <div class="amount-box">
                    <div class="amount-label">Amount {{ $isReceive ? 'Received' : 'Paid' }}</div>
                    <div class="amount-value">{{ number_format((float) $voucher->amount, 2) }}</div>
                    @if($amountInWords)
                        <div class="amount-words">{{ $amountInWords }}</div>
                    @endif
                </div>
            </div>

            <div class="signatures">
                <div>
                    <div class="sig-line">{{ $isReceive ? 'Received By' : 'Paid By' }}</div>
                </div>
                <div>
                    <div class="sig-line">{{ $isReceive ? 'Paid By' : 'Received By' }}</div>
                </div>
                <div>
                    <div class="sig-line">Authorized Signature</div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
