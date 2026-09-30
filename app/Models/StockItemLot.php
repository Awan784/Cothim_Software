<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockItemLot extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'stock_item_id',
        'batch_no',
        'expiry_date',
        'quantity',
        'received_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'expiry_date' => 'date',
            'received_at' => 'datetime',
        ];
    }

    public static function normalizeBatch(?string $batch): string
    {
        return trim((string) $batch);
    }

    public function batchLabel(): string
    {
        $batch = self::normalizeBatch($this->batch_no);

        return $batch !== '' ? $batch : '—';
    }

    public function stockItem(): BelongsTo
    {
        return $this->belongsTo(StockItem::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
