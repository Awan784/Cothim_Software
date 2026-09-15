<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesOrder extends Model
{
    use BelongsToOrganization;

    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'order_no',
        'salesman_id',
        'customer_id',
        'order_date',
        'status',
        'subtotal',
        'discount_amount',
        'vat_amount',
        'total',
        'company_retain_percent',
        'salesman_commission_percent',
        'company_retain_amount',
        'salesman_commission_amount',
        'notes',
        'sales_invoice_id',
        'confirmed_by',
        'confirmed_at',
        'rejected_at',
        'reject_reason',
    ];

    protected $casts = [
        'order_date' => 'date',
        'subtotal' => 'float',
        'discount_amount' => 'float',
        'vat_amount' => 'float',
        'total' => 'float',
        'company_retain_percent' => 'float',
        'salesman_commission_percent' => 'float',
        'company_retain_amount' => 'float',
        'salesman_commission_amount' => 'float',
        'confirmed_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function salesman(): BelongsTo
    {
        return $this->belongsTo(Salesman::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id');
    }

    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SalesOrderLine::class)->orderBy('sort_order');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_CONFIRMED => 'Confirmed',
            self::STATUS_REJECTED => 'Rejected',
            default => 'Pending',
        };
    }

    public static function nextNumber(): string
    {
        $max = 1000;

        foreach (static::query()->where('order_no', 'like', 'SO-%')->lockForUpdate()->pluck('order_no') as $orderNo) {
            if (preg_match('/^SO-(\d+)$/', (string) $orderNo, $match)) {
                $n = (int) $match[1];
                if ($n > $max) {
                    $max = $n;
                }
            }
        }

        return 'SO-'.($max + 1);
    }
}
