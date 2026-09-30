<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\PurchaseReturn;
use App\Models\SalesInvoice;
use App\Models\SalesReturn;
use App\Models\StockItem;
use App\Models\StockItemLot;
use App\Models\StockMovement;
use InvalidArgumentException;

class InventoryService
{
    /**
     * @param  array{unit_cost?: mixed, moved_at?: mixed, reference?: ?string, notes?: ?string, source_type?: ?string, source_id?: ?int, batch_no?: ?string, stock_item_lot_id?: ?int, expiry_date?: mixed}  $meta
     */
    public function receive(int $stockItemId, float $quantity, array $meta = []): void
    {
        $qty = round($quantity, 2);
        if ($qty <= 0) {
            return;
        }

        $lot = $this->lotForReceive($stockItemId, $meta);
        $lot->quantity = round((float) $lot->quantity + $qty, 2);
        if (! empty($meta['expiry_date'])) {
            $lot->expiry_date = $meta['expiry_date'];
        }
        $lot->save();

        $this->syncItemFromLots($stockItemId);
        $this->move($stockItemId, 'in', $qty, $meta, (int) $lot->id);
    }

    /**
     * @param  array{unit_cost?: mixed, moved_at?: mixed, reference?: ?string, notes?: ?string, source_type?: ?string, source_id?: ?int, batch_no?: ?string, stock_item_lot_id?: ?int}  $meta
     */
    public function issue(int $stockItemId, float $quantity, array $meta = []): void
    {
        $remaining = round($quantity, 2);
        if ($remaining <= 0) {
            return;
        }

        $lotId = filled($meta['stock_item_lot_id'] ?? null) ? (int) $meta['stock_item_lot_id'] : null;
        if ($lotId) {
            $this->issueFromLot($stockItemId, $lotId, $remaining, $meta);

            return;
        }

        $lots = StockItemLot::query()
            ->where('stock_item_id', $stockItemId)
            ->where('quantity', '>', 0)
            ->orderBy('received_at')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($lots as $lot) {
            if ($remaining <= 0.009) {
                break;
            }
            $take = min($remaining, (float) $lot->quantity);
            $this->issueFromLot($stockItemId, (int) $lot->id, $take, $meta);
            $remaining = round($remaining - $take, 2);
        }

        if ($remaining > 0.009) {
            throw new InvalidArgumentException('Not enough stock for this item.');
        }
    }

    public function revertPurchaseOrder(PurchaseOrder $purchaseOrder): void
    {
        if ($this->revertSourceMovements('purchase_order', $purchaseOrder->id)) {
            return;
        }

        $purchaseOrder->loadMissing('items');
        foreach ($purchaseOrder->items as $item) {
            if (! $item->stock_item_id) {
                continue;
            }
            $this->adjustLotQty(
                $item->stock_item_lot_id ?? null,
                (int) $item->stock_item_id,
                -((float) $item->quantity),
                $item->batch_no ?? null
            );
            $this->syncItemFromLots((int) $item->stock_item_id);
        }
    }

    public function revertPurchaseReturn(PurchaseReturn $purchaseReturn): void
    {
        if ($this->revertSourceMovements('purchase_return', $purchaseReturn->id)) {
            return;
        }

        $purchaseReturn->loadMissing('items');
        foreach ($purchaseReturn->items as $item) {
            if (! $item->stock_item_id) {
                continue;
            }
            $this->adjustLotQty(null, (int) $item->stock_item_id, (float) $item->quantity, null);
            $this->syncItemFromLots((int) $item->stock_item_id);
        }
    }

    public function revertSalesInvoice(SalesInvoice $invoice): void
    {
        if ($this->revertSourceMovements('sales_invoice', $invoice->id)) {
            return;
        }

        $invoice->loadMissing('lines');
        foreach ($invoice->lines as $line) {
            $stockItemId = $line->stock_item_id;
            if (! $stockItemId && $line->description) {
                $stockItemId = StockItem::query()->where('name', $line->description)->value('id');
            }
            if (! $stockItemId) {
                continue;
            }

            $this->adjustLotQty(
                $line->stock_item_lot_id ?? null,
                (int) $stockItemId,
                (float) $line->quantity,
                $line->batch_no ?? null
            );
            $this->syncItemFromLots((int) $stockItemId);
        }
    }

