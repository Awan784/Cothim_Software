<?php

namespace App\Services;

use App\Models\SalesInvoice;
use App\Models\Salesman;
use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use App\Models\StockItem;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class SalesOrderService
{
    public function __construct(
        private VatCalculator $vat,
        private SettingsService $settings,
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

        return DB::transaction(function () use ($order, $data, $computed, $salesman) {
            $payload = [
                'salesman_id' => $salesman->id,
                'customer_id' => $data['customer_id'],
                'order_date' => $data['order_date'],
                'notes' => $data['notes'] ?? null,
                'mode' => $this->normalizedMode($data['mode'] ?? null),
                'subtotal' => $computed['subtotal'],
                'discount_amount' => $computed['discount_amount'],
                'vat_amount' => $computed['vat_amount'],
                'total' => $computed['total'],
                'company_retain_percent' => 0,
                'salesman_commission_percent' => 0,
                'company_retain_amount' => 0,
                'salesman_commission_amount' => 0,
            ];

            if ($order) {
                $order->update($payload);
                $order->lines()->delete();
            } else {
                $order = $this->createWithNextNumber($payload);
            }

            foreach ($computed['lines'] as $line) {
                SalesOrderLine::create([
                    'sales_order_id' => $order->id,
                    'stock_item_id' => $line['stock_item_id'] ?? null,
                    'stock_item_lot_id' => $line['stock_item_lot_id'] ?? null,
                    'batch_no' => $line['batch_no'] ?? null,
                    'description' => $line['description'] ?? 'Item',
                    'print_note' => $line['print_note'] ?? null,
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

    public function confirm(SalesOrder $order, User $user, array $extra = []): SalesInvoice
    {
        if (! $order->isPending()) {
            throw new InvalidArgumentException('Only pending orders can be confirmed.');
        }

        $order->load(['lines', 'salesman']);

        if ($order->lines->isEmpty()) {
            throw new InvalidArgumentException('Add lines before confirming.');
        }

        $builty = $this->normalizedBuilty($extra);

        return DB::transaction(function () use ($order, $user, $builty) {
            $order->fill($builty);
            $order->save();

            $lines = $order->lines->map(function (SalesOrderLine $line) {
                try {
                    return StockItem::applyLotToLine([
                        'stock_item_id' => $line->stock_item_id,
                        'stock_item_lot_id' => $line->stock_item_lot_id,
                        'batch_no' => $line->batch_no,
                        'description' => $line->description,
                        'print_note' => $line->print_note,
                        'quantity' => $line->quantity,
                        'unit_price' => $line->unit_price,
                        'discount_rate' => $line->discount_rate,
                        'vat_rate' => $line->vat_rate,
                    ]);
                } catch (ValidationException $e) {
                    throw new InvalidArgumentException(collect($e->errors())->flatten()->first() ?: 'Select a batch for each line.');
                }
            })->all();

            $invoice = $this->invoices->generate([
                'customer_id' => $order->customer_id,
                'invoice_date' => $order->order_date?->toDateString() ?? now()->toDateString(),
                'due_date' => null,
                'type' => 'simplified',
                'notes' => $order->notes,
                'mode' => $order->mode,
                'builty_postal' => $order->builty_postal,
                'builty_exp' => $order->builty_exp,
                'salesman_id' => $order->salesman_id,
                'sales_order_id' => $order->id,
            ], $lines, $user);

            $order->update([
                'status' => SalesOrder::STATUS_CONFIRMED,
                'sales_invoice_id' => $invoice->id,
                'confirmed_by' => $user->id,
                'confirmed_at' => now(),
                'company_retain_percent' => 0,
                'salesman_commission_percent' => 0,
                'company_retain_amount' => 0,
                'salesman_commission_amount' => (float) $invoice->salesman_commission_amount,
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

    public function softDelete(SalesOrder $order): void
    {
        DB::transaction(function () use ($order) {
            if ($order->sales_invoice_id) {
                $invoice = SalesInvoice::query()->find($order->sales_invoice_id);
                if ($invoice) {
                    $this->invoices->softDelete($invoice, false);
                }
            }

            if (! $order->trashed()) {
                $order->delete();
            }
        });
    }

    /**
     * @param  array<string, mixed>  $line
     * @return array<string, mixed>
     */
    public function hydrateLine(array $line): array
    {
        $line['discount_rate'] = $line['discount_rate'] ?? 0;
        $line['vat_rate'] = $line['vat_rate'] ?? 0;

        if (empty($line['stock_item_id'])) {
            return $line;
        }

        $item = StockItem::query()->find($line['stock_item_id']);
        if ($item && empty($line['description'])) {
            $line['description'] = $item->name;
        }

        $note = trim((string) ($line['print_note'] ?? ''));
        $line['print_note'] = $note === '' ? null : $note;

        return StockItem::applyLotToLine($line);
    }

    public function updateBuilty(SalesOrder $order, array $extra): SalesOrder
    {
        $builty = $this->normalizedBuilty($extra);

        return DB::transaction(function () use ($order, $builty) {
            $order->update($builty);

            if ($order->sales_invoice_id) {
                SalesInvoice::query()->whereKey($order->sales_invoice_id)->update($builty);
            }

            return $order->fresh(['invoice', 'customer']);
        });
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array{builty_postal: ?string, builty_exp: ?float}
     */
    public function normalizedBuilty(array $extra): array
    {
        $postal = trim((string) ($extra['builty_postal'] ?? ''));
        $exp = $extra['builty_exp'] ?? null;

        return [
            'builty_postal' => $postal === '' ? null : $postal,
            'builty_exp' => $exp === null || $exp === '' ? null : round((float) $exp, 2),
        ];
    }

    private function normalizedMode(mixed $mode): ?string
    {
        $mode = trim((string) $mode);

        return $mode === '' ? null : $mode;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function createWithNextNumber(array $payload): SalesOrder
    {
        for ($attempt = 0; $attempt < 8; $attempt++) {
            try {
                return SalesOrder::create($payload + [
                    'order_no' => SalesOrder::nextNumber(),
                    'status' => SalesOrder::STATUS_PENDING,
                ]);
            } catch (QueryException $e) {
                if (! $this->isOrderNoCollision($e) || $attempt === 7) {
                    throw $e;
                }
            }
        }

        throw new InvalidArgumentException('Could not assign an order number. Try again.');
    }

    private function isOrderNoCollision(QueryException $e): bool
    {
        if ((string) $e->getCode() !== '23000') {
            return false;
        }

        return str_contains($e->getMessage(), 'order_no')
            || str_contains($e->getMessage(), 'sales_orders_organization_id_order_no_unique');
    }
}
