<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'address',
        'vat_number',
        'opening_balance',
        'current_balance',
        'is_active',
    ];
}
