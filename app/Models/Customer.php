<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'name',
        'company_name',
        'proprietor_name',
        'phone',
        'mobile',
        'email',
        'ntn',
        'strn',
        'vat_number',
        'license_no',
        'address',
        'city',
        'area',
        'opening_balance',
        'current_balance',
        'is_active',
    ];

    public function displayName(): string
    {
        return $this->company_name ?: $this->name;
    }

    public function searchText(): string
    {
        $values = [];
        foreach ([
            $this->company_name,
            $this->name,
            $this->proprietor_name,
            $this->phone,
            $this->mobile,
            $this->city,
            $this->area,
            $this->address,
            $this->license_no,
        ] as $part) {
            $part = trim((string) $part);
            if ($part !== '' && ! in_array($part, $values, true)) {
                $values[] = $part;
            }
        }

        return implode(' ', $values);
    }
}
