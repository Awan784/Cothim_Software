<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    protected $fillable = [
        'purchase_order_id',
        'stock_item_id',
        'item_name',
        'unit',
        'unit_price',
        'quantity',
        'line_total',
        'note',
    ];

    public function displayName(): string
    {
        if ($this->item_name) {
            return $this->item_name;
        }

        return $this->stockItem?->name ?? '—';
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function stockItem(): BelongsTo
    {
        return $this->belongsTo(StockItem::class);
    }
}

