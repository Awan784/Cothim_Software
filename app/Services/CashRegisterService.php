<?php

namespace App\Services;

use App\Models\BankAccount;
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
     *     openingAccounts: \Illuminate\Support\Collection<int, array<string, mixed>>,
     *     broughtForward: float,
     *     closingBalance: float,
     *     totalCashIn: float,
     *     totalCashOut: float
     * }
     */
    public function register(Carbon $from, Carbon $to): array
    {
        $openingAccounts = $this->openingAccounts($from);
        $broughtForward = round((float) $openingAccounts->sum('balance'), 2);

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

        $entries->push([
            'date' => $from->copy(),
            'voucher_no' => '—',
            'type' => '',
            'type_label' => 'Opening Balance (Cash + Bank)',
            'party_type' => '',
            'party_name' => '',
            'paid_via' => '',
            'reference' => '',
            'notes' => $openingAccounts
                ->filter(fn (array $row) => abs((float) $row['balance']) > 0.005)
                ->map(fn (array $row) => $row['name'].' '.number_format((float) $row['balance'], 2))
                ->implode(' · '),
            'amount' => abs($broughtForward),
            'cash_in' => $broughtForward > 0 ? $broughtForward : 0.0,
            'cash_out' => $broughtForward < 0 ? abs($broughtForward) : 0.0,
            'balance' => $broughtForward,
            'affects_cash_balance' => true,
            'is_brought_forward' => true,
        ]);

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
                'balance' => round($running, 2),
                'is_brought_forward' => false,
            ]));
        }

        return [
            'entries' => $entries,
            'openingAccounts' => $openingAccounts,
            'broughtForward' => $broughtForward,
            'closingBalance' => round($running, 2),
            'totalCashIn' => round($totalCashIn, 2),
            'totalCashOut' => round($totalCashOut, 2),
        ];
    }

    /**
     * Cash + bank balances as of the morning of $from (movements before from_date).
     *
     * @return \Illuminate\Support\Collection<int, array{kind: string, kind_label: string, id: int, name: string, balance: float}>
     */
    private function openingAccounts(Carbon $from): Collection
    {
        $before = $from->copy()->subDay()->endOfDay();
        $rows = collect();

        foreach (CashAccount::query()->orderBy('name')->orderBy('id')->get() as $cash) {
            $balance = (float) $cash->opening_balance
                + $this->netVoucherMovement('cash', (int) $cash->id, null, $before)
                + $this->netJournalMovement('cash', (int) $cash->id, null, $before);

            $rows->push([
                'kind' => 'cash',
                'kind_label' => 'Cash',
                'id' => (int) $cash->id,
                'name' => (string) $cash->name,
                'balance' => round($balance, 2),
            ]);
        }

        foreach (BankAccount::query()->orderBy('name')->orderBy('id')->get() as $bank) {
            $balance = (float) $bank->opening_balance
                + $this->netVoucherMovement('bank', (int) $bank->id, null, $before)
                + $this->netJournalMovement('bank', (int) $bank->id, null, $before);

            $label = trim((string) $bank->name);
            if (! empty($bank->bank_name) && strcasecmp((string) $bank->bank_name, $label) !== 0) {
                $label .= ' — '.$bank->bank_name;
            }

            $rows->push([
                'kind' => 'bank',
                'kind_label' => $bank->is_cash ? 'Shop Cash' : 'Bank',
                'id' => (int) $bank->id,
                'name' => $label,
                'balance' => round($balance, 2),
            ]);
        }

        return $rows;
    }

    private function collectMovements(Carbon $from, Carbon $to): Collection
    {
        $entries = collect();

        $vouchers = CashVoucher::query()
            ->with(['cashAccount', 'bankAccount', 'salesmanSettlement'])
            ->affectingCash()
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
            ->whereIn('account_type', ['cash', 'bank'])
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
            $isCash = $line->account_type === 'cash';
            $accountName = $isCash
                ? (CashAccount::find($line->account_id)?->name ?? 'Cash')
                : (BankAccount::find($line->account_id)?->name ?? 'Bank');

            $entries->push([
                'date' => $voucher->voucher_date,
                'sort_key' => 'jv-'.$line->id,
                'voucher_no' => $voucher->voucher_no,
                'type' => 'journal',
                'type_label' => 'Journal Entry',
                'party_type' => $isCash ? 'Cash' : 'Bank',
                'party_name' => $accountName,
                'paid_via' => $isCash ? 'Cash' : 'Bank',
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
        $isCashMethod = ($voucher->payment_method ?? 'cash') === 'cash';

        $partyName = $voucher->account_type === 'other'
            ? ($voucher->other_name ?? '—')
            : ($voucher->account_display_name ?? '—');

        if ($isCashMethod) {
            $paidVia = $voucher->cashAccount?->name
                ? 'Cash — '.$voucher->cashAccount->name
                : 'Cash';
        } else {
            $paidVia = $voucher->bankAccount?->name
                ? 'Bank — '.$voucher->bankAccount->name
                : 'Bank';
        }

        $affectsBalance = $voucher->affectsCashBalance();

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
            'notes' => $voucher->cashBookNotes(),
            'amount' => $amount,
            'cash_in' => $affectsBalance && $voucher->type === 'receive' ? $amount : 0.0,
            'cash_out' => $affectsBalance && $voucher->type === 'payment' ? $amount : 0.0,
            'affects_cash_balance' => $affectsBalance,
        ];
    }

    private function transactionLabel(CashVoucher $voucher): string
    {
        $isReceive = $voucher->type === 'receive';
        $via = ($voucher->payment_method ?? 'cash') === 'bank' ? 'Bank' : 'Cash';

        if ($voucher->isSettlementCash()) {
            return $isReceive ? 'Settlement receive (Salesman)' : 'Settlement payment (Salesman)';
        }

        return match ($voucher->account_type) {
            'customer' => $isReceive ? $via.' Receive (Customer)' : $via.' Payment (Customer)',
            'supplier' => $isReceive ? $via.' Receive (Supplier)' : $via.' Payment (Supplier)',
            'vendor' => $isReceive ? $via.' Receive (Vendor)' : $via.' Payment (Vendor)',
            'investor' => $isReceive ? $via.' Receive (Investor)' : $via.' Payment (Investor)',
            'expense' => $isReceive ? 'Expense Receive' : 'Expense Payment',
            'salesman' => $isReceive ? $via.' Receive (Salesman)' : $via.' Payment (Salesman)',
            'other' => $isReceive ? $via.' Receive (Other)' : $via.' Payment (Other)',
            default => $isReceive ? $via.' Receive' : $via.' Payment',
        };
    }

    private function netVoucherMovement(string $source, int $accountId, ?Carbon $from, ?Carbon $to): float
    {
        $query = CashVoucher::query()->affectingCash();

        if ($source === 'cash') {
            $query->where('payment_method', 'cash')->where('cash_account_id', $accountId);
        } else {
            $query->where('payment_method', 'bank')->where('bank_account_id', $accountId);
        }

        if ($from) {
            $query->whereDate('voucher_date', '>=', $from->toDateString());
        }
        if ($to) {
            $query->whereDate('voucher_date', '<=', $to->toDateString());
        }

        $net = 0.0;
        foreach ($query->get(['type', 'amount']) as $voucher) {
            $amount = (float) $voucher->amount;
            $net += $voucher->type === 'receive' ? $amount : -$amount;
        }

        return $net;
    }

    private function netJournalMovement(string $accountType, int $accountId, ?Carbon $from, ?Carbon $to): float
    {
        $query = JournalVoucherLine::query()
            ->where('account_type', $accountType)
            ->where('account_id', $accountId)
            ->with('journalVoucher');

        $query->whereHas('journalVoucher', function ($q) use ($from, $to) {
            if ($from) {
                $q->whereDate('voucher_date', '>=', $from->toDateString());
            }
            if ($to) {
                $q->whereDate('voucher_date', '<=', $to->toDateString());
            }
        });

        $net = 0.0;
        foreach ($query->get() as $line) {
            $net += (float) $line->debit - (float) $line->credit;
        }

        return $net;
    }

    private function partyTypeLabel(?string $accountType): string
    {
        return match ($accountType) {
            'customer' => 'Customer',
            'supplier' => 'Supplier',
            'vendor' => 'Vendor',
            'investor' => 'Investor',
            'expense' => 'Expense',
            'salesman' => 'Salesman',
            'other' => 'Other',
            default => $accountType ? ucfirst($accountType) : '—',
        };
    }
}
