<?php

namespace App\Services;

use App\Models\CashVoucher;
use App\Models\Customer;
use App\Models\ExpenseAccount;
use App\Models\JournalVoucherLine;
use App\Models\PurchaseOrder;
use App\Models\PurchaseReturn;
use App\Models\SalesInvoice;
use App\Models\SalesOrder;
use App\Models\SalesReturn;
use App\Models\Supplier;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class PartyLedgerService
{
    public const ACCOUNT_TYPES = [
        'customer' => 'Customer',
        'supplier' => 'Supplier',
        'vendor' => 'Vendor',
        'expense' => 'Expense Account',
    ];

    public function accountsForType(string $accountType): Collection
    {
        $this->assertAccountType($accountType);

        $query = match ($accountType) {
            'customer' => Customer::query(),
            'supplier' => Supplier::query(),
            'vendor' => Vendor::query(),
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
            'vendor' => 3000,
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
     *     openingBalance: float,
     *     totalDebit: float,
     *     totalCredit: float
     * }
     */
    public function ledger(string $accountType, int $accountId, Carbon $from, Carbon $to): array
    {
        $this->assertAccountType($accountType);
        $this->assertPartyExists($accountType, $accountId);

        $openingBalance = $this->openingBalance($accountType, $accountId);

        $all = $this->collectEntries($accountType, $accountId)
            ->sortBy([
                ['sort_group', 'asc'],
                ['sort_date', 'asc'],
                ['sort_seq', 'asc'],
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
                'is_opening' => ! empty($entry['is_opening']),
                'tone' => $entry['tone'] ?? null,
                'source' => $entry['source'] ?? null,
            ]);
        }

        return [
            'entries' => $entries,
            'broughtForward' => $broughtForward,
            'closingBalance' => $running,
            'openingBalance' => $openingBalance,
            'totalDebit' => $totalDebit,
            'totalCredit' => $totalCredit,
        ];
    }

    private function collectEntries(string $accountType, int $accountId): Collection
    {
        $entries = collect();
        $opening = $this->openingBalance($accountType, $accountId);

        if (abs($opening) > 0.005) {
            [$debit, $credit] = $this->splitBalanceForDisplay($accountType, $opening);
            $openingDate = $this->openingDate($accountType, $accountId);
            $entries->push([
                'date' => $openingDate,
                'sort_group' => 0,
                'sort_date' => $openingDate->format('Y-m-d'),
                'sort_seq' => 0,
                'sort_key' => '0-opening',
                'ref' => 'OB',
                'description' => 'Opening Balance',
                'notes' => '',
                'debit' => $debit,
                'credit' => $credit,
                'tone' => null,
                'source' => 'opening',
                'is_opening' => true,
            ]);
        }

        if ($accountType === 'customer') {
            foreach (SalesInvoice::with(['lines', 'salesOrder.lines'])
                ->where('customer_id', $accountId)
                ->where('status', '!=', 'draft')
                ->get() as $invoice) {
                $date = $this->ledgerDate(
                    $invoice->invoice_date,
                    $invoice->created_at,
                    $invoice->salesOrder?->order_date,
                    $invoice->salesOrder?->created_at,
                );
                $orderNo = $invoice->salesOrder?->order_no;
                $lineNotes = $this->saleLineNotes($invoice->lines->isNotEmpty() ? $invoice->lines : $invoice->salesOrder?->lines);
                $notes = collect([
                    $orderNo ? 'Order '.$orderNo : null,
                    $lineNotes,
                    filled($invoice->notes) ? (string) $invoice->notes : null,
                ])->filter()->implode(' · ');

                $entries->push([
                    'date' => $date,
                    'sort_group' => 1,
                    'sort_date' => $date->format('Y-m-d'),
                    'sort_seq' => (int) ($invoice->created_at?->timestamp ?? $invoice->id),
                    'sort_key' => 'si-'.$invoice->id,
                    'ref' => $invoice->invoice_no ?: 'INV-'.$invoice->id,
                    'description' => $orderNo ? 'Sales invoice · '.$orderNo : 'Sales invoice',
                    'notes' => $notes,
                    'debit' => (float) $invoice->total,
                    'credit' => 0.0,
                    'tone' => null,
                    'source' => 'sale',
                ]);
            }

            foreach (SalesOrder::with('lines')
                ->where('customer_id', $accountId)
                ->whereNull('sales_invoice_id')
                ->where('status', '!=', SalesOrder::STATUS_REJECTED)
                ->get() as $order) {
                $date = $this->ledgerDate($order->order_date, $order->created_at);
                $lineNotes = $this->saleLineNotes($order->lines);
                $notes = collect([
                    $order->statusLabel(),
                    $lineNotes,
                    filled($order->notes) ? (string) $order->notes : null,
                ])->filter()->implode(' · ');

                $entries->push([
                    'date' => $date,
                    'sort_group' => 1,
                    'sort_date' => $date->format('Y-m-d'),
                    'sort_seq' => (int) ($order->created_at?->timestamp ?? $order->id),
                    'sort_key' => 'so-'.$order->id,
                    'ref' => $order->order_no ?: 'SO-'.$order->id,
                    'description' => 'Sales order',
                    'notes' => $notes,
                    'debit' => (float) $order->total,
                    'credit' => 0.0,
                    'tone' => null,
                    'source' => 'sale',
                ]);
            }

            foreach (SalesReturn::where('customer_id', $accountId)->get() as $sr) {
                $date = $this->ledgerDate($sr->return_date, $sr->created_at);
                $entries->push([
                    'date' => $date,
                    'sort_group' => 1,
                    'sort_date' => $date->format('Y-m-d'),
                    'sort_seq' => (int) ($sr->created_at?->timestamp ?? $sr->id),
                    'sort_key' => 'sr-'.$sr->id,
                    'ref' => $sr->return_no,
                    'description' => 'Sales return',
                    'notes' => (string) ($sr->notes ?? ''),
                    'debit' => 0.0,
                    'credit' => (float) $sr->total_amount,
                    'tone' => null,
                    'source' => 'sales_return',
                ]);
            }
        }

        if ($accountType === 'supplier' || $accountType === 'vendor') {
            $purchaseQuery = $accountType === 'vendor'
                ? PurchaseOrder::where('vendor_id', $accountId)->where('party_type', 'vendor')
                : PurchaseOrder::where('supplier_id', $accountId)->where(function ($query) {
                    $query->where('party_type', 'supplier')->orWhereNull('party_type');
                });
            foreach ($purchaseQuery->get() as $po) {
                $date = $po->po_date instanceof Carbon ? $po->po_date : Carbon::parse($po->po_date);
                $entries->push([
                    'date' => $date,
                    'sort_group' => 1,
                    'sort_date' => $date->format('Y-m-d'),
                    'sort_seq' => (int) ($po->created_at?->timestamp ?? $po->id),
                    'sort_key' => 'po-'.$po->id,
                    'ref' => $po->po_no,
                    'description' => 'Purchase order',
                    'notes' => (string) ($po->notes ?? ''),
                    'debit' => 0.0,
                    'credit' => (float) $po->total_amount,
                    'tone' => null,
                    'source' => 'purchase',
                ]);
            }

            if ($accountType === 'supplier') {
            foreach (PurchaseReturn::where('supplier_id', $accountId)->get() as $pr) {
                $date = $pr->return_date instanceof Carbon ? $pr->return_date : Carbon::parse($pr->return_date);
                $entries->push([
                    'date' => $date,
                    'sort_group' => 1,
                    'sort_date' => $date->format('Y-m-d'),
                    'sort_seq' => (int) ($pr->created_at?->timestamp ?? $pr->id),
                    'sort_key' => 'pr-'.$pr->id,
                    'ref' => $pr->return_no,
                    'description' => 'Purchase return',
                    'notes' => (string) ($pr->notes ?? ''),
                    'debit' => (float) $pr->total_amount,
                    'credit' => 0.0,
                    'tone' => null,
                    'source' => 'purchase_return',
                ]);
            }
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
                'sort_group' => 1,
                'sort_date' => ($voucher->voucher_date instanceof Carbon ? $voucher->voucher_date : Carbon::parse($voucher->voucher_date))->format('Y-m-d'),
                'sort_seq' => (int) ($voucher->created_at?->timestamp ?? $voucher->id),
                'sort_key' => 'jv-'.$line->id,
                'ref' => $voucher->voucher_no,
                'description' => $line->line_note ?: 'Journal voucher',
                'notes' => (string) ($voucher->notes ?? ''),
                'debit' => (float) $line->debit,
                'credit' => (float) $line->credit,
                'tone' => (float) $line->debit > 0 ? 'payment' : ((float) $line->credit > 0 ? 'receive' : null),
                'source' => 'journal',
            ]);
        }

        return $entries;
    }

    /**
     * Prefer the document date unless it is clearly a mis-parsed year; then use created_at / order dates.
     */
    private function ledgerDate(mixed $documentDate, mixed $createdAt, mixed $orderDate = null, mixed $orderCreatedAt = null): Carbon
    {
        $document = $this->asDate($documentDate);
        $created = $this->asDate($createdAt);
        $order = $this->asDate($orderDate);
        $orderCreated = $this->asDate($orderCreatedAt);

        if ($document && $created && abs((int) $document->year - (int) $created->year) > 1) {
            $document = null;
        }
        if ($order && $orderCreated && abs((int) $order->year - (int) $orderCreated->year) > 1) {
            $order = null;
        }

        $chosen = $document ?? $created ?? $order ?? $orderCreated ?? now();

        return $chosen->copy()->startOfDay();
    }

    private function asDate(mixed $value): ?Carbon
    {
        if ($value instanceof Carbon) {
            return $value->copy();
        }
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function saleLineNotes(?iterable $lines): string
    {
        if ($lines === null) {
            return '';
        }

        return collect($lines)->map(function ($line) {
            $name = trim((string) ($line->description ?? ''));
            if ($name === '') {
                return null;
            }
            $qty = (float) ($line->quantity ?? 0);
            $qtyLabel = abs($qty - round($qty)) < 0.0005
                ? (string) (int) round($qty)
                : rtrim(rtrim(number_format($qty, 3, '.', ''), '0'), '.');

            return $qtyLabel.' × '.$name;
        })->filter()->implode(', ');
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
        } elseif ($accountType === 'supplier' || $accountType === 'vendor') {
            $debit = $voucher->type === 'payment' ? $amount : 0.0;
            $credit = $voucher->type === 'receive' ? $amount : 0.0;
        } elseif ($accountType === 'expense') {
            $debit = $voucher->type === 'payment' ? $amount : 0.0;
            $credit = $voucher->type === 'receive' ? $amount : 0.0;
        }

        $paymentLabel = ucfirst($voucher->payment_method ?? 'cash');

        $date = $voucher->voucher_date instanceof Carbon ? $voucher->voucher_date : Carbon::parse($voucher->voucher_date);

        return [
            'date' => $date,
            'sort_group' => 1,
            'sort_date' => $date->format('Y-m-d'),
            'sort_seq' => (int) ($voucher->created_at?->timestamp ?? $voucher->id),
            'sort_key' => 'cv-'.$voucher->id,
            'ref' => $voucher->voucher_no,
            'description' => 'Cash voucher ('.strtoupper($voucher->type).', '.$paymentLabel.')',
            'notes' => (string) ($voucher->notes ?? ''),
            'debit' => $debit,
            'credit' => $credit,
            'tone' => $voucher->type,
            'source' => 'cash',
        ];
    }

    private function balanceDelta(string $accountType, float $debit, float $credit): float
    {
        return match ($accountType) {
            'customer', 'expense' => $debit - $credit,
            'supplier', 'vendor' => $credit - $debit,
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

    private function openingBalance(string $accountType, int $accountId): float
    {
        return match ($accountType) {
            'customer' => (float) (Customer::whereKey($accountId)->value('opening_balance') ?? 0),
            'supplier' => (float) (Supplier::whereKey($accountId)->value('opening_balance') ?? 0),
            'vendor' => (float) (Vendor::whereKey($accountId)->value('opening_balance') ?? 0),
            default => 0.0,
        };
    }

    private function openingDate(string $accountType, int $accountId): Carbon
    {
        $createdAt = match ($accountType) {
            'customer' => Customer::whereKey($accountId)->value('created_at'),
            'supplier' => Supplier::whereKey($accountId)->value('created_at'),
            'vendor' => Vendor::whereKey($accountId)->value('created_at'),
            default => null,
        };

        if ($createdAt) {
            return Carbon::parse($createdAt)->startOfDay();
        }

        return Carbon::parse('2000-01-01')->startOfDay();
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
            'vendor' => Vendor::whereKey($accountId)->exists(),
            'expense' => ExpenseAccount::whereKey($accountId)->exists(),
            default => false,
        };

        if (! $exists) {
            throw new InvalidArgumentException('Account not found.');
        }
    }
}
