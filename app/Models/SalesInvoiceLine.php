<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesInvoiceLine extends Model
{
    protected $fillable = [
        'sales_invoice_id',
        'stock_item_id',
        'stock_item_lot_id',
        'batch_no',
        'description',
        'print_note',
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

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id');
    }

    public function stockItem(): BelongsTo
    {
        return $this->belongsTo(StockItem::class);
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(StockItemLot::class, 'stock_item_lot_id');
    }

    public function batchLabel(): string
    {
        if (trim((string) $this->batch_no) !== '') {
            return $this->batch_no;
        }

        return $this->lot?->batchLabel() ?: ($this->stockItem?->batch_no ?: '—');
    }

    public function printName(): string
    {
        return ams_print_item_name($this->description, $this->print_note);
    }
}
