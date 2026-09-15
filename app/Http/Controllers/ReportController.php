<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Supplier;
use App\Services\CashRegisterService;
use App\Services\JournalReportService;
use App\Services\PartyLedgerService;
use App\Services\SettingsService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public static function catalog(): array
    {
        return [
            ['slug' => 'account-receivables', 'title' => 'A/c Receivables', 'color' => 'orange'],
            ['slug' => 'party-ledger', 'title' => 'Party Ledger', 'color' => 'teal'],
            ['slug' => 'cash-register', 'title' => 'Cash Register', 'color' => 'green'],
            ['slug' => 'journal-report', 'title' => 'Journal Report', 'color' => 'pink'],
        ];
    }

    public function index(): View
    {
        return view('reports.index', [
            'reports' => self::catalog(),
        ]);
    }

    public function show(string $report, Request $request): View
    {
        $item = collect(self::catalog())->firstWhere('slug', $report);

        abort_unless($item, 404);

        if ($report === 'account-receivables') {
            return $this->accountReceivablesReport($item);
        }

        abort(404);
    }

    public function partyLedgerAccounts(Request $request, PartyLedgerService $ledger): JsonResponse
    {
        $data = $request->validate([
            'account_type' => ['required', 'string', 'in:'.implode(',', array_keys(PartyLedgerService::ACCOUNT_TYPES))],
        ]);

        $accounts = $ledger->accountsForType($data['account_type'])
            ->map(fn ($row) => ['id' => $row->id, 'name' => $row->name]);

        return response()->json($accounts);
    }

    public function partyLedger(Request $request, PartyLedgerService $ledger): View
    {
        $data = $request->validate([
            'account_type' => ['required', 'string', 'in:'.implode(',', array_keys(PartyLedgerService::ACCOUNT_TYPES))],
            'account_id' => ['required', 'integer', 'min:1'],
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
        ]);

        $from = Carbon::parse($data['from_date'])->startOfDay();
        $to = Carbon::parse($data['to_date'])->endOfDay();

        $result = $ledger->ledger($data['account_type'], (int) $data['account_id'], $from, $to);

        return view('reports.party-ledger', [
            'companyName' => app(SettingsService::class)->companyName(),
            'printedAt' => now(),
            'partyName' => $ledger->partyName($data['account_type'], (int) $data['account_id']),
            'partyCode' => $ledger->partyCode($data['account_type'], (int) $data['account_id']),
            'accountType' => $data['account_type'],
            'accountTypeLabel' => PartyLedgerService::ACCOUNT_TYPES[$data['account_type']],
            'fromDate' => $from,
            'toDate' => $to,
            'entries' => $result['entries'],
            'openingBalance' => $result['openingBalance'],
            'closingBalance' => $result['closingBalance'],
            'totalDebit' => $result['totalDebit'],
            'totalCredit' => $result['totalCredit'],
            'backUrl' => route('reports.index'),
            'backLabel' => 'Back to Reports',
        ]);
    }

    public function journalReport(Request $request, JournalReportService $journalReport): View
    {
        $data = $request->validate([
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
        ]);

        $from = Carbon::parse($data['from_date'])->startOfDay();
        $to = Carbon::parse($data['to_date'])->endOfDay();

        $result = $journalReport->report($from, $to);

        return view('reports.journal-report', [
            'companyName' => app(SettingsService::class)->companyName(),
            'printedAt' => now(),
            'fromDate' => $from,
            'toDate' => $to,
            'vouchers' => $result['vouchers'],
            'grandTotalDebit' => $result['grandTotalDebit'],
            'grandTotalCredit' => $result['grandTotalCredit'],
            'voucherCount' => $result['voucherCount'],
            'lineCount' => $result['lineCount'],
        ]);
    }

    public function cashRegister(Request $request, CashRegisterService $register): View
    {
        $data = $request->validate([
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
        ]);

        $from = Carbon::parse($data['from_date'])->startOfDay();
        $to = Carbon::parse($data['to_date'])->endOfDay();

        $result = $register->register($from, $to);

        return view('reports.cash-register', [
            'companyName' => app(SettingsService::class)->companyName(),
            'printedAt' => now(),
            'fromDate' => $from,
            'toDate' => $to,
            'entries' => $result['entries'],
            'closingBalance' => $result['closingBalance'],
            'totalCashIn' => $result['totalCashIn'],
            'totalCashOut' => $result['totalCashOut'],
        ]);
    }

    private function accountReceivablesReport(array $item): View
    {
        $rows = collect();

        foreach (Customer::orderBy('name')->get() as $customer) {
            $balance = (float) $customer->current_balance;
            if (abs($balance) < 0.005) {
                continue;
            }

            $rows->push([
                'code' => 1000 + (int) $customer->id,
                'party_name' => $customer->name,
                'party_type' => 'Customer',
                'debit' => max($balance, 0),
                'credit' => $balance < 0 ? abs($balance) : 0,
            ]);
        }

        foreach (Supplier::orderBy('name')->get() as $supplier) {
            $balance = (float) $supplier->current_balance;
            if (abs($balance) < 0.005) {
                continue;
            }

            $rows->push([
                'code' => 2000 + (int) $supplier->id,
                'party_name' => $supplier->name,
                'party_type' => 'Supplier',
                'debit' => $balance < 0 ? abs($balance) : 0,
                'credit' => max($balance, 0),
            ]);
        }

        $rows = $rows->sortBy('code')->values();

        return view('reports.account-receivables', [
            'title' => $item['title'],
            'companyName' => app(SettingsService::class)->companyName(),
            'printedAt' => now(),
            'rows' => $rows,
            'totalDebit' => (float) $rows->sum('debit'),
            'totalCredit' => (float) $rows->sum('credit'),
        ]);
    }

}
