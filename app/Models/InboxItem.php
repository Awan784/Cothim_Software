<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class InboxItem extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'type',
        'status',
        'title',
        'original_name',
        'path',
        'mime',
        'size',
        'extracted_amount',
        'extracted_date',
        'extracted_party',
        'notes',
        'posted_type',
        'posted_id',
        'user_id',
    ];

    protected $casts = [
        'extracted_amount' => 'float',
        'extracted_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isNew(): bool
    {
        return $this->status === 'new';
    }

    public function fileUrl(): ?string
    {
        if (! $this->path) {
            return null;
        }

        return Storage::disk('public')->url($this->path);
    }
}
