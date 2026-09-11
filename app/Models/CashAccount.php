<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashAccount extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'name',
        'opening_balance',
        'current_balance',
        'is_active',
    ];

    public function vouchers(): HasMany
    {
        return $this->hasMany(CashVoucher::class);
    }
}
