<?php

namespace App\Services;

use App\Models\PurchaseBill;
use App\Models\PurchaseBillLine;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PurchaseBillService
{
    public function __construct(
        private VatCalculator $vat,
        private SettingsService $settings,
        private CashVoucherService $vouchers,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  list<array<string, mixed>>  $lines
     */
    public function saveDraft(?PurchaseBill $bill, array $data, array $lines, User $user): PurchaseBill
    {
        $computed = $this->vat->document($lines, $this->settings->vatRate());
        if ($computed['lines'] === []) {
            throw new InvalidArgumentException('Add at least one bill line.');
        }

        return DB::transaction(function () use ($bill, $data, $computed, $user) {
            $payload = [
                'supplier_id' => $data['supplier_id'],
                'bill_date' => $data['bill_date'],
                'due_date' => $data['due_date'] ?? null,
                'notes' => $data['notes'] ?? null,
                'subtotal' => $computed['subtotal'],
                'vat_amount' => $computed['vat_amount'],
                'total' => $computed['total'],
            ];

            if ($bill) {
                if (! $bill->isDraft()) {
                    throw new InvalidArgumentException('Posted bills cannot be edited.');
                }
                $bill->update($payload);
                $bill->lines()->delete();
            } else {
                $bill = PurchaseBill::create($payload + [
                    'status' => 'draft',
                    'created_by' => $user->id,
                    'amount_paid' => 0,
                ]);
            }

            foreach ($computed['lines'] as $line) {
                PurchaseBillLine::create([
                    'purchase_bill_id' => $bill->id,
                    'description' => $line['description'] ?? 'Item',
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'vat_rate' => $line['vat_rate'],
                    'line_net' => $line['line_net'],
                    'vat_amount' => $line['vat_amount'],
                    'line_total' => $line['line_total'],
                    'sort_order' => $line['sort_order'],
                ]);
            }

            return $bill->fresh(['lines', 'supplier']);
        });
    }

    public function post(PurchaseBill $bill): PurchaseBill
    {
        if (! $bill->isDraft()) {
            throw new InvalidArgumentException('This bill is already posted.');
        }

        return DB::transaction(function () use ($bill) {
            $bill->bill_no = $bill->bill_no ?: $this->nextNumber();
            $bill->status = 'posted';
            $bill->posted_at = now();
            $bill->save();

            Supplier::whereKey($bill->supplier_id)->increment('current_balance', (float) $bill->total);

            return $bill->fresh(['lines', 'supplier']);
        });
    }

    public function recordPayment(PurchaseBill $bill, float $amount, string $method = 'cash', ?int $bankAccountId = null): PurchaseBill
    {
        if ($bill->isDraft()) {
            throw new InvalidArgumentException('Post the bill before recording a payment.');
        }

        $due = $bill->balanceDue();
        if ($amount <= 0 || $amount > $due + 0.009) {
            throw new InvalidArgumentException('Payment must be greater than 0 and not more than '.number_format($due, 2).'.');
        }

        return DB::transaction(function () use ($bill, $amount, $method, $bankAccountId) {
            $this->vouchers->create([
                'type' => 'payment',
                'payment_method' => $method === 'bank' ? 'bank' : 'cash',
                'bank_account_id' => $bankAccountId,
                'account_type' => 'supplier',
                'account_id' => $bill->supplier_id,
                'amount' => $amount,
                'voucher_date' => now()->toDateString(),
                'reference' => $bill->bill_no,
                'notes' => 'Payment for bill '.$bill->bill_no,
            ]);

            $bill->amount_paid = round((float) $bill->amount_paid + $amount, 2);
            $bill->status = $bill->balanceDue() <= 0.009 ? 'paid' : 'posted';
            $bill->save();

            return $bill->fresh(['supplier']);
        });
    }

    private function nextNumber(): string
    {
        $year = now()->format('Y');
        $count = PurchaseBill::query()
            ->whereYear('created_at', $year)
            ->whereNotNull('bill_no')
            ->count() + 1;

        return 'BILL-'.$year.'-'.str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }
}
