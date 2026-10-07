<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

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

    public function lots(): HasMany
    {
        return $this->hasMany(StockItemLot::class);
    }

    public function currentLot(): ?StockItemLot
    {
        if ($this->relationLoaded('lots')) {
            return $this->lots->sortByDesc('id')->first();
        }

        return $this->lots()->orderByDesc('id')->first();
    }

    public function lotsSummary(): string
    {
        $batch = StockItemLot::normalizeBatch($this->batch_no);
        if ($batch !== '') {
            return $batch;
        }

        return $this->currentLot()?->batchLabel() ?: '—';
    }

    public function seedOpeningLot(): void
    {
        if ($this->lots()->exists()) {
            return;
        }

        $qty = round((float) $this->quantity, 2);
        $batch = StockItemLot::normalizeBatch($this->batch_no);

        $this->lots()->create([
            'batch_no' => $batch,
            'quantity' => $qty,
            'expiry_date' => $this->expiry_date,
            'received_at' => now(),
        ]);
    }

    public function collapseToSingleLot(?string $batchNo = null): ?StockItemLot
    {
        $lots = $this->lots()->orderByDesc('received_at')->orderByDesc('id')->lockForUpdate()->get();
        if ($lots->isEmpty()) {
            $this->seedOpeningLot();

            return $this->lots()->first();
        }

        $keep = $lots->first();
        $sum = round((float) $lots->sum('quantity'), 2);
        $batch = StockItemLot::normalizeBatch($batchNo);
        if ($batch === '') {
            foreach ($lots as $lot) {
                $candidate = StockItemLot::normalizeBatch($lot->batch_no);
                if ($candidate !== '') {
                    $batch = $candidate;
                    break;
                }
            }
        }

        $extraIds = $lots->where('id', '!=', $keep->id)->pluck('id')->all();
        if ($extraIds !== []) {
            foreach (['stock_movements', 'purchase_order_items', 'sales_invoice_lines', 'sales_order_lines', 'sales_return_items'] as $table) {
                DB::table($table)->whereIn('stock_item_lot_id', $extraIds)->update([
                    'stock_item_lot_id' => $keep->id,
                ]);
            }
            StockItemLot::query()->whereIn('id', $extraIds)->delete();
        }

        $keep->quantity = $sum;
        $keep->batch_no = $batch;
        if ($this->expiry_date) {
            $keep->expiry_date = $this->expiry_date;
        }
        $keep->save();

        $this->quantity = $sum;
        $this->batch_no = $batch !== '' ? $batch : null;
        $this->save();
        $this->unsetRelation('lots');

        return $keep;
    }

    public function syncQuantityFromLots(): void
    {
        $lot = $this->collapseToSingleLot() ?? $this->currentLot();
        $this->quantity = round((float) ($lot?->quantity ?? 0), 2);
        $batch = StockItemLot::normalizeBatch($lot?->batch_no);
        $this->batch_no = $batch !== '' ? $batch : null;
        $this->save();
    }

    public function syncSingleLotFromItem(): void
    {
        $qty = round((float) $this->quantity, 2);
        $batch = StockItemLot::normalizeBatch($this->batch_no);
        $this->collapseToSingleLot($batch);
        $lot = $this->currentLot();

        if (! $lot) {
            $this->quantity = $qty;
            $this->batch_no = $batch !== '' ? $batch : null;
            $this->seedOpeningLot();

            return;
        }

        $lot->batch_no = $batch;
        $lot->quantity = $qty;
        $lot->expiry_date = $this->expiry_date ?: $lot->expiry_date;
        $lot->save();

        $this->batch_no = $batch !== '' ? $batch : null;
        $this->quantity = $qty;
        $this->save();
        $this->unsetRelation('lots');
    }

    /**
     * @param  array<string, mixed>  $line
     * @return array<string, mixed>
     */
    public static function applyLotToLine(array $line): array
    {
        if (empty($line['stock_item_id'])) {
            $line['stock_item_lot_id'] = null;

            return $line;
        }

        $item = static::query()->find($line['stock_item_id']);
        if (! $item) {
            return $line;
        }

        $lot = $item->currentLot();
        if (! $lot) {
            $item->seedOpeningLot();
            $lot = $item->currentLot();
        }

        $line['stock_item_lot_id'] = $lot?->id;
        $batch = StockItemLot::normalizeBatch($item->batch_no ?: $lot?->batch_no);
        $line['batch_no'] = $batch !== '' ? $batch : null;

        return $line;
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
