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
    ];
}
