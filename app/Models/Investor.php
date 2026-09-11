<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;

use Illuminate\Database\Eloquent\Model;

class Investor extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'address',
        'opening_investment',
        'current_balance',
        'is_active',
    ];
}
