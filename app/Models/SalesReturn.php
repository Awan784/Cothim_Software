<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesReturn extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'return_no',
        'customer_id',
        'return_date',
        'total_amount',
        'notes',
    ];

    protected $casts = [
        'return_date' => 'date',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesReturnItem::class);
    }

    public static function nextNumber(): string
    {
        $max = 1000;

        foreach (static::query()->where('return_no', 'like', 'SR-%')->lockForUpdate()->pluck('return_no') as $returnNo) {
            if (preg_match('/^SR-(\d+)$/', (string) $returnNo, $match)) {
                $n = (int) $match[1];
                if ($n > $max) {
                    $max = $n;
                }
            }
        }

        return 'SR-'.($max + 1);
    }
}
