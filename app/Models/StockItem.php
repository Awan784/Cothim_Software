<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockItem extends Model
{
    use BelongsToOrganization;

    public const UNITS = [
        'pcs' => 'Pcs',
        'carton' => 'Carton',
        'box' => 'Box',
    ];

    public const POTENCIES = ['6X', '12X', '30X', '200X', '6C', '30C', '200C', '1M', '10M', 'CM', 'Q'];

    protected $fillable = [
        'stock_category_id',
        'sku',
        'batch_no',
        'name',
        'unit',
        'quantity',
        'cost_price',
        'sale_price',
        'has_variants',
        'description',
        'potency',
        'pack_size',
        'manufacturer',
        'expiry_date',
        'composition',
        'barcode',
        'storage_note',
        'hs_code',
        'reorder_level',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'quantity' => 'decimal:2',
            'has_variants' => 'boolean',
            'is_active' => 'boolean',
            'expiry_date' => 'date',
        ];
    }

    public function stockCategory(): BelongsTo
    {
        return $this->belongsTo(StockCategory::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(StockItemVariant::class)->orderBy('sort_order')->orderBy('id');
    }

    public function isOutOfStock(): bool
    {
        return (float) $this->quantity <= 0;
    }

    public function isLowStock(): bool
    {
        if ($this->isOutOfStock()) {
            return true;
        }

        $reorder = (int) ($this->reorder_level ?? 0);

        return $reorder > 0 && (float) $this->quantity <= $reorder;
    }

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->where(function (Builder $inner) {
            $inner->where('quantity', '<=', 0)
                ->orWhere(function (Builder $reorder) {
                    $reorder->where('reorder_level', '>', 0)
                        ->whereColumn('quantity', '<=', 'reorder_level');
                });
        });
    }

    public function scopeOutOfStock(Builder $query): Builder
    {
        return $query->where('quantity', '<=', 0);
    }

    public function purchaseLabel(): string
    {
        $parts = [$this->name];
        $potency = trim((string) $this->potency);
        if ($potency !== '' && $potency !== '0') {
            $parts[] = $potency;
        }
        $pack = trim((string) $this->pack_size);
        if ($pack !== '') {
            $parts[] = $pack;
        }

        return implode(' · ', $parts);
    }
}
