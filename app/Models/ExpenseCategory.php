<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpenseCategory extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'name',
        'nominal_account_id',
        'is_active',
    ];

    public function nominalAccount(): BelongsTo
    {
        return $this->belongsTo(NominalAccount::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }
}
