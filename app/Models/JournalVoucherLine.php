<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalVoucherLine extends Model
{
    protected $fillable = [
        'journal_voucher_id',
        'account_type',
        'account_id',
        'debit',
        'credit',
        'line_note',
    ];

    public function journalVoucher(): BelongsTo
    {
        return $this->belongsTo(JournalVoucher::class);
    }

    public function accountDisplayName(): ?string
    {
        if (empty($this->account_id)) {
            return null;
        }

        return match ($this->account_type) {
            'customer' => Customer::find($this->account_id)?->name,
            'supplier' => Supplier::find($this->account_id)?->name,
            'investor' => Investor::find($this->account_id)?->name,
            'expense' => ExpenseAccount::find($this->account_id)?->name,
            'bank' => BankAccount::find($this->account_id)?->name,
            'cash' => CashAccount::find($this->account_id)?->name,
            'nominal' => NominalAccount::find($this->account_id)?->name,
            default => null,
        };
    }
}
