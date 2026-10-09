<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\CashAccount;
use App\Models\CashVoucher;
use App\Models\Customer;
use App\Models\ExpenseAccount;
use App\Models\Investor;
use App\Models\Supplier;
use App\Models\Vendor;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class CashVoucherService
{
    public function defaultCashAccountId(): int
    {
        return CashAccount::query()
            ->orderByDesc('is_active')
            ->orderBy('id')
            ->value('id')
            ?? CashAccount::create([
                'name' => 'Shop Cash',
                'opening_balance' => 0,
                'current_balance' => 0,
                'is_active' => true,
            ])->id;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): CashVoucher
    {
        $data['cash_account_id'] = $this->defaultCashAccountId();

        if (($data['payment_method'] ?? '') === 'bank' && empty($data['bank_account_id'])) {
            throw new InvalidArgumentException('Please select a bank account.');
        }
        if (($data['payment_method'] ?? '') === 'cash') {
            $data['bank_account_id'] = null;
        }

        if (($data['account_type'] ?? '') === 'other') {
            if (empty($data['other_name'])) {
                throw new InvalidArgumentException('Please enter Other name.');
            }
            $data['account_id'] = null;
        } else {
            if (empty($data['account_id'])) {
                throw new InvalidArgumentException('Please select an account.');
            }
            $data['other_name'] = null;
        }

        $data['affects_cash'] = (bool) ($data['affects_cash'] ?? true);

        return DB::transaction(function () use ($data) {
            $amount = (float) $data['amount'];
            $type = $data['type'];
            $data['voucher_no'] = $data['voucher_no'] ?? CashVoucher::nextNumber($type);

            $voucher = CashVoucher::create($data);

            if ($voucher->affectsCashBalance()) {
                $this->applySourceBalance($voucher, $amount, $type, true);
            }

            $this->applyPartyBalance($voucher, $amount, $type, true);

            return $voucher->fresh(['cashAccount', 'bankAccount']);
        });
    }

    public function delete(CashVoucher $voucher): void
    {
        DB::transaction(function () use ($voucher) {
            $amount = (float) $voucher->amount;

            if ($voucher->affectsCashBalance()) {
                $this->applySourceBalance($voucher, $amount, $voucher->type, false);
            }

            $this->applyPartyBalance($voucher, $amount, $voucher->type, false);

            $voucher->delete();
        });
    }

    public function applySourceBalance(CashVoucher $voucher, float $amount, string $type, bool $apply): void
    {
        $receive = $apply ? ($type === 'receive') : ($type !== 'receive');

        if (($voucher->payment_method ?? 'cash') === 'cash') {
            $cash = CashAccount::whereKey($voucher->cash_account_id)->lockForUpdate()->firstOrFail();
            if ($receive) {
                $cash->increment('current_balance', $amount);
            } else {
                $cash->decrement('current_balance', $amount);
            }

            return;
        }

        $bank = BankAccount::whereKey($voucher->bank_account_id)->lockForUpdate()->firstOrFail();
        if ($apply && $type === 'payment' && (float) $bank->current_balance < $amount) {
            throw new RuntimeException('No balance in this bank account.');
        }

        if ($receive) {
            $bank->increment('current_balance', $amount);
        } else {
            $bank->decrement('current_balance', $amount);
        }
    }

    public function applyPartyBalance(CashVoucher $voucher, float $amount, string $type, bool $apply): void
    {
        if (in_array($voucher->account_type, ['other', 'salesman'], true) || empty($voucher->account_id)) {
            return;
        }

        $multiplier = $apply ? 1 : -1;

        if ($voucher->account_type === 'customer') {
            if ($type === 'receive') {
                Customer::whereKey($voucher->account_id)->decrement('current_balance', $amount * $multiplier);
            } else {
                Customer::whereKey($voucher->account_id)->increment('current_balance', $amount * $multiplier);
            }
        } elseif ($voucher->account_type === 'supplier') {
            if ($type === 'payment') {
                Supplier::whereKey($voucher->account_id)->decrement('current_balance', $amount * $multiplier);
            } else {
                Supplier::whereKey($voucher->account_id)->increment('current_balance', $amount * $multiplier);
            }
        } elseif ($voucher->account_type === 'vendor') {
            if ($type === 'payment') {
                Vendor::whereKey($voucher->account_id)->decrement('current_balance', $amount * $multiplier);
            } else {
                Vendor::whereKey($voucher->account_id)->increment('current_balance', $amount * $multiplier);
            }
        } elseif ($voucher->account_type === 'investor') {
            if ($type === 'receive') {
                Investor::whereKey($voucher->account_id)->increment('current_balance', $amount * $multiplier);
            } else {
                Investor::whereKey($voucher->account_id)->decrement('current_balance', $amount * $multiplier);
            }
        } elseif ($voucher->account_type === 'expense' && $voucher->account_id) {
            ExpenseAccount::syncTotalSpentFor((int) $voucher->account_id);
        }
    }
}
