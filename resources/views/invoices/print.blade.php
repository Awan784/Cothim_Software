<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->invoice_no }} — Tax invoice</title>
    <style>
        body { font-family: Inter, Arial, sans-serif; color: #111827; margin: 32px; }
        h1 { font-size: 22px; margin: 0 0 4px; }
        .muted { color: #6b7280; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border-bottom: 1px solid #e5e7eb; padding: 8px; text-align: left; }
        .num { text-align: right; }
        .qr { margin-top: 24px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <button class="no-print" onclick="window.print()">Print</button>
    <h1>Tax invoice {{ $invoice->invoice_no }}</h1>
    <p class="muted">{{ $settings->companyName() }} · VAT {{ $settings->get('company_vat_number') ?: '—' }} · CR {{ $settings->get('company_cr_number') ?: '—' }}</p>
    <p>{{ $settings->get('company_address') }}</p>
    <p><strong>Customer:</strong> {{ $invoice->customer?->displayName() }}
        @if($invoice->customer?->ntn) · NTN {{ $invoice->customer->ntn }}@endif
        @if($invoice->customer?->strn ?: $invoice->customer?->vat_number) · STRN {{ $invoice->customer->strn ?: $invoice->customer->vat_number }}@endif
    </p>
    @if($invoice->customer?->address || $invoice->customer?->city)
        <p class="muted">{{ collect([$invoice->customer?->address, $invoice->customer?->area, $invoice->customer?->city])->filter()->join(', ') }}</p>
    @endif
    <p><strong>Date:</strong> {{ ams_date($invoice->invoice_date) }} · <strong>Type:</strong> {{ $invoice->type }}</p>
    <table>
        <thead>
            <tr><th>Description</th><th class="num">Qty</th><th class="num">Price</th><th class="num">VAT</th><th class="num">Total</th></tr>
        </thead>
        <tbody>
            @foreach($invoice->lines as $line)
                <tr>
                    <td>{{ $line->description }}</td>
                    <td class="num">{{ number_format((float) $line->quantity, 3) }}</td>
                    <td class="num">{{ number_format((float) $line->unit_price, 2) }}</td>
                    <td class="num">{{ number_format((float) $line->vat_amount, 2) }}</td>
                    <td class="num">{{ number_format((float) $line->line_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <p class="num"><strong>Subtotal</strong> {{ number_format((float) $invoice->subtotal, 2) }}<br>
    <strong>VAT</strong> {{ number_format((float) $invoice->vat_amount, 2) }}<br>
    <strong>Total</strong> {{ number_format((float) $invoice->total, 2) }}</p>
    <div class="qr">
        <p class="muted">ZATCA Phase 1 QR</p>
        <div id="qrcode"></div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script>
        new QRCode(document.getElementById('qrcode'), {
            text: @json($invoice->zatca_qr_payload ?? ''),
            width: 140,
            height: 140
        });
    </script>
</body>
</html>
