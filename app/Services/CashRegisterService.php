<?php

namespace App\Services;

use App\Models\CashAccount;
use App\Models\CashVoucher;
use App\Models\JournalVoucherLine;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CashRegisterService
{
    /**
     * @return array{
     *     entries: \Illuminate\Support\Collection<int, array<string, mixed>>,
     *     broughtForward: float,
     *     closingBalance: float,
     *     totalCashIn: float,
     *     totalCashOut: float
     * }
     */
    public function register(Carbon $from, Carbon $to): array
    {
        $broughtForward = $this->openingCashBalance()
            + $this->netCashMovement(null, $from->copy()->subDay());

        $raw = $this->collectMovements($from, $to)
            ->sortBy([
                ['date', 'asc'],
                ['sort_key', 'asc'],
            ])
            ->values();

        $running = $broughtForward;
        $entries = collect();
        $totalCashIn = 0.0;
        $totalCashOut = 0.0;

        if (abs($broughtForward) > 0.005) {
            $entries->push([
                'date' => $from->copy(),
                'voucher_no' => '—',
                'type' => '',
                'type_label' => 'Brought Forward',
                'party_type' => '',
                'party_name' => '',
                'paid_via' => '',
                'reference' => '',
                'notes' => '',
                'amount' => abs($broughtForward),
                'cash_in' => $broughtForward > 0 ? $broughtForward : 0.0,
                'cash_out' => $broughtForward < 0 ? abs($broughtForward) : 0.0,
                'balance' => $broughtForward,
                'affects_cash_balance' => true,
                'is_brought_forward' => true,
            ]);
        }

        foreach ($raw as $row) {
            $affectsBalance = ! empty($row['affects_cash_balance']);
            $cashIn = (float) $row['cash_in'];
            $cashOut = (float) $row['cash_out'];

            if ($affectsBalance) {
                $totalCashIn += $cashIn;
                $totalCashOut += $cashOut;
                $running += $cashIn - $cashOut;
            }

            $entries->push(array_merge($row, [
                'balance' => $running,
                'is_brought_forward' => false,
            ]));
        }

        return [
            'entries' => $entries,
            'broughtForward' => $broughtForward,
            'closingBalance' => $running,
            'totalCashIn' => $totalCashIn,
            'totalCashOut' => $totalCashOut,
        ];
    }

    private function collectMovements(Carbon $from, Carbon $to): Collection
    {
        $entries = collect();

        $vouchers = CashVoucher::query()
            ->with(['cashAccount', 'bankAccount'])
            ->whereDate('voucher_date', '>=', $from->toDateString())
            ->whereDate('voucher_date', '<=', $to->toDateString())
            ->orderBy('voucher_date')
            ->orderBy('id')
            ->get();

        CashVoucher::loadAccountNames($vouchers);

        foreach ($vouchers as $voucher) {
            $entries->push($this->entryFromCashVoucher($voucher));
        }

        $journalQuery = JournalVoucherLine::query()
            ->where('account_type', 'cash')
            ->with('journalVoucher')
            ->whereHas('journalVoucher', function ($q) use ($from, $to) {
                $q->whereDate('voucher_date', '>=', $from->toDateString())
                    ->whereDate('voucher_date', '<=', $to->toDateString());
            });

        foreach ($journalQuery->get() as $line) {
            $voucher = $line->journalVoucher;
            if (! $voucher) {
                continue;
            }

            $debit = (float) $line->debit;
            $credit = (float) $line->credit;
            $cashAccountName = CashAccount::find($line->account_id)?->name ?? 'Cash';

            $entries->push([
                'date' => $voucher->voucher_date,
                'sort_key' => 'jv-'.$line->id,
                'voucher_no' => $voucher->voucher_no,
                'type' => 'journal',
                'type_label' => 'Journal Entry',
                'party_type' => 'Cash',
                'party_name' => $cashAccountName,
                'paid_via' => 'Cash',
                'reference' => '',
                'notes' => $line->line_note ?? $voucher->notes ?? '',
                'amount' => max($debit, $credit),
                'cash_in' => $debit,
                'cash_out' => $credit,
                'affects_cash_balance' => true,
            ]);
        }

        return $entries;
    }

    /**
     * @return array<string, mixed>
     */
    private function entryFromCashVoucher(CashVoucher $voucher): array
    {
        $amount = (float) $voucher->amount;
        $isCash = ($voucher->payment_method ?? 'cash') === 'cash';

        $partyName = $voucher->account_type === 'other'
            ? ($voucher->other_name ?? '—')
            : ($voucher->account_display_name ?? '—');

        if ($isCash) {
            $paidVia = $voucher->cashAccount?->name
                ? 'Cash — '.$voucher->cashAccount->name
                : 'Cash';
        } else {
            $paidVia = $voucher->bankAccount?->name
                ? 'Bank — '.$voucher->bankAccount->name
                : 'Bank';
        }

        return [
            'date' => $voucher->voucher_date,
            'sort_key' => 'cv-'.$voucher->id,
            'voucher_no' => $voucher->voucher_no,
            'type' => $voucher->type,
            'type_label' => $this->transactionLabel($voucher),
            'party_type' => $this->partyTypeLabel($voucher->account_type),
            'party_name' => $partyName,
            'paid_via' => $paidVia,
            'reference' => $voucher->reference ?? '',
            'notes' => $voucher->notes ?? '',
            'amount' => $amount,
            'cash_in' => $isCash && $voucher->type === 'receive' ? $amount : 0.0,
            'cash_out' => $isCash && $voucher->type === 'payment' ? $amount : 0.0,
            'affects_cash_balance' => $isCash,
        ];
    }

    private function transactionLabel(CashVoucher $voucher): string
    {
        $isReceive = $voucher->type === 'receive';

        return match ($voucher->account_type) {
            'customer' => $isReceive ? 'Cash Receive (Customer)' : 'Cash Payment (Customer)',
            'supplier' => $isReceive ? 'Cash Receive (Supplier)' : 'Cash Payment (Supplier)',
            'investor' => $isReceive ? 'Cash Receive (Investor)' : 'Cash Payment (Investor)',
            'expense' => $isReceive ? 'Expense Receive' : 'Expense Payment',
            'other' => $isReceive ? 'Cash Receive (Other)' : 'Cash Payment (Other)',
            default => $isReceive ? 'Cash Receive' : 'Cash Payment',
        };
    }

    private function openingCashBalance(): float
    {
        return (float) CashAccount::sum('opening_balance');
    }

    private function netCashMovement(?Carbon $from, ?Carbon $to): float
    {
        $net = 0.0;

        $voucherQuery = CashVoucher::query()->where('payment_method', 'cash');

        if ($from) {
            $voucherQuery->whereDate('voucher_date', '>=', $from->toDateString());
        }
        if ($to) {
            $voucherQuery->whereDate('voucher_date', '<=', $to->toDateString());
        }

        foreach ($voucherQuery->get() as $voucher) {
            $amount = (float) $voucher->amount;
            $net += $voucher->type === 'receive' ? $amount : -$amount;
        }

        $journalQuery = JournalVoucherLine::query()
            ->where('account_type', 'cash')
            ->with('journalVoucher');

        $journalQuery->whereHas('journalVoucher', function ($q) use ($from, $to) {
            if ($from) {
                $q->whereDate('voucher_date', '>=', $from->toDateString());
            }
            if ($to) {
                $q->whereDate('voucher_date', '<=', $to->toDateString());
            }
        });

        foreach ($journalQuery->get() as $line) {
            $net += (float) $line->debit - (float) $line->credit;
        }

        return $net;
    }

    private function partyTypeLabel(?string $accountType): string
    {
        return match ($accountType) {
            'customer' => 'Customer',
            'supplier' => 'Supplier',
            'investor' => 'Investor',
            'expense' => 'Expense',
            'other' => 'Other',
            default => $accountType ? ucfirst($accountType) : '—',
        };
    }
}
