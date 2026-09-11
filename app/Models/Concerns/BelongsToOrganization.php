<?php

namespace App\Models\Concerns;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToOrganization
{
    public static function bootBelongsToOrganization(): void
    {
        static::addGlobalScope('organization', function (Builder $query) {
            $orgId = organization_id();
            if ($orgId) {
                $query->where($query->getModel()->qualifyColumn('organization_id'), $orgId);
            }
        });

        static::creating(function ($model) {
            if (empty($model->organization_id) && $orgId = organization_id()) {
                $model->organization_id = $orgId;
            }
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
