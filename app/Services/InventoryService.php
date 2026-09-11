<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\StockItem;
use App\Models\StockMovement;
use InvalidArgumentException;

class InventoryService
{
    /**
     * @param  array{unit_cost?: mixed, moved_at?: mixed, reference?: ?string, notes?: ?string, source_type?: ?string, source_id?: ?int}  $meta
     */
    public function receive(int $stockItemId, float $quantity, array $meta = []): void
    {
        $this->adjust($stockItemId, $quantity, false);
        $this->move($stockItemId, 'in', $quantity, $meta);
    }

    /**
     * @param  array{unit_cost?: mixed, moved_at?: mixed, reference?: ?string, notes?: ?string, source_type?: ?string, source_id?: ?int}  $meta
     */
    public function issue(int $stockItemId, float $quantity, array $meta = []): void
    {
        $this->adjust($stockItemId, -$quantity, true);
        $this->move($stockItemId, 'out', $quantity, $meta);
    }

    public function revertPurchaseOrder(PurchaseOrder $purchaseOrder): void
    {
        $purchaseOrder->loadMissing('items');

        foreach ($purchaseOrder->items as $item) {
            if ($item->stock_item_id) {
                $this->adjust((int) $item->stock_item_id, -((float) $item->quantity), false);
            }
        }

        StockMovement::query()
            ->where('source_type', 'purchase_order')
            ->where('source_id', $purchaseOrder->id)
            ->delete();
    }

    private function adjust(int $stockItemId, float $delta, bool $requireStock): void
    {
        $item = StockItem::whereKey($stockItemId)->lockForUpdate()->firstOrFail();
        $next = round((float) $item->quantity + $delta, 2);

        if ($requireStock && $next < -0.0001) {
            throw new InvalidArgumentException(
                'Not enough stock for '.$item->name.'. Available: '.number_format((float) $item->quantity, 2)
            );
        }

        $item->quantity = $next;
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
