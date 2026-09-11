<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\CashAccount;
use App\Models\Customer;
use App\Models\ExpenseAccount;
use App\Models\Investor;
use App\Models\JournalVoucher;
use App\Models\JournalVoucherLine;
use App\Models\NominalAccount;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JournalVoucherController extends Controller
{
    private function accountOptions(): array
    {
        return [
            'customers' => Customer::orderBy('name')->get(),
            'suppliers' => Supplier::orderBy('name')->get(),
            'investors' => Investor::orderBy('name')->get(),
            'expenseAccounts' => ExpenseAccount::orderBy('name')->get(),
            'bankAccounts' => BankAccount::orderBy('name')->get(),
            'cashAccounts' => CashAccount::orderBy('name')->get(),
            'nominalAccounts' => NominalAccount::orderBy('name')->get(),
        ];
    }

    public function index()
    {
        $journalVouchers = JournalVoucher::orderByDesc('voucher_date')
            ->orderByDesc('id')
            ->limit(500)
            ->get();

        return view('journal-vouchers.index', compact('journalVouchers'));
    }

    public function create()
    {
        return view('journal-vouchers.create', array_merge($this->accountOptions(), [
            'previewVoucherNo' => 'JV-'.now()->format('Ymd-His').'-'.random_int(100, 999),
        ]));
    }

    public function store(Request $request)
    {
        $data = $this->validateVoucher($request);

        DB::transaction(function () use ($data) {
            $totals = $this->totalsFromLines($data['lines']);

            $voucher = JournalVoucher::create([
                'voucher_no' => 'JV-'.now()->format('Ymd-His').'-'.random_int(100, 999),
                'voucher_date' => $data['voucher_date'],
                'notes' => $data['notes'] ?? null,
                'total_debit' => $totals['debit'],
                'total_credit' => $totals['credit'],
            ]);

            foreach ($data['lines'] as $line) {
                $lineModel = JournalVoucherLine::create([
                    'journal_voucher_id' => $voucher->id,
                    'account_type' => $line['account_type'],
                    'account_id' => $line['account_id'],
                    'debit' => $line['debit'],
                    'credit' => $line['credit'],
                    'line_note' => $line['line_note'] ?? null,
                ]);

                $this->applyLineBalance($lineModel, true);
            }
        });

        return redirect()->route('journal-vouchers.index')->with('success', 'Journal voucher created.');
    }

    public function show(JournalVoucher $journalVoucher)
    {
        return redirect()->route('journal-vouchers.edit', $journalVoucher);
    }

    public function edit(JournalVoucher $journalVoucher)
    {
        $journalVoucher->load('lines');

        return view('journal-vouchers.edit', array_merge(
            ['journalVoucher' => $journalVoucher],
            $this->accountOptions()
        ));
    }

    public function update(Request $request, JournalVoucher $journalVoucher)
    {
        $data = $this->validateVoucher($request);

        DB::transaction(function () use ($journalVoucher, $data) {
            $journalVoucher->load('lines');

            foreach ($journalVoucher->lines as $line) {
                $this->applyLineBalance($line, false);
            }

            $journalVoucher->lines()->delete();

            $totals = $this->totalsFromLines($data['lines']);

            $journalVoucher->update([
                'voucher_date' => $data['voucher_date'],
                'notes' => $data['notes'] ?? null,
                'total_debit' => $totals['debit'],
                'total_credit' => $totals['credit'],
            ]);

            foreach ($data['lines'] as $line) {
                $lineModel = JournalVoucherLine::create([
                    'journal_voucher_id' => $journalVoucher->id,
                    'account_type' => $line['account_type'],
                    'account_id' => $line['account_id'],
                    'debit' => $line['debit'],
                    'credit' => $line['credit'],
                    'line_note' => $line['line_note'] ?? null,
                ]);

                $this->applyLineBalance($lineModel, true);
            }
        });

        return redirect()->route('journal-vouchers.index')->with('success', 'Journal voucher updated.');
    }

    public function destroy(JournalVoucher $journalVoucher)
    {
        DB::transaction(function () use ($journalVoucher) {
            $journalVoucher->load('lines');

            foreach ($journalVoucher->lines as $line) {
                $this->applyLineBalance($line, false);
            }

            $journalVoucher->delete();
        });

        return back()->with('success', 'Journal voucher deleted.');
    }

    private function validateVoucher(Request $request): array
    {
        $data = $request->validate([
            'voucher_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.account_type' => ['required', 'in:customer,supplier,investor,expense,bank,cash,nominal'],
            'lines.*.account_id' => ['required', 'integer'],
            'lines.*.debit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.credit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.line_note' => ['nullable', 'string', 'max:255'],
        ]);

        $normalized = [];
        foreach ($data['lines'] as $line) {
            $debit = round((float) ($line['debit'] ?? 0), 2);
            $credit = round((float) ($line['credit'] ?? 0), 2);

            if (($debit > 0 && $credit > 0) || ($debit <= 0 && $credit <= 0)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'lines' => 'Each line must have either a debit or a credit amount (not both).',
                ]);
            }

            $normalized[] = [
                'account_type' => $line['account_type'],
                'account_id' => (int) $line['account_id'],
                'debit' => $debit,
                'credit' => $credit,
                'line_note' => $line['line_note'] ?? null,
            ];
        }

        $totals = $this->totalsFromLines($normalized);

        if (abs($totals['debit'] - $totals['credit']) > 0.009) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'lines' => 'Total debit must equal total credit.',
            ]);
        }

        $data['lines'] = $normalized;

        return $data;
    }

    private function totalsFromLines(array $lines): array
    {
        $debit = 0.0;
        $credit = 0.0;

        foreach ($lines as $line) {
            $debit += (float) $line['debit'];
            $credit += (float) $line['credit'];
        }

        return ['debit' => $debit, 'credit' => $credit];
    }

    private function applyLineBalance(JournalVoucherLine $line, bool $apply): void
    {
        $debit = (float) $line->debit;
        $credit = (float) $line->credit;
        $multiplier = $apply ? 1 : -1;

        if ($line->account_type === 'nominal') {
            return;
        }

        match ($line->account_type) {
            'customer' => $this->adjustCustomer($line->account_id, $debit, $credit, $multiplier),
            'supplier' => $this->adjustSupplier($line->account_id, $debit, $credit, $multiplier),
            'investor' => $this->adjustInvestor($line->account_id, $debit, $credit, $multiplier),
            'expense' => $this->adjustExpense($line->account_id, $debit, $credit, $multiplier),
            'bank' => $this->adjustBank($line->account_id, $debit, $credit, $multiplier),
            'cash' => $this->adjustCash($line->account_id, $debit, $credit, $multiplier),
            default => null,
        };
    }

    private function adjustCustomer(int $id, float $debit, float $credit, int $multiplier): void
    {
        $model = Customer::whereKey($id)->lockForUpdate()->firstOrFail();
        $delta = ($debit - $credit) * $multiplier;
        $model->increment('current_balance', $delta);
    }

    private function adjustSupplier(int $id, float $debit, float $credit, int $multiplier): void
    {
        $model = Supplier::whereKey($id)->lockForUpdate()->firstOrFail();
        $delta = ($credit - $debit) * $multiplier;
        $model->increment('current_balance', $delta);
    }

    private function adjustInvestor(int $id, float $debit, float $credit, int $multiplier): void
    {
        $model = Investor::whereKey($id)->lockForUpdate()->firstOrFail();
        $delta = ($credit - $debit) * $multiplier;
        $model->increment('current_balance', $delta);
    }

    private function adjustExpense(int $id, float $debit, float $credit, int $multiplier): void
    {
        unset($debit, $credit, $multiplier);
        ExpenseAccount::whereKey($id)->lockForUpdate()->firstOrFail();
    }

    private function adjustBank(int $id, float $debit, float $credit, int $multiplier): void
    {
        $model = BankAccount::whereKey($id)->lockForUpdate()->firstOrFail();
        $delta = ($debit - $credit) * $multiplier;
        $model->increment('current_balance', $delta);
    }

    private function adjustCash(int $id, float $debit, float $credit, int $multiplier): void
    {
        $model = CashAccount::whereKey($id)->lockForUpdate()->firstOrFail();
        $delta = ($debit - $credit) * $multiplier;
        $model->increment('current_balance', $delta);
    }
}
