<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashVoucher extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'voucher_no',
        'type',
        'cash_account_id',
        'payment_method',
        'bank_account_id',
        'account_type',
        'account_id',
        'other_name',
        'amount',
        'voucher_date',
        'reference',
        'notes',
    ];

    protected $casts = [
        'voucher_date' => 'date',
    ];

    public function cashAccount(): BelongsTo
    {
        return $this->belongsTo(CashAccount::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function accountDisplayName(): ?string
    {
        if ($this->account_type === 'other') {
            return $this->other_name;
        }

        if (empty($this->account_id)) {
            return null;
        }

        return match ($this->account_type) {
            'customer' => Customer::find($this->account_id)?->name,
            'supplier' => Supplier::find($this->account_id)?->name,
            'investor' => Investor::find($this->account_id)?->name,
            'expense' => ExpenseAccount::find($this->account_id)?->name,
            default => null,
        };
    }

    /**
     * @param  Collection<int, self>|iterable<int, self>  $vouchers
     */
    public static function loadAccountNames(iterable $vouchers): void
    {
        $collection = $vouchers instanceof Collection ? $vouchers : collect($vouchers);

        $models = [
            'customer' => Customer::class,
            'supplier' => Supplier::class,
            'investor' => Investor::class,
            'expense' => ExpenseAccount::class,
        ];

        $namesByType = [];

        foreach ($models as $type => $modelClass) {
            $ids = $collection
                ->where('account_type', $type)
                ->pluck('account_id')
                ->filter()
                ->unique()
                ->values();

            if ($ids->isNotEmpty()) {
                $namesByType[$type] = $modelClass::query()
                    ->whereIn('id', $ids)
                    ->pluck('name', 'id');
            }
        }

        foreach ($collection as $voucher) {
            if ($voucher->account_type === 'other') {
                $voucher->setAttribute('account_display_name', $voucher->other_name);

                continue;
            }

            $name = $namesByType[$voucher->account_type][$voucher->account_id] ?? null;
            $voucher->setAttribute('account_display_name', $name);
        }
    }
}
