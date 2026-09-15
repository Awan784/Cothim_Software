<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseReturn extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'return_no',
        'supplier_id',
        'return_date',
        'total_amount',
        'notes',
    ];

    protected $casts = [
        'return_date' => 'date',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }

    public static function nextNumber(): string
    {
        $max = 1000;

        foreach (static::query()->where('return_no', 'like', 'PR-%')->lockForUpdate()->pluck('return_no') as $returnNo) {
            if (preg_match('/^PR-(\d+)$/', (string) $returnNo, $match)) {
                $n = (int) $match[1];
                if ($n > $max) {
                    $max = $n;
                }
            }
        }

        return 'PR-'.($max + 1);
    }
}
