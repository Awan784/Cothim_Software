<?php

namespace App\Services;

use App\Models\SalesInvoice;
use App\Models\Salesman;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SalesmanLedgerService
{
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
    public function ledger(Salesman $salesman, Carbon $from, Carbon $to): array
    {
        $openingBalance = round((float) $salesman->opening_balance, 2);

        $all = $this->collectEntries($salesman)
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
                $broughtForward += (float) $entry['debit'] - (float) $entry['credit'];
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
            $entries->push([
                'date' => $from->copy(),
                'ref' => '—',
                'description' => 'Brought Forward',
                'notes' => '',
                'debit' => $broughtForward > 0 ? $broughtForward : 0.0,
                'credit' => $broughtForward < 0 ? abs($broughtForward) : 0.0,
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
            $running += $debit - $credit;

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

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function collectEntries(Salesman $salesman): Collection
    {
        $entries = collect();
        $opening = round((float) $salesman->opening_balance, 2);

        if (abs($opening) > 0.005) {
            $openingDate = $salesman->created_at
                ? Carbon::parse($salesman->created_at)->startOfDay()
                : Carbon::parse('2000-01-01')->startOfDay();

            $entries->push([
                'date' => $openingDate,
                'sort_group' => 0,
                'sort_date' => $openingDate->format('Y-m-d'),
                'sort_seq' => 0,
                'sort_key' => '0-opening',
                'ref' => 'OB',
                'description' => 'Opening Balance',
                'notes' => '',
                'debit' => $opening > 0 ? $opening : 0.0,
                'credit' => $opening < 0 ? abs($opening) : 0.0,
                'tone' => null,
                'source' => 'opening',
                'is_opening' => true,
            ]);
        }

        $invoices = SalesInvoice::query()
            ->with(['customer', 'lines', 'salesOrder'])
            ->where('status', '!=', 'draft')
            ->where(function ($query) use ($salesman) {
                $query->where('salesman_id', $salesman->id)
                    ->orWhereHas('salesOrder', fn ($order) => $order->where('salesman_id', $salesman->id));
            })
            ->orderBy('invoice_date')
            ->orderBy('id')
            ->get();

        foreach ($invoices as $invoice) {
            $date = $this->ledgerDate(
                $invoice->invoice_date,
                $invoice->created_at,
                $invoice->salesOrder?->order_date,
                $invoice->salesOrder?->created_at,
            );
            $ref = $invoice->invoice_no ?: 'INV-'.$invoice->id;
            $customer = $invoice->customer?->displayName() ?: 'Customer';
            $seq = (int) ($invoice->created_at?->timestamp ?? $invoice->id);
            $sortDate = $date->format('Y-m-d');
            $total = round((float) $invoice->total, 2);
            $paid = round((float) $invoice->amount_paid, 2);
            $due = $invoice->balanceDue();
            $commission = round((float) $invoice->salesman_commission_amount, 2);
            $commissionDue = $total > 0.009 ? round($commission * ($due / $total), 2) : 0.0;
            $lineNotes = $this->saleLineNotes($invoice->lines);

            $entries->push([
                'date' => $date,
                'sort_group' => 1,
                'sort_date' => $sortDate,
                'sort_seq' => $seq,
                'sort_key' => 'si-'.$invoice->id,
                'ref' => $ref,
                'description' => 'Sales invoice · '.$customer,
                'notes' => $lineNotes,
                'debit' => $total,
                'credit' => 0.0,
                'tone' => null,
                'source' => 'sale',
            ]);

            if ($commissionDue > 0.009) {
                $entries->push([
                    'date' => $date,
                    'sort_group' => 1,
                    'sort_date' => $sortDate,
                    'sort_seq' => $seq + 1,
                    'sort_key' => 'si-comm-'.$invoice->id,
                    'ref' => $ref,
                    'description' => 'Commission',
                    'notes' => '',
                    'debit' => 0.0,
                    'credit' => $commissionDue,
                    'tone' => 'receive',
                    'source' => 'commission',
                ]);
            }

            if ($paid > 0.009) {
                $entries->push([
                    'date' => $date,
                    'sort_group' => 1,
                    'sort_date' => $sortDate,
                    'sort_seq' => $seq + 2,
                    'sort_key' => 'si-paid-'.$invoice->id,
                    'ref' => $ref,
                    'description' => 'Receipt / Settlement',
                    'notes' => '',
                    'debit' => 0.0,
                    'credit' => $paid,
                    'tone' => 'receive',
                    'source' => 'cash',
                ]);
            }
        }

        return $entries;
    }

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
}
