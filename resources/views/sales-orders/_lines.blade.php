<div class="card p-4 mb-3">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="text-end">Qty</th>
                    <th class="text-end">Price</th>
                    <th class="text-end">Disc %</th>
                    <th>Batch #</th>
                    <th class="text-end">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->lines as $line)
                    <tr>
                        <td>{{ $line->description }}</td>
                        <td class="text-end">{{ number_format((float) $line->quantity, 3) }}</td>
                        <td class="text-end">{{ number_format((float) $line->unit_price, 2) }}</td>
                        <td class="text-end">{{ number_format((float) $line->discount_rate, 2) }}%</td>
                        <td>{{ $line->stockItem?->batch_no ?: '—' }}</td>
                        <td class="text-end">{{ number_format((float) $line->line_total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr><td colspan="5" class="text-end">Subtotal</td><td class="text-end">{{ number_format((float) $order->subtotal + (float) $order->discount_amount, 2) }}</td></tr>
                @if((float) $order->discount_amount > 0)
                    <tr><td colspan="5" class="text-end">Discount</td><td class="text-end">{{ number_format((float) $order->discount_amount, 2) }}</td></tr>
                @endif
                <tr><td colspan="5" class="text-end"><strong>Total</strong></td><td class="text-end"><strong>{{ number_format((float) $order->total, 2) }}</strong></td></tr>
                <tr><td colspan="5" class="text-end">Company retain ({{ number_format((float) $order->company_retain_percent, 2) }}%)</td><td class="text-end">{{ number_format((float) $order->company_retain_amount, 2) }}</td></tr>
                <tr><td colspan="5" class="text-end">Salesman commission ({{ number_format((float) $order->salesman_commission_percent, 2) }}% of remaining)</td><td class="text-end">{{ number_format((float) $order->salesman_commission_amount, 2) }}</td></tr>
            </tfoot>
        </table>
    </div>
    @if($order->notes)
        <p class="mb-0 text-muted">Notes: {{ $order->notes }}</p>
    @endif
    @if($order->mode)
        <p class="mb-0 mt-1 text-muted">Mode: {{ $order->mode }}</p>
    @endif
    @if($order->builty_postal || $order->builty_exp)
        <p class="mb-0 mt-1 text-muted">
            Builty postal: {{ $order->builty_postal ?: '—' }}
            · Builty exp: {{ $order->builty_exp !== null ? number_format((float) $order->builty_exp, 2) : '—' }}
        </p>
    @endif
</div>
