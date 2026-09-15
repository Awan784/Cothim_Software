<?php

namespace App\Services;

use App\Models\SalesInvoice;
use App\Models\Salesman;
use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use App\Models\StockItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SalesOrderService
{
    public function __construct(
        private VatCalculator $vat,
        private SettingsService $settings,
        private CommissionService $commission,
        private SalesInvoiceService $invoices,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  list<array<string, mixed>>  $lines
     */
    public function save(?SalesOrder $order, array $data, array $lines, Salesman $salesman): SalesOrder
    {
        if ($order && ! $order->isPending()) {
            throw new InvalidArgumentException('Only pending orders can be edited.');
        }

        $computed = $this->vat->document($lines, $this->settings->vatRate());
        if ($computed['lines'] === []) {
            throw new InvalidArgumentException('Add at least one order line.');
        }

        $snapshot = $this->commission->snapshot((float) $computed['total'], $salesman);

        return DB::transaction(function () use ($order, $data, $computed, $salesman, $snapshot) {
            $payload = [
                'salesman_id' => $salesman->id,
                'customer_id' => $data['customer_id'],
                'order_date' => $data['order_date'],
                'notes' => $data['notes'] ?? null,
                'subtotal' => $computed['subtotal'],
                'discount_amount' => $computed['discount_amount'],
                'vat_amount' => $computed['vat_amount'],
                'total' => $computed['total'],
            ] + $snapshot;

            if ($order) {
                $order->update($payload);
                $order->lines()->delete();
            } else {
                $order = SalesOrder::create($payload + [
                    'order_no' => SalesOrder::nextNumber(),
                    'status' => SalesOrder::STATUS_PENDING,
                ]);
            }

            foreach ($computed['lines'] as $line) {
                SalesOrderLine::create([
                    'sales_order_id' => $order->id,
                    'stock_item_id' => $line['stock_item_id'] ?? null,
                    'description' => $line['description'] ?? 'Item',
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'discount_rate' => $line['discount_rate'],
                    'discount_amount' => $line['discount_amount'],
                    'vat_rate' => $line['vat_rate'],
                    'line_net' => $line['line_net'],
                    'vat_amount' => $line['vat_amount'],
                    'line_total' => $line['line_total'],
                    'sort_order' => $line['sort_order'],
                ]);
            }

            return $order->fresh(['lines', 'customer', 'salesman']);
        });
    }

    public function confirm(SalesOrder $order, User $user): SalesInvoice
    {
        if (! $order->isPending()) {
            throw new InvalidArgumentException('Only pending orders can be confirmed.');
        }

        $order->load(['lines', 'salesman']);

        if ($order->lines->isEmpty()) {
            throw new InvalidArgumentException('Add lines before confirming.');
        }

        return DB::transaction(function () use ($order, $user) {
            $lines = $order->lines->map(fn (SalesOrderLine $line) => [
                'stock_item_id' => $line->stock_item_id,
                'description' => $line->description,
                'quantity' => $line->quantity,
                'unit_price' => $line->unit_price,
                'discount_rate' => $line->discount_rate,
                'vat_rate' => $line->vat_rate,
            ])->all();

            $invoice = $this->invoices->generate([
                'customer_id' => $order->customer_id,
                'invoice_date' => $order->order_date?->toDateString() ?? now()->toDateString(),
                'due_date' => null,
                'type' => 'simplified',
                'notes' => $order->notes,
                'salesman_id' => $order->salesman_id,
                'sales_order_id' => $order->id,
                'company_retain_percent' => $order->company_retain_percent,
                'salesman_commission_percent' => $order->salesman_commission_percent,
                'company_retain_amount' => $order->company_retain_amount,
                'salesman_commission_amount' => $order->salesman_commission_amount,
            ], $lines, $user);

            $split = $this->commission->split(
                (float) $invoice->total,
                (float) $order->company_retain_percent,
                (float) $order->salesman_commission_percent
            );

            $invoice->fill($split + [
                'salesman_id' => $order->salesman_id,
                'sales_order_id' => $order->id,
            ]);
            $invoice->save();

            $order->update([
                'status' => SalesOrder::STATUS_CONFIRMED,
                'sales_invoice_id' => $invoice->id,
                'confirmed_by' => $user->id,
                'confirmed_at' => now(),
                'company_retain_amount' => $split['company_retain_amount'],
                'salesman_commission_amount' => $split['salesman_commission_amount'],
            ]);

            return $invoice->fresh(['customer', 'lines']);
        });
    }

    public function reject(SalesOrder $order, ?string $reason = null): SalesOrder
    {
        if (! $order->isPending()) {
            throw new InvalidArgumentException('Only pending orders can be rejected.');
        }

        $order->update([
            'status' => SalesOrder::STATUS_REJECTED,
            'rejected_at' => now(),
            'reject_reason' => $reason,
        ]);

        return $order->fresh();
    }

    public function deletePending(SalesOrder $order): void
    {
        if (! $order->isPending()) {
            throw new InvalidArgumentException('Only pending orders can be deleted.');
        }

        $order->delete();
    }

    /**
     * @param  array<string, mixed>  $line
     * @return array<string, mixed>
     */
    public function hydrateLine(array $line): array
    {
        $line['discount_rate'] = $line['discount_rate'] ?? 0;

        if (empty($line['stock_item_id'])) {
            return $line;
        }

        $item = StockItem::query()->find($line['stock_item_id']);
        if ($item && empty($line['description'])) {
            $line['description'] = $item->name;
        }

        return $line;
    }
}
