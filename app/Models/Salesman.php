<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Salesman extends Authenticatable
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
        'commission_percent',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'monthly_target' => 'decimal:2',
            'commission_percent' => 'float',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function isPlatformAdmin(): bool
    {
        return false;
    }

    public function isAdmin(): bool
    {
        return false;
    }

    public function orders(): HasMany
    {
        return $this->hasMany(SalesOrder::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(SalesInvoice::class);
    }

    public function customersInCity(): Builder
    {
        if (! $this->city) {
            return Customer::query()->whereRaw('0 = 1');
        }

        return Customer::query()->where('city', $this->city)->orderByRaw('COALESCE(NULLIF(company_name, ""), name)');
    }

    public function assignableCustomers(): Builder
    {
        $query = Customer::query()->orderByRaw('COALESCE(NULLIF(company_name, ""), name)');

        if (filled($this->city)) {
            $query->where('city', $this->city);
        }

        return $query;
    }

    public function canSellTo(Customer $customer): bool
    {
        if (! filled($this->city)) {
            return true;
        }

        return $customer->city === $this->city;
    }
}
