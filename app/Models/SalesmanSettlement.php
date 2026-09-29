<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesmanSettlement extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'salesman_id',
        'settlement_no',
        'settlement_date',
        'cash_received',
        'allocated_amount',
        'opening_advance',
        'closing_advance',
        'payment_method',
        'bank_account_id',
        'cash_voucher_id',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'settlement_date' => 'date',
        'cash_received' => 'float',
        'allocated_amount' => 'float',
        'opening_advance' => 'float',
        'closing_advance' => 'float',
    ];

    public static function nextNumber(): string
    {
        $prefix = 'SST';
        $max = 0;

        foreach (static::query()->where('settlement_no', 'like', $prefix.'-%')->lockForUpdate()->pluck('settlement_no') as $settlementNo) {
            if (preg_match('/^'.preg_quote($prefix, '/').'-(\d+)$/', (string) $settlementNo, $match)) {
                $max = max($max, (int) $match[1]);
            }
        }

        return $prefix.'-'.str_pad((string) ($max + 1), 4, '0', STR_PAD_LEFT);
    }

    public function salesman(): BelongsTo
    {
        return $this->belongsTo(Salesman::class);
    }

    public function cashVoucher(): BelongsTo
    {
        return $this->belongsTo(CashVoucher::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(SalesmanSettlementAllocation::class);
    }
}
