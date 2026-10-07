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
        'cities',
        'monthly_target',
        'commission_percent',
        'advance_balance',
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
            'advance_balance' => 'float',
            'is_active' => 'boolean',
            'password' => 'hashed',
            'cities' => 'array',
        ];
    }

    /**
     * @return list<string>
     */
    public function cityList(): array
    {
        $cities = $this->cities;
        if (! is_array($cities) || $cities === []) {
            return filled($this->city) ? [trim((string) $this->city)] : [];
        }

        $list = [];
        foreach ($cities as $city) {
            $city = trim((string) $city);
            if ($city !== '' && ! in_array($city, $list, true)) {
                $list[] = $city;
            }
        }

        return $list;
    }

    public function citiesLabel(): string
    {
        return implode(', ', $this->cityList());
    }

    public function scopeAssignedCity(Builder $query, string $city): Builder
    {
        return $query->where(function (Builder $inner) use ($city) {
            $inner->where('city', $city)->orWhereJsonContains('cities', $city);
        });
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

    public function settlements(): HasMany
    {
        return $this->hasMany(SalesmanSettlement::class);
    }

    public function customersInCity(): Builder
    {
        $cities = $this->cityList();
        if ($cities === []) {
            return Customer::query()->whereRaw('0 = 1');
        }

        return Customer::query()->whereIn('city', $cities)->orderByRaw('COALESCE(NULLIF(company_name, ""), name)');
    }

    public function assignableCustomers(): Builder
    {
        $query = Customer::query()->orderByRaw('COALESCE(NULLIF(company_name, ""), name)');
        $cities = $this->cityList();

        if ($cities !== []) {
            $query->whereIn('city', $cities);
        }

        return $query;
    }

    public function canSellTo(Customer $customer): bool
    {
        $cities = $this->cityList();
        if ($cities === []) {
            return true;
        }

        return in_array((string) $customer->city, $cities, true);
    }
}
