<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'po_no',
        'party_type',
        'supplier_id',
        'vendor_id',
        'po_date',
        'total_amount',
        'notes',
    ];

    protected $casts = [
        'po_date' => 'date',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function isVendorPurchase(): bool
    {
        return $this->party_type === 'vendor';
    }

    public function partyName(): string
    {
        if ($this->isVendorPurchase()) {
            return $this->vendor?->name ?: '—';
        }

        return $this->supplier?->name ?: '—';
    }

    public function party(): Supplier|Vendor|null
    {
        return $this->isVendorPurchase() ? $this->vendor : $this->supplier;
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public static function nextNumber(): string
    {
        $max = 1000;

        foreach (static::query()->where('po_no', 'like', 'PO-%')->lockForUpdate()->pluck('po_no') as $poNo) {
            if (preg_match('/^PO-(\d+)$/', (string) $poNo, $match)) {
                $n = (int) $match[1];
                if ($n > $max) {
                    $max = $n;
                }
            }
        }

        return 'PO-'.($max + 1);
    }
}

