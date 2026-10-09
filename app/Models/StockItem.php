<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Support\AmsDate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

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

    /**
     * @return Collection<int, StockItemLot>
     */
    public function lotsCollection(): Collection
    {
        if ($this->relationLoaded('lots')) {
            return $this->lots->sortBy([
                ['received_at', 'asc'],
                ['id', 'asc'],
            ])->values();
        }

        return $this->lots()->orderBy('received_at')->orderBy('id')->get();
    }

    public function currentLot(): ?StockItemLot
    {
        $lots = $this->lotsCollection();

        return $lots->first(fn (StockItemLot $lot) => (float) $lot->quantity > 0)
            ?? $lots->first();
    }

    public function lotsSummary(): string
    {
        $lots = $this->lotsCollection();
        if ($lots->isEmpty()) {
            $batch = StockItemLot::normalizeBatch($this->batch_no);

            return $batch !== '' ? $batch : '—';
        }

        return $lots->map(fn (StockItemLot $lot) => $lot->batchLabel().' Qty '.$lot->qtyLabel())->implode(' / ');
    }

    public function lotsHtml(): HtmlString
    {
        $lots = $this->lotsCollection();
        if ($lots->isEmpty()) {
            $batch = StockItemLot::normalizeBatch($this->batch_no);
            $text = $batch !== '' ? $batch : '—';

            return new HtmlString('<span class="stock-batch-no">'.e($text).'</span>');
        }

        $html = $lots->map(function (StockItemLot $lot) {
            $extra = [];
            if ($lot->manufactured_at) {
                $extra[] = 'Mfg '.AmsDate::format($lot->manufactured_at);
            }
            if ($lot->expiry_date) {
                $extra[] = 'Exp '.AmsDate::format($lot->expiry_date);
            }
            $meta = $extra !== []
                ? '<span class="stock-batch-meta">'.e(implode(' · ', $extra)).'</span>'
                : '';

            return '<div class="stock-batch-line"><span class="stock-batch-no">'.e($lot->batchLabel()).'</span><span class="stock-batch-qty">Qty '.e($lot->qtyLabel()).'</span>'.$meta.'</div>';
        })->implode('');

        return new HtmlString($html);
    }

    /**
     * @return list<array{id: int, batch: string, qty: int}>
     */
    public function lotsPickerPayload(): array
    {
        return $this->lotsCollection()->map(fn (StockItemLot $lot) => [
            'id' => (int) $lot->id,
            'batch' => $lot->batchLabel(),
            'qty' => (int) round((float) $lot->quantity),
        ])->values()->all();
    }

    public function seedOpeningLot(?string $manufacturedAt = null): void
    {
        if ($this->lots()->exists()) {
            return;
        }

        $qty = round((float) $this->quantity, 2);
        $batch = StockItemLot::normalizeBatch($this->batch_no);

        $this->lots()->create([
            'batch_no' => $batch,
            'quantity' => $qty,
            'manufactured_at' => $manufacturedAt,
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
        $lots = $this->lots()->orderByDesc('received_at')->orderByDesc('id')->get();
        $this->quantity = round((float) $lots->sum('quantity'), 2);
        $primary = $lots->first(fn (StockItemLot $lot) => (float) $lot->quantity != 0.0) ?? $lots->first();
        $batch = StockItemLot::normalizeBatch($primary?->batch_no);
        $this->batch_no = $batch !== '' ? $batch : null;
        $this->save();
        $this->unsetRelation('lots');
    }

    public function syncSingleLotFromItem(?string $manufacturedAt = null): void
    {
        $count = $this->lots()->count();
        if ($count > 1) {
            $this->syncQuantityFromLots();

            return;
        }

        $qty = round((float) $this->quantity, 2);
        $batch = StockItemLot::normalizeBatch($this->batch_no);
        $lot = $this->lots()->lockForUpdate()->first();

        if (! $lot) {
            $this->quantity = $qty;
            $this->batch_no = $batch !== '' ? $batch : null;
            $this->save();
            $this->seedOpeningLot($manufacturedAt);

            return;
        }

        $lot->batch_no = $batch;
        $lot->quantity = $qty;
        $lot->expiry_date = $this->expiry_date ?: $lot->expiry_date;
        if ($manufacturedAt) {
            $lot->manufactured_at = $manufacturedAt;
        }
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

        $lot = null;
        $lotId = (int) ($line['stock_item_lot_id'] ?? 0);
        if ($lotId > 0) {
            $lot = $item->lots()->whereKey($lotId)->first();
        }

        if (! $lot) {
            $batch = StockItemLot::normalizeBatch($line['batch_no'] ?? null);
            if ($batch !== '') {
                $lot = $item->lots()->where('batch_no', $batch)->first();
            }
        }

        if (! $lot) {
            $lot = $item->currentLot();
        }

        if (! $lot) {
            $item->seedOpeningLot();
            $lot = $item->currentLot();
        }

        $line['stock_item_lot_id'] = $lot?->id;
        $batch = StockItemLot::normalizeBatch($lot?->batch_no);
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
