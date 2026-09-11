<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'stock_item_id',
        'type',
        'quantity',
        'unit_cost',
        'moved_at',
        'reference',
        'notes',
        'source_type',
        'source_id',
    ];

    protected $casts = [
        'moved_at' => 'datetime',
    ];

    public function stockItem(): BelongsTo
    {
        return $this->belongsTo(StockItem::class);
    }
}
