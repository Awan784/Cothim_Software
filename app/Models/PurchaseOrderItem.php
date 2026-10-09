<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    protected $fillable = [
        'purchase_order_id',
        'stock_item_id',
        'stock_item_lot_id',
        'batch_no',
        'manufactured_at',
        'expiry_date',
        'item_name',
        'unit',
        'unit_price',
        'quantity',
        'line_total',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'manufactured_at' => 'date',
            'expiry_date' => 'date',
        ];
    }

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

