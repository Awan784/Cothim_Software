<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesInvoice extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'invoice_no',
        'uuid',
        'customer_id',
        'invoice_date',
        'due_date',
        'type',
        'status',
        'subtotal',
        'vat_amount',
        'total',
        'amount_paid',
        'notes',
        'zatca_qr_payload',
        'zatca_status',
        'zatca_uuid',
        'issued_at',
        'created_by',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'issued_at' => 'datetime',
        'subtotal' => 'float',
        'vat_amount' => 'float',
        'total' => 'float',
        'amount_paid' => 'float',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SalesInvoiceLine::class)->orderBy('sort_order');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function balanceDue(): float
    {
        return round((float) $this->total - (float) $this->amount_paid, 2);
    }
}
