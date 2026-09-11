<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockItemVariant extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'stock_item_id',
        'name',
        'size',
        'price',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    public function stockItem(): BelongsTo
    {
        return $this->belongsTo(StockItem::class);
    }

    public function label(): string
    {
        return $this->size ? $this->name.' ('.$this->size.')' : $this->name;
    }
}
