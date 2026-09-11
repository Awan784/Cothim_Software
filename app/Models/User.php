<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    public const TYPE_ADMIN = 1;

    public const TYPE_USER = 2;

    protected $fillable = [
        'organization_id',
        'name',
        'email',
        'password',
        'show_password',
        'user_type',
        'is_platform_admin',
        'permissions',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'permissions' => 'array',
            'is_active' => 'boolean',
            'is_platform_admin' => 'boolean',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function isPlatformAdmin(): bool
    {
        return (bool) $this->is_platform_admin;
    }

    public function isAdmin(): bool
    {
        return (int) $this->user_type === self::TYPE_ADMIN;
    }

    public function canModule(string $module, string $action): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $permissions = $this->permissions ?? [];

        return (bool) ($permissions[$module][$action] ?? false);
    }

    /**
     * @param  array<string, array<string, mixed>>|null  $input
     * @return array<string, array<string, bool>>
     */
    public static function normalizePermissions(?array $input): array
    {
        $modules = array_keys(config('permissions.modules', []));
        $actions = array_keys(config('permissions.actions', []));
        $normalized = [];

        foreach ($modules as $module) {
            foreach ($actions as $action) {
                $normalized[$module][$action] = ! empty($input[$module][$action]);
            }
        }

        return $normalized;
    }
}
