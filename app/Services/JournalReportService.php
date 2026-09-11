<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\CashAccount;
use App\Models\Customer;
use App\Models\ExpenseAccount;
use App\Models\Investor;
use App\Models\JournalVoucher;
use App\Models\JournalVoucherLine;
use App\Models\NominalAccount;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class JournalReportService
{
    /**
     * @return array{
     *     vouchers: \Illuminate\Support\Collection<int, array<string, mixed>>,
     *     grandTotalDebit: float,
     *     grandTotalCredit: float,
     *     voucherCount: int,
     *     lineCount: int
     * }
     */
    public function report(Carbon $from, Carbon $to): array
    {
        $journalVouchers = JournalVoucher::query()
            ->with('lines')
            ->whereDate('voucher_date', '>=', $from->toDateString())
            ->whereDate('voucher_date', '<=', $to->toDateString())
            ->orderBy('voucher_date')
            ->orderBy('id')
            ->get();

        $allLines = $journalVouchers->flatMap(fn ($v) => $v->lines);
        $this->loadAccountNames($allLines);

        $grandDebit = 0.0;
        $grandCredit = 0.0;
        $lineCount = 0;

        $vouchers = $journalVouchers->map(function (JournalVoucher $voucher) use (&$grandDebit, &$grandCredit, &$lineCount) {
            $lines = $voucher->lines->map(function (JournalVoucherLine $line) {
                return [
                    'account_type' => $this->accountTypeLabel($line->account_type),
                    'account_name' => $line->account_display_name ?? '—',
                    'narration' => $line->line_note ?? '',
                    'debit' => (float) $line->debit,
                    'credit' => (float) $line->credit,
                ];
            })->values();

            $lineCount += $lines->count();
            $grandDebit += (float) $voucher->total_debit;
            $grandCredit += (float) $voucher->total_credit;

            return [
                'date' => $voucher->voucher_date,
                'voucher_no' => $voucher->voucher_no,
                'notes' => $voucher->notes ?? '',
                'total_debit' => (float) $voucher->total_debit,
                'total_credit' => (float) $voucher->total_credit,
                'lines' => $lines,
            ];
        })->values();

        return [
            'vouchers' => $vouchers,
            'grandTotalDebit' => $grandDebit,
            'grandTotalCredit' => $grandCredit,
            'voucherCount' => $vouchers->count(),
            'lineCount' => $lineCount,
        ];
    }

    /**
     * @param  Collection<int, JournalVoucherLine>|\Illuminate\Support\Collection<int, JournalVoucherLine>  $lines
     */
    public function loadAccountNames(Collection $lines): void
    {
        $models = [
            'customer' => Customer::class,
            'supplier' => Supplier::class,
            'investor' => Investor::class,
            'expense' => ExpenseAccount::class,
            'bank' => BankAccount::class,
            'cash' => CashAccount::class,
            'nominal' => NominalAccount::class,
        ];

        $namesByType = [];

        foreach ($models as $type => $modelClass) {
            $ids = $lines
                ->where('account_type', $type)
                ->pluck('account_id')
                ->filter()
                ->unique()
                ->values();

            if ($ids->isNotEmpty()) {
                $namesByType[$type] = $modelClass::query()
                    ->whereIn('id', $ids)
                    ->pluck('name', 'id');
            }
        }

        foreach ($lines as $line) {
            $name = $namesByType[$line->account_type][$line->account_id] ?? null;
            $line->setAttribute('account_display_name', $name);
        }
    }

    private function accountTypeLabel(?string $accountType): string
    {
        return match ($accountType) {
            'customer' => 'Customer',
            'supplier' => 'Supplier',
            'investor' => 'Investor',
            'expense' => 'Expense',
            'bank' => 'Bank',
            'cash' => 'Cash',
            'nominal' => 'Nominal',
            default => $accountType ? ucfirst($accountType) : '—',
        };
    }
}
