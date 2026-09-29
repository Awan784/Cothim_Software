<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesmanSettlementAllocation extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'salesman_settlement_id',
        'sales_invoice_id',
        'customer_id',
        'cash_voucher_id',
        'amount',
    ];

    protected $casts = [
        'amount' => 'float',
    ];

    public function settlement(): BelongsTo
    {
        return $this->belongsTo(SalesmanSettlement::class, 'salesman_settlement_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function cashVoucher(): BelongsTo
    {
        return $this->belongsTo(CashVoucher::class);
    }
}
