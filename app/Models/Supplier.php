<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'address',
        'city',
        'vat_number',
        'opening_balance',
        'current_balance',
        'is_active',
    ];
}
