<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseReturnItem extends Model
{
    protected $fillable = [
        'purchase_return_id',
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

    public function purchaseReturn(): BelongsTo
    {
        return $this->belongsTo(PurchaseReturn::class);
    }

    public function stockItem(): BelongsTo
    {
        return $this->belongsTo(StockItem::class);
    }
}
