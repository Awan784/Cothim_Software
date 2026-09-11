<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Salesman extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'name',
        'username',
        'password',
        'show_password',
        'phone',
        'mobile',
        'email',
        'city',
        'monthly_target',
        'is_active',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'monthly_target' => 'decimal:2',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function customersInCity(): Builder
    {
        if (! $this->city) {
            return Customer::query()->whereRaw('0 = 1');
        }

        return Customer::query()->where('city', $this->city)->orderByRaw('COALESCE(NULLIF(company_name, ""), name)');
    }
}
