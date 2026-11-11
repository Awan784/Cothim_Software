<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsLog extends Model
{
    use BelongsToOrganization;

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    public const STATUS_BLOCKED = 'blocked';

    public const STATUS_SKIPPED = 'skipped';

    protected $fillable = [
        'purpose',
        'recipient_type',
        'recipient_name',
        'phone',
        'message',
        'status',
        'provider_reply',
        'sales_invoice_id',
        'cash_voucher_id',
        'user_id',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id');
    }

    public function cashVoucher(): BelongsTo
    {
        return $this->belongsTo(CashVoucher::class, 'cash_voucher_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_SENT => 'Sent',
            self::STATUS_BLOCKED => 'Blocked',
            self::STATUS_SKIPPED => 'Skipped',
            default => 'Failed',
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_SENT => 'bg-success',
            self::STATUS_BLOCKED => 'bg-dark',
            self::STATUS_SKIPPED => 'bg-secondary',
            default => 'bg-danger',
        };
    }
}
