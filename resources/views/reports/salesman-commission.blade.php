<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Salesman Commission</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: "Segoe UI", system-ui, sans-serif; background: #eef1f5; color: #1a1a2e; }
        .toolbar { position: sticky; top: 0; z-index: 100; display: flex; justify-content: space-between; gap: 1rem; padding: 0.75rem 1.5rem; background: #1a1a2e; color: #fff; }
        .btn { display: inline-flex; align-items: center; padding: 0.45rem 1rem; border-radius: 6px; font-size: 0.875rem; font-weight: 600; text-decoration: none; border: none; cursor: pointer; }
        .btn-print { background: #17a2b8; color: #fff; }
        .btn-back { background: #fff; color: #1a1a2e; }
        .sheet { max-width: 1100px; margin: 1.5rem auto; background: #fff; padding: 2rem; }
        h1 { margin: 0 0 0.25rem; font-size: 1.4rem; }
        .meta { color: #555; margin-bottom: 1.25rem; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d7dbe3; padding: 0.45rem 0.6rem; font-size: 0.9rem; }
        th { background: #f3f4f6; text-align: left; }
        .num { text-align: right; }
        tfoot td { font-weight: 700; background: #f8fafc; }
        @media print {
            .toolbar { display: none; }
            body { background: #fff; }
            .sheet { margin: 0; padding: 0; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <div>Salesman Commission</div>
        <div>
            <button class="btn btn-print" type="button" onclick="window.print()">Print</button>
            <a class="btn btn-back" href="{{ route('reports.index') }}">Back</a>
        </div>
    </div>
    <div class="sheet">
        <h1>{{ $companyName }}</h1>
        <div class="meta">
            Salesman commission · {{ ams_date($fromDate) }} to {{ ams_date($toDate) }}
            @if($salesman) · {{ $salesman->name }}@endif
            · Printed {{ ams_datetime($printedAt) }}
        </div>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Invoice</th>
                    <th>Salesman</th>
                    <th>Customer</th>
                    <th class="num">Total</th>
                    <th class="num">Retain</th>
                    <th class="num">Commission</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $invoice)
                    <tr>
                        <td>{{ ams_date($invoice->invoice_date) }}</td>
                        <td>{{ $invoice->invoice_no }}</td>
                        <td>{{ $invoice->salesman?->name ?: '—' }}</td>
                        <td>{{ $invoice->customer?->displayName() ?: '—' }}</td>
                        <td class="num">{{ ams_num($invoice->total) }}</td>
                        <td class="num">{{ ams_num($invoice->company_retain_amount) }}</td>
                        <td class="num">{{ ams_num($invoice->salesman_commission_amount) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center">No salesman invoices in this period.</td></tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4">Totals</td>
                    <td class="num">{{ ams_num($totalSales) }}</td>
                    <td class="num">{{ ams_num($totalRetain) }}</td>
                    <td class="num">{{ ams_num($totalCommission) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</body>
</html>
