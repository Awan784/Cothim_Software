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
        'affects_cash',
        'salesman_settlement_id',
        'sales_invoice_id',
    ];

    protected $casts = [
        'voucher_date' => 'date',
        'affects_cash' => 'boolean',
    ];

    public static function prefixFor(string $type): string
    {
        return $type === 'receive' ? 'CRV' : 'CPV';
    }

    public static function nextNumber(string $type): string
    {
        $prefix = self::prefixFor($type);
        $max = 100;

        foreach (static::query()->where('voucher_no', 'like', $prefix.'-%')->lockForUpdate()->pluck('voucher_no') as $voucherNo) {
            if (preg_match('/^'.preg_quote($prefix, '/').'-(\d+)$/', (string) $voucherNo, $match)) {
                $n = (int) $match[1];
                if ($n > $max) {
                    $max = $n;
                }
            }
        }

        return $prefix.'-'.($max + 1);
    }

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
            'salesman' => Salesman::find($this->account_id)?->name,
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
            'salesman' => Salesman::class,
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

    public function salesmanSettlement(): BelongsTo
    {
        return $this->belongsTo(SalesmanSettlement::class, 'salesman_settlement_id');
    }

    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id');
    }

    public function isSettlementLinked(): bool
    {
        return $this->salesman_settlement_id !== null;
    }

    public function affectsCashBalance(): bool
    {
        return (bool) ($this->affects_cash ?? true);
    }

    public function isInvoiceAllocation(): bool
    {
        return $this->isSettlementLinked() && ! $this->affectsCashBalance();
    }

    public function isSettlementCash(): bool
    {
        return $this->isSettlementLinked() && $this->affectsCashBalance();
    }

    public function cashBookNotes(): string
    {
        $settlement = $this->salesmanSettlement;
        if ($this->isSettlementCash() && $settlement) {
            return 'Allocated '.number_format((float) $settlement->allocated_amount, 2)
                .' · Advance '.number_format((float) $settlement->closing_advance, 2);
        }

        return (string) ($this->notes ?? '');
    }

    public function scopeAffectingCash($query)
    {
        return $query->where('affects_cash', true);
    }
}
