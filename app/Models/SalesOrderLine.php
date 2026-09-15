<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesOrderLine extends Model
{
    protected $fillable = [
        'sales_order_id',
        'stock_item_id',
        'description',
        'quantity',
        'unit_price',
        'discount_rate',
        'discount_amount',
        'vat_rate',
        'line_net',
        'vat_amount',
        'line_total',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'float',
        'unit_price' => 'float',
        'discount_rate' => 'float',
        'discount_amount' => 'float',
        'vat_rate' => 'float',
        'line_net' => 'float',
        'vat_amount' => 'float',
        'line_total' => 'float',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }

    public function stockItem(): BelongsTo
    {
        return $this->belongsTo(StockItem::class);
    }
}
