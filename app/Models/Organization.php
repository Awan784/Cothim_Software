<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Organization extends Model
{
    public const PLAN_TRIAL = 'trial';

    public const PLAN_PLUS = 'plus';

    public const PLAN_PRO = 'pro';

    protected $fillable = [
        'name',
        'slug',
        'plan',
        'trial_ends_at',
        'vat_number',
        'cr_number',
        'address',
        'default_vat_rate',
        'zatca_environment',
        'status',
        'whatsapp',
        'admin_notes',
    ];

    protected $casts = [
        'trial_ends_at' => 'date',
        'default_vat_rate' => 'float',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function isOnTrial(): bool
    {
        return $this->plan === self::PLAN_TRIAL;
    }

    public function trialDaysLeft(): ?int
    {
        if (! $this->trial_ends_at) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($this->trial_ends_at->copy()->endOfDay(), false);
    }

    public static function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'company';
        $slug = $base;
        $i = 1;
        while (static::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }
}