    public function revertSalesReturn(SalesReturn $salesReturn): void
    {
        if ($this->revertSourceMovements('sales_return', $salesReturn->id)) {
            return;
        }

        $salesReturn->loadMissing('items');
        foreach ($salesReturn->items as $item) {
            if (! $item->stock_item_id) {
                continue;
            }
            $this->adjustLotQty(
                $item->stock_item_lot_id ?? null,
                (int) $item->stock_item_id,
                -((float) $item->quantity),
                $item->batch_no ?? null
            );
            $this->syncItemFromLots((int) $item->stock_item_id);
        }
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function issueFromLot(int $stockItemId, int $lotId, float $quantity, array $meta): void
    {
        $qty = round($quantity, 2);
        $lot = StockItemLot::query()
            ->where('stock_item_id', $stockItemId)
            ->whereKey($lotId)
            ->lockForUpdate()
            ->first();

        if (! $lot) {
            throw new InvalidArgumentException('Select a valid batch for this item.');
        }

        if ((float) $lot->quantity + 0.009 < $qty) {
            throw new InvalidArgumentException(
                'Batch '.$lot->batchLabel().' has only '.number_format((float) $lot->quantity, 2).' remaining. Add another line for the other batch.'
            );
        }

        $lot->quantity = round((float) $lot->quantity - $qty, 2);
        $lot->save();
        $this->syncItemFromLots($stockItemId);
        $this->move($stockItemId, 'out', $qty, $meta, (int) $lot->id);
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function lotForReceive(int $stockItemId, array $meta): StockItemLot
    {
        $item = StockItem::whereKey($stockItemId)->lockForUpdate()->firstOrFail();
        $lotId = filled($meta['stock_item_lot_id'] ?? null) ? (int) $meta['stock_item_lot_id'] : null;
        if ($lotId) {
            $lot = StockItemLot::query()
                ->where('stock_item_id', $stockItemId)
                ->whereKey($lotId)
                ->lockForUpdate()
                ->first();
            if ($lot) {
                return $lot;
            }
        }

        $batch = StockItemLot::normalizeBatch($meta['batch_no'] ?? null);
        $lot = StockItemLot::query()
            ->where('stock_item_id', $stockItemId)
            ->where('batch_no', $batch)
            ->lockForUpdate()
            ->first();

        if ($lot) {
            return $lot;
        }

        return StockItemLot::create([
            'organization_id' => $item->organization_id,
            'stock_item_id' => $stockItemId,
            'batch_no' => $batch,
            'expiry_date' => $meta['expiry_date'] ?? $item->expiry_date,
            'quantity' => 0,
            'received_at' => $meta['moved_at'] ?? now(),
        ]);
    }

    private function adjustLotQty(?int $lotId, int $stockItemId, float $delta, ?string $batchNo): void
    {
        $delta = round($delta, 2);
        if ($delta == 0.0) {
            return;
        }

        $lot = null;
        if ($lotId) {
            $lot = StockItemLot::query()->whereKey($lotId)->lockForUpdate()->first();
        }
        if (! $lot) {
            $batch = StockItemLot::normalizeBatch($batchNo);
            $lot = StockItemLot::query()
                ->where('stock_item_id', $stockItemId)
                ->where('batch_no', $batch)
                ->lockForUpdate()
                ->first();
        }
        if (! $lot && $delta > 0) {
            $oldest = StockItemLot::query()
                ->where('stock_item_id', $stockItemId)
                ->orderBy('received_at')
                ->orderBy('id')
                ->lockForUpdate()
                ->first();
            $lot = $oldest;
        }

        if (! $lot) {
            if ($delta <= 0) {
                $item = StockItem::whereKey($stockItemId)->lockForUpdate()->first();
                if ($item) {
                    $item->quantity = round((float) $item->quantity + $delta, 2);
                    $item->save();
                }

                return;
            }

            $item = StockItem::whereKey($stockItemId)->firstOrFail();
            $lot = StockItemLot::create([
                'organization_id' => $item->organization_id,
                'stock_item_id' => $stockItemId,
                'batch_no' => StockItemLot::normalizeBatch($batchNo),
                'quantity' => 0,
                'received_at' => now(),
            ]);
        }

        $lot->quantity = round((float) $lot->quantity + $delta, 2);
        $lot->save();
    }

    private function syncItemFromLots(int $stockItemId): void
    {
        $item = StockItem::whereKey($stockItemId)->lockForUpdate()->first();
        if (! $item) {
            return;
        }

        $item->syncQuantityFromLots();
    }

    private function revertSourceMovements(string $sourceType, int $sourceId): bool
    {
        $moves = StockMovement::query()
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        if ($moves->isEmpty()) {
            return false;
        }

        $itemIds = [];
        foreach ($moves as $move) {
            $qty = (float) $move->quantity;
            $delta = $move->type === 'in' ? -$qty : $qty;
            $this->adjustLotQty(
                $move->stock_item_lot_id,
                (int) $move->stock_item_id,
                $delta,
                null
            );
            $itemIds[(int) $move->stock_item_id] = true;
        }

        StockMovement::query()
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->delete();

        foreach (array_keys($itemIds) as $itemId) {
            $this->syncItemFromLots((int) $itemId);
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function move(int $stockItemId, string $type, float $quantity, array $meta, ?int $lotId): void
    {
        StockMovement::create([
            'stock_item_id' => $stockItemId,
            'stock_item_lot_id' => $lotId,
            'type' => $type,
            'quantity' => $quantity,
            'unit_cost' => $meta['unit_cost'] ?? null,
            'moved_at' => $meta['moved_at'] ?? now(),
            'reference' => $meta['reference'] ?? null,
            'notes' => $meta['notes'] ?? null,
            'source_type' => $meta['source_type'] ?? null,
            'source_id' => $meta['source_id'] ?? null,
        ]);
    }
}
