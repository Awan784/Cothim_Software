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
        'discount_amount',
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
        'discount_amount' => 'float',
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

    public function isPaid(): bool
    {
        return $this->status === 'paid' || $this->balanceDue() <= 0.009;
    }

    public function isOverdue(): bool
    {
        return ! $this->isPaid()
            && ! $this->isDraft()
            && $this->due_date
            && $this->due_date->copy()->endOfDay()->isPast();
    }

    public function listStatus(): string
    {
        if ($this->isPaid()) {
            return 'paid';
        }
        if ($this->isDraft()) {
            return 'draft';
        }
        if ($this->isOverdue()) {
            return 'overdue';
        }

        return 'issued';
    }

    public function listStatusLabel(): string
    {
        return match ($this->listStatus()) {
            'paid' => 'Paid',
            'draft' => 'Draft',
            'overdue' => 'Overdue',
            default => 'Issued',
        };
    }

    public function balanceDue(): float
    {
        return round((float) $this->total - (float) $this->amount_paid, 2);
    }
}
