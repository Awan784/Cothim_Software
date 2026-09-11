<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpenseAccount extends Model
{
    use BelongsToOrganization;

    protected $table = 'expense_categories';

    protected $fillable = [
        'name',
        'description',
        'nominal_account_id',
        'is_active',
        'total_spent',
    ];

    public function cashVouchers(): HasMany
    {
        return $this->hasMany(CashVoucher::class, 'account_id')
            ->where('account_type', 'expense');
    }

    /**
     * Total spent for one expense account from cash vouchers (payments minus refunds).
     */
    public static function spentForAccount(int $accountId): float
    {
        return max(0, round(self::sumVoucherNetForAccount($accountId), 2));
    }

    /**
     * Grand total of all expense cash voucher payments.
     */
    public static function totalExpenses(): float
    {
        return max(0, round((float) CashVoucher::query()
            ->where('account_type', 'expense')
            ->whereNotNull('account_id')
            ->selectRaw(self::voucherNetSumSql().' as total')
            ->value('total'), 2));
    }

    private static function voucherNetSumSql(): string
    {
        return "COALESCE(SUM(CASE WHEN type = 'payment' THEN amount WHEN type = 'receive' THEN -amount ELSE 0 END), 0)";
    }

    private static function sumVoucherNetForAccount(int $accountId): float
    {
        return (float) CashVoucher::query()
            ->where('account_type', 'expense')
            ->where('account_id', $accountId)
            ->selectRaw(self::voucherNetSumSql().' as total')
            ->value('total');
    }

    public static function syncTotalSpentFor(int $accountId): void
    {
        self::whereKey($accountId)->update([
            'total_spent' => self::spentForAccount($accountId),
        ]);
    }

    public static function syncAllTotalSpent(): void
    {
        foreach (self::query()->pluck('id') as $id) {
            self::syncTotalSpentFor((int) $id);
        }
    }
}
