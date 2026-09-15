<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\PurchaseReturn;
use App\Models\SalesReturn;
use App\Models\StockItem;
use App\Models\StockMovement;

class InventoryService
{
    /**
     * @param  array{unit_cost?: mixed, moved_at?: mixed, reference?: ?string, notes?: ?string, source_type?: ?string, source_id?: ?int}  $meta
     */
    public function receive(int $stockItemId, float $quantity, array $meta = []): void
    {
        $this->adjust($stockItemId, $quantity);
        $this->move($stockItemId, 'in', $quantity, $meta);
    }

    /**
     * @param  array{unit_cost?: mixed, moved_at?: mixed, reference?: ?string, notes?: ?string, source_type?: ?string, source_id?: ?int}  $meta
     */
    public function issue(int $stockItemId, float $quantity, array $meta = []): void
    {
        $this->adjust($stockItemId, -$quantity);
        $this->move($stockItemId, 'out', $quantity, $meta);
    }

    public function revertPurchaseOrder(PurchaseOrder $purchaseOrder): void
    {
        $purchaseOrder->loadMissing('items');

        foreach ($purchaseOrder->items as $item) {
            if ($item->stock_item_id) {
                $this->adjust((int) $item->stock_item_id, -((float) $item->quantity));
            }
        }

        StockMovement::query()
            ->where('source_type', 'purchase_order')
            ->where('source_id', $purchaseOrder->id)
            ->delete();
    }

    public function revertPurchaseReturn(PurchaseReturn $purchaseReturn): void
    {
        $purchaseReturn->loadMissing('items');

        foreach ($purchaseReturn->items as $item) {
            if ($item->stock_item_id) {
                $this->adjust((int) $item->stock_item_id, (float) $item->quantity);
            }
        }

        StockMovement::query()
            ->where('source_type', 'purchase_return')
            ->where('source_id', $purchaseReturn->id)
            ->delete();
    }

    public function revertSalesReturn(SalesReturn $salesReturn): void
    {
        $salesReturn->loadMissing('items');

        foreach ($salesReturn->items as $item) {
            if ($item->stock_item_id) {
                $this->adjust((int) $item->stock_item_id, -((float) $item->quantity));
            }
        }

        StockMovement::query()
            ->where('source_type', 'sales_return')
            ->where('source_id', $salesReturn->id)
            ->delete();
    }

    private function adjust(int $stockItemId, float $delta): void
    {
        $item = StockItem::whereKey($stockItemId)->lockForUpdate()->firstOrFail();
        $item->quantity = round((float) $item->quantity + $delta, 2);
        $item->save();
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function move(int $stockItemId, string $type, float $quantity, array $meta): void
    {
        StockMovement::create([
            'stock_item_id' => $stockItemId,
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
