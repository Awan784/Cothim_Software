<?php

namespace App\Services;

use App\Models\CashVoucher;
use App\Models\Customer;
use App\Models\ExpenseAccount;
use App\Models\Investor;
use App\Models\JournalVoucherLine;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class PartyLedgerService
{
    public const ACCOUNT_TYPES = [
        'customer' => 'Customer',
        'supplier' => 'Supplier',
        'investor' => 'Investor',
        'expense' => 'Expense Account',
    ];

    public function accountsForType(string $accountType): Collection
    {
        $this->assertAccountType($accountType);

        $query = match ($accountType) {
            'customer' => Customer::query(),
            'supplier' => Supplier::query(),
            'investor' => Investor::query(),
            'expense' => ExpenseAccount::query(),
        };

        return $query->orderBy('name')->get(['id', 'name']);
    }

    public function partyName(string $accountType, int $accountId): ?string
    {
        return $this->accountsForType($accountType)->firstWhere('id', $accountId)?->name;
    }

    public function partyCode(string $accountType, int $accountId): string
    {
        $prefix = match ($accountType) {
            'customer' => 1000,
            'supplier' => 2000,
            'investor' => 3000,
            'expense' => 4000,
            default => 0,
        };

        return (string) ($prefix + $accountId);
    }

    /**
     * @return array{
     *     entries: \Illuminate\Support\Collection<int, array<string, mixed>>,
     *     broughtForward: float,
     *     closingBalance: float,
     *     totalDebit: float,
     *     totalCredit: float
     * }
     */
    public function ledger(string $accountType, int $accountId, Carbon $from, Carbon $to): array
    {
        $this->assertAccountType($accountType);
        $this->assertPartyExists($accountType, $accountId);

        $all = $this->collectEntries($accountType, $accountId)
            ->sortBy([
                ['date', 'asc'],
                ['sort_key', 'asc'],
            ])
            ->values();

        $broughtForward = 0.0;
        foreach ($all as $entry) {
            $date = $entry['date'];
            if ($date instanceof Carbon && $date->lt($from)) {
                $broughtForward += $this->balanceDelta(
                    $accountType,
                    (float) $entry['debit'],
                    (float) $entry['credit']
                );
            }
        }

        $period = $all->filter(function ($entry) use ($from, $to) {
            $date = $entry['date'];
            if (! $date instanceof Carbon) {
                return false;
            }

            return $date->betweenIncluded($from, $to);
        })->values();

        $running = $broughtForward;
        $entries = collect();

        if (abs($broughtForward) > 0.005) {
            [$bfDebit, $bfCredit] = $this->splitBalanceForDisplay($accountType, $broughtForward);
            $entries->push([
                'date' => $from->copy(),
                'ref' => '—',
                'description' => 'Brought Forward',
                'notes' => '',
                'debit' => $bfDebit,
                'credit' => $bfCredit,
                'balance' => $broughtForward,
                'is_brought_forward' => true,
            ]);
        }

        $totalDebit = 0.0;
        $totalCredit = 0.0;

        foreach ($period as $entry) {
            $debit = (float) $entry['debit'];
            $credit = (float) $entry['credit'];
            $totalDebit += $debit;
            $totalCredit += $credit;
            $running += $this->balanceDelta($accountType, $debit, $credit);

            $entries->push([
                'date' => $entry['date'],
                'ref' => $entry['ref'],
                'description' => $entry['description'],
                'notes' => $entry['notes'] ?? '',
                'debit' => $debit,
                'credit' => $credit,
                'balance' => $running,
                'is_brought_forward' => false,
                'tone' => $entry['tone'] ?? null,
            ]);
        }

        return [
            'entries' => $entries,
            'broughtForward' => $broughtForward,
            'closingBalance' => $running,
            'totalDebit' => $totalDebit,
            'totalCredit' => $totalCredit,
        ];
    }

    private function collectEntries(string $accountType, int $accountId): Collection
    {
        $entries = collect();

        if ($accountType === 'supplier') {
            foreach (PurchaseOrder::where('supplier_id', $accountId)->get() as $po) {
                $entries->push([
                    'date' => $po->po_date,
                    'sort_key' => 'po-'.$po->id,
                    'ref' => $po->po_no,
                    'description' => 'Purchase order',
                    'notes' => (string) ($po->notes ?? ''),
                    'debit' => 0.0,
                    'credit' => (float) $po->total_amount,
                    'tone' => null,
                ]);
            }
        }

        foreach (CashVoucher::where('account_type', $accountType)
            ->where('account_id', $accountId)
            ->orderBy('voucher_date')
            ->orderBy('id')
            ->get() as $voucher) {
            $entries->push($this->entryFromCashVoucher($accountType, $voucher));
        }

        foreach (JournalVoucherLine::query()
            ->where('account_type', $accountType)
            ->where('account_id', $accountId)
            ->with('journalVoucher')
            ->get() as $line) {
            $voucher = $line->journalVoucher;
            if (! $voucher) {
                continue;
            }

            $entries->push([
                'date' => $voucher->voucher_date,
                'sort_key' => 'jv-'.$line->id,
                'ref' => $voucher->voucher_no,
                'description' => $line->line_note ?: 'Journal voucher',
                'notes' => (string) ($voucher->notes ?? ''),
                'debit' => (float) $line->debit,
                'credit' => (float) $line->credit,
                'tone' => (float) $line->debit > 0 ? 'payment' : ((float) $line->credit > 0 ? 'receive' : null),
            ]);
        }

        return $entries;
    }

    /**
     * @return array<string, mixed>
     */
    private function entryFromCashVoucher(string $accountType, CashVoucher $voucher): array
    {
        $amount = (float) $voucher->amount;
        $debit = 0.0;
        $credit = 0.0;

        if ($accountType === 'customer') {
            $debit = $voucher->type === 'payment' ? $amount : 0.0;
            $credit = $voucher->type === 'receive' ? $amount : 0.0;
        } elseif ($accountType === 'supplier') {
            $debit = $voucher->type === 'payment' ? $amount : 0.0;
            $credit = $voucher->type === 'receive' ? $amount : 0.0;
        } elseif ($accountType === 'investor') {
            $debit = $voucher->type === 'payment' ? $amount : 0.0;
            $credit = $voucher->type === 'receive' ? $amount : 0.0;
        } elseif ($accountType === 'expense') {
            $debit = $voucher->type === 'payment' ? $amount : 0.0;
            $credit = $voucher->type === 'receive' ? $amount : 0.0;
        }

        $paymentLabel = ucfirst($voucher->payment_method ?? 'cash');

        return [
            'date' => $voucher->voucher_date,
            'sort_key' => 'cv-'.$voucher->id,
            'ref' => $voucher->voucher_no,
            'description' => 'Cash voucher ('.strtoupper($voucher->type).', '.$paymentLabel.')',
            'notes' => (string) ($voucher->notes ?? ''),
            'debit' => $debit,
            'credit' => $credit,
            'tone' => $voucher->type,
        ];
    }

    private function balanceDelta(string $accountType, float $debit, float $credit): float
    {
        return match ($accountType) {
            'customer', 'expense' => $debit - $credit,
            'supplier', 'investor' => $credit - $debit,
            default => 0.0,
        };
    }

    /**
     * @return array{0: float, 1: float}
     */
    private function splitBalanceForDisplay(string $accountType, float $balance): array
    {
        if (in_array($accountType, ['customer', 'expense'], true)) {
            return [
                $balance > 0 ? $balance : 0.0,
                $balance < 0 ? abs($balance) : 0.0,
            ];
        }

        return [
            $balance < 0 ? abs($balance) : 0.0,
            $balance > 0 ? $balance : 0.0,
        ];
    }

    private function assertAccountType(string $accountType): void
    {
        if (! array_key_exists($accountType, self::ACCOUNT_TYPES)) {
            throw new InvalidArgumentException('Invalid account type.');
        }
    }

    private function assertPartyExists(string $accountType, int $accountId): void
    {
        $exists = match ($accountType) {
            'customer' => Customer::whereKey($accountId)->exists(),
            'supplier' => Supplier::whereKey($accountId)->exists(),
            'investor' => Investor::whereKey($accountId)->exists(),
            'expense' => ExpenseAccount::whereKey($accountId)->exists(),
            default => false,
        };

        if (! $exists) {
            throw new InvalidArgumentException('Account not found.');
        }
    }
}
