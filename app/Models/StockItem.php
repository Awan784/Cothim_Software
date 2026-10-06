<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

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
     * @return list<array{id: int, batch: string, qty: float}>
     */
    public function lotsPayload(): array
    {
        $lots = $this->relationLoaded('lots')
            ? $this->lots
            : $this->lots()->get();

        return $lots->map(fn (StockItemLot $lot) => [
            'id' => $lot->id,
            'batch' => StockItemLot::normalizeBatch($lot->batch_no),
            'qty' => (float) $lot->quantity,
        ])->values()->all();
    }

    public function lotsSummary(): string
    {
        $lots = $this->relationLoaded('lots')
            ? $this->lots
            : $this->lots()->get();

        $parts = [];
        foreach ($lots as $lot) {
            if ((float) $lot->quantity <= 0) {
                continue;
            }
            $parts[] = $lot->dropdownLabel();
        }

        if ($parts === []) {
            return $this->batch_no ?: '—';
        }

        return implode(', ', $parts);
    }

    public function oldestSellableLot(): ?StockItemLot
    {
        if ($this->relationLoaded('lots')) {
            return $this->lots
                ->filter(fn (StockItemLot $lot) => (float) $lot->quantity > 0)
                ->sortBy([
                    fn (StockItemLot $a, StockItemLot $b) => ($a->received_at?->getTimestamp() ?? 0) <=> ($b->received_at?->getTimestamp() ?? 0),
                    fn (StockItemLot $a, StockItemLot $b) => $a->id <=> $b->id,
                ])
                ->first();
        }

        return $this->lots()
            ->where('quantity', '>', 0)
            ->orderBy('received_at')
            ->orderBy('id')
            ->first();
    }

    public function seedOpeningLot(): void
    {
        if ($this->lots()->exists()) {
            return;
        }

        $qty = round((float) $this->quantity, 2);
        $batch = StockItemLot::normalizeBatch($this->batch_no);
        if ($qty <= 0 && $batch === '') {
            return;
        }

        $this->lots()->create([
            'batch_no' => $batch,
            'quantity' => $qty,
            'expiry_date' => $this->expiry_date,
            'received_at' => now(),
        ]);
    }

    public function syncQuantityFromLots(): void
    {
        $sum = round((float) $this->lots()->sum('quantity'), 2);
        $latest = $this->lots()
            ->orderByDesc('received_at')
            ->orderByDesc('id')
            ->first();

        $this->quantity = $sum;
        $this->batch_no = $latest && StockItemLot::normalizeBatch($latest->batch_no) !== ''
            ? $latest->batch_no
            : null;
        $this->save();
    }

    public function syncSingleLotFromItem(): void
    {
        if ($this->lots()->count() > 1) {
            $this->syncQuantityFromLots();

            return;
        }

        $qty = round((float) $this->quantity, 2);
        $batch = StockItemLot::normalizeBatch($this->batch_no);
        $lot = $this->lots()->first();

        if (! $lot) {
            $this->seedOpeningLot();

            return;
        }

        $lot->batch_no = $batch;
        $lot->quantity = $qty;
        $lot->expiry_date = $this->expiry_date ?: $lot->expiry_date;
        $lot->save();
        $this->syncQuantityFromLots();
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

        $item = static::query()->with('lots')->find($line['stock_item_id']);
        if (! $item) {
            return $line;
        }

        if ($item->lots->isEmpty() && (float) $item->quantity > 0) {
            $item->seedOpeningLot();
            $item->load('lots');
        }

        $qty = (float) ($line['quantity'] ?? 0);
        $lotId = filled($line['stock_item_lot_id'] ?? null) ? (int) $line['stock_item_lot_id'] : null;
        $lot = $lotId
            ? $item->lots->firstWhere('id', $lotId)
            : $item->oldestSellableLot();

        if (! $lot) {
            throw ValidationException::withMessages([
                'lines' => 'Select a batch for '.$item->name.'.',
            ]);
        }

        if ((int) $lot->stock_item_id !== (int) $item->id) {
            throw ValidationException::withMessages([
                'lines' => 'Batch does not belong to '.$item->name.'.',
            ]);
        }

        if ($qty > (float) $lot->quantity + 0.009) {
            throw ValidationException::withMessages([
                'lines' => $item->name.' batch '.$lot->batchLabel().' has only '.number_format((float) $lot->quantity, 2).' remaining. Add another line for the other batch.',
            ]);
        }

        $line['stock_item_lot_id'] = $lot->id;
        $line['batch_no'] = StockItemLot::normalizeBatch($lot->batch_no) !== '' ? $lot->batch_no : null;

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
