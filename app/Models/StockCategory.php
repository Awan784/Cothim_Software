<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockCategory extends Model
{
    use BelongsToOrganization;

    public const KINDS = [
        'dilution' => 'Dilution',
        'mother_tincture' => 'Mother Tincture',
        'biochemic' => 'Biochemic',
        'tablet' => 'Tablet',
        'syrup' => 'Syrup',
        'drops' => 'Drops',
        'ointment' => 'Ointment',
        'cream' => 'Cream',
        'trituration' => 'Trituration',
        'other' => 'Other',
    ];

    protected $fillable = [
        'name',
        'kind',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function stockItems(): HasMany
    {
        return $this->hasMany(StockItem::class);
    }

    public function kindLabel(): string
    {
        return self::KINDS[$this->kind] ?? ($this->kind ?: '—');
    }
}
