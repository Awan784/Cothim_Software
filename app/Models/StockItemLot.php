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
        'manufactured_at',
        'expiry_date',
        'quantity',
        'received_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'manufactured_at' => 'date',
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

    public function qtyLabel(): string
    {
        return (string) (int) round((float) $this->quantity);
    }

    public function dropdownLabel(bool $hideQty = false): string
    {
        if ($hideQty) {
            return $this->batchLabel();
        }

        return $this->batchLabel().' — Qty '.$this->qtyLabel();
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
