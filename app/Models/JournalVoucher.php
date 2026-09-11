<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JournalVoucher extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'voucher_no',
        'voucher_date',
        'notes',
        'total_debit',
        'total_credit',
    ];

    protected $casts = [
        'voucher_date' => 'date',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(JournalVoucherLine::class);
    }
}
