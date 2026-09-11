<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;

use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'name',
        'bank_name',
        'account_number',
        'opening_balance',
        'current_balance',
        'is_active',
        'is_cash',
    ];

    protected $casts = [
        'is_cash' => 'boolean',
        'is_active' => 'boolean',
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
    ];

    public static function ensureShopCash(): self
    {
        $existing = static::query()
            ->where('is_cash', true)
            ->orderBy('id')
            ->first();

        if ($existing) {
            return $existing;
        }

        $named = static::query()
            ->whereIn('name', ['Shop Cash', 'Cash', 'Main Cash'])
            ->orderBy('id')
            ->first();

        if ($named) {
            $named->update([
                'is_cash' => true,
                'is_active' => true,
            ]);

            return $named->fresh();
        }

        return static::create([
            'name' => 'Shop Cash',
            'bank_name' => 'Cash in Hand',
            'account_number' => null,
            'opening_balance' => 0,
            'current_balance' => 0,
            'is_active' => true,
            'is_cash' => true,
        ]);
    }
}
