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
     * @param  array{unit_cost?: mixed, moved_at?: mixed, reference?: ?string, notes?: ?string, source_type?: ?string, source_id?: ?int, batch_no?: ?string, stock_item_lot_id?: ?int, expiry_date?: mixed, manufactured_at?: mixed}  $meta
     */
    public function receive(int $stockItemId, float $quantity, array $meta = []): ?StockItemLot
    {
        $qty = round($quantity, 2);
        if ($qty <= 0) {
            return null;
        }

        $lot = $this->lotForReceive($stockItemId, $meta);
        $lot->quantity = round((float) $lot->quantity + $qty, 2);
        if (! empty($meta['expiry_date'])) {
            $lot->expiry_date = $meta['expiry_date'];
        }
        if (! empty($meta['manufactured_at'])) {
            $lot->manufactured_at = $meta['manufactured_at'];
        }
        $lot->save();

        $this->syncItemFromLots($stockItemId);
        $this->move($stockItemId, 'in', $qty, $meta, (int) $lot->id);

        return $lot;
    }

    /**
     * @param  array{unit_cost?: mixed, moved_at?: mixed, reference?: ?string, notes?: ?string, source_type?: ?string, source_id?: ?int, batch_no?: ?string, stock_item_lot_id?: ?int}  $meta
     */
    public function issue(int $stockItemId, float $quantity, array $meta = []): ?StockItemLot
    {
        $remaining = round($quantity, 2);
        if ($remaining <= 0) {
            return null;
        }

        $lotId = (int) ($meta['stock_item_lot_id'] ?? 0);
        if ($lotId > 0) {
            $lot = $this->saleLot($stockItemId, $lotId);
            $this->issueFromLot($stockItemId, (int) $lot->id, $remaining, $meta);

            return $lot;
        }

        $item = StockItem::whereKey($stockItemId)->lockForUpdate()->firstOrFail();
        $lots = $item->lots()->orderBy('received_at')->orderBy('id')->lockForUpdate()->get();
        if ($lots->isEmpty()) {
            $item->seedOpeningLot();
            $lots = $item->lots()->orderBy('received_at')->orderBy('id')->lockForUpdate()->get();
        }

        $last = $lots->last();
        foreach ($lots as $lot) {
            if ($remaining <= 0) {
                break;
            }

            $onHand = round((float) $lot->quantity, 2);
            $isLast = $last && (int) $lot->id === (int) $last->id;
            if ($onHand <= 0 && ! $isLast) {
                continue;
            }

            $take = $isLast ? $remaining : min($onHand, $remaining);
            if ($take <= 0) {
                continue;
            }

            $this->issueFromLot($stockItemId, (int) $lot->id, $take, $meta);
            $remaining = round($remaining - $take, 2);
        }

        return null;
    }

    /**
     * Oldest batch that still has qty. A chosen batch with qty 0 is skipped
     * when a later batch still has stock. If every batch is empty, the chosen
     * batch (or the oldest) is used so the sale can go negative.
     */
    private function saleLot(int $stockItemId, int $requestedId): StockItemLot
    {
        $item = StockItem::whereKey($stockItemId)->lockForUpdate()->firstOrFail();
        $lots = $item->lots()->orderBy('received_at')->orderBy('id')->lockForUpdate()->get();
        if ($lots->isEmpty()) {
            $item->seedOpeningLot();
            $lots = $item->lots()->orderBy('received_at')->orderBy('id')->lockForUpdate()->get();
        }

        $requested = $lots->firstWhere('id', $requestedId);
        if ($requested && (float) $requested->quantity > 0) {
            return $requested;
        }

        $withStock = $lots->first(fn (StockItemLot $lot) => (float) $lot->quantity > 0);
        if ($withStock) {
            return $withStock;
        }

        return $requested ?? $lots->first();
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
        $batch = StockItemLot::normalizeBatch($meta['batch_no'] ?? null);
        $lotId = (int) ($meta['stock_item_lot_id'] ?? 0);

        $lot = null;
        if ($lotId > 0) {
            $lot = $item->lots()->whereKey($lotId)->lockForUpdate()->first();
        }
        if (! $lot) {
            $lot = $item->lots()->where('batch_no', $batch)->lockForUpdate()->first();
        }

        if (! $lot) {
            $lot = $item->lots()->create([
                'batch_no' => $batch,
                'manufactured_at' => $meta['manufactured_at'] ?? null,
                'expiry_date' => $meta['expiry_date'] ?? $item->expiry_date,
                'quantity' => 0,
                'received_at' => $meta['moved_at'] ?? now(),
            ]);
        }

        return $lot;
    }

    private function adjustLotQty(?int $lotId, int $stockItemId, float $delta, ?string $batchNo): void
    {
        $delta = round($delta, 2);
        if ($delta == 0.0) {
            return;
        }

        $item = StockItem::whereKey($stockItemId)->lockForUpdate()->first();
        if (! $item) {
            return;
        }

        $lot = $this->findLot($item, $lotId, $batchNo);

        if (! $lot) {
            if ($delta <= 0) {
                $item->quantity = round((float) $item->quantity + $delta, 2);
                $item->save();

                return;
            }

            $lot = $item->lots()->create([
                'batch_no' => StockItemLot::normalizeBatch($batchNo),
                'quantity' => 0,
                'received_at' => now(),
            ]);
        }

        $lot->quantity = round((float) $lot->quantity + $delta, 2);
        $lot->save();
    }

    private function findLot(StockItem $item, ?int $lotId, ?string $batchNo): ?StockItemLot
    {
        if ($lotId) {
            $lot = $item->lots()->whereKey($lotId)->lockForUpdate()->first();
            if ($lot) {
                return $lot;
            }
        }

        $batch = StockItemLot::normalizeBatch($batchNo);
        if ($batch !== '') {
            $lot = $item->lots()->where('batch_no', $batch)->lockForUpdate()->first();
            if ($lot) {
                return $lot;
            }
        }

        return $item->lots()->orderBy('received_at')->orderBy('id')->lockForUpdate()->first();
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
