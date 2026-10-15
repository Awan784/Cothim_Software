<?php

namespace App\Services;

use App\Models\CashVoucher;
use App\Models\SalesInvoice;
use App\Models\Salesman;
use App\Models\SalesmanSettlement;
use App\Models\SalesmanSettlementAllocation;
use App\Models\SalesOrder;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class SalesmanSettlementService
{
    public function __construct(private CashVoucherService $vouchers) {}

    /**
     * @return Collection<int, SalesInvoice>
     */
    public function unpaidInvoices(Salesman $salesman): Collection
    {
        return SalesInvoice::query()
            ->with('customer')
            ->where('status', '!=', 'draft')
            ->whereRaw('(total - amount_paid) > 0.009')
            ->where(function ($query) use ($salesman) {
                $query->where('salesman_id', $salesman->id)
                    ->orWhereHas('salesOrder', fn ($order) => $order->where('salesman_id', $salesman->id));
            })
            ->orderBy('invoice_date')
            ->orderBy('id')
            ->get();
    }

    public function currentAdvance(Salesman $salesman): float
    {
        return round((float) $salesman->advance_balance, 2);
    }

    /**
     * Same net as the commission report: opening balance, sales, commission, and payments.
     */
    public function netReceivable(Salesman $salesman, ?Carbon $from = null, ?Carbon $to = null): float
    {
        $from = ($from ?? Carbon::parse('2000-01-01'))->copy()->startOfDay();
        $to = ($to ?? now())->copy()->endOfDay();
        $fromStr = $from->toDateString();
        $toStr = $to->toDateString();

        $invoiceQuery = SalesInvoice::query()
            ->with('salesOrder')
            ->where('status', '!=', 'draft')
            ->where(function ($query) use ($salesman) {
                $query->where('salesman_id', $salesman->id)
                    ->orWhereHas('salesOrder', fn ($order) => $order->where('salesman_id', $salesman->id));
            })
            ->where(function ($query) use ($from, $to, $fromStr, $toStr) {
                $query->whereBetween('invoice_date', [$fromStr, $toStr])
                    ->orWhereBetween('created_at', [$from, $to])
                    ->orWhereHas('salesOrder', function ($order) use ($from, $to, $fromStr, $toStr) {
                        $order->whereBetween('order_date', [$fromStr, $toStr])
                            ->orWhereBetween('created_at', [$from, $to]);
                    });
            });

        $sales = round((float) (clone $invoiceQuery)->sum('total'), 2);
        $commission = round((float) (clone $invoiceQuery)->sum('salesman_commission_amount'), 2);

        $openOrders = SalesOrder::query()
            ->where('salesman_id', $salesman->id)
            ->whereNull('sales_invoice_id')
            ->where('status', '!=', SalesOrder::STATUS_REJECTED)
            ->where(function ($query) use ($from, $to, $fromStr, $toStr) {
                $query->whereBetween('order_date', [$fromStr, $toStr])
                    ->orWhereBetween('created_at', [$from, $to]);
            })
            ->get(['total', 'salesman_commission_amount', 'builty_exp']);

        $sales = round($sales + (float) $openOrders->sum('total'), 2);
        $commission = round($commission + (float) $openOrders->sum('salesman_commission_amount'), 2);

        $builty = round((float) (clone $invoiceQuery)->sum('builty_exp') + (float) $openOrders->sum('builty_exp'), 2);
        $paid = round((float) SalesmanSettlement::query()
            ->where('salesman_id', $salesman->id)
            ->whereBetween('settlement_date', [$fromStr, $toStr])
            ->sum('cash_received'), 2);
        $applied = (float) SalesmanSettlement::query()
            ->where('salesman_id', $salesman->id)
            ->where('settlement_date', '>=', $fromStr)
            ->sum('applied_to_opening');
        $opening = round((float) $salesman->opening_balance + $applied, 2);

        return round($opening + $sales - $commission - $builty - $paid, 2);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<array{invoice_id: int, amount: float}>  $allocations
     */
    public function create(array $data, array $allocations, ?int $userId = null): SalesmanSettlement
    {
        $cashReceived = round((float) ($data['cash_received'] ?? 0), 2);
        $method = ($data['payment_method'] ?? 'cash') === 'bank' ? 'bank' : 'cash';
        $bankAccountId = $method === 'bank' ? ($data['bank_account_id'] ?? null) : null;

        if ($cashReceived < -0.009) {
            throw new InvalidArgumentException('Cash received cannot be negative.');
        }
        if ($method === 'bank' && $cashReceived > 0.009 && empty($bankAccountId)) {
            throw new InvalidArgumentException('Please select a bank account.');
        }
        if ($cashReceived <= 0.009) {
            $method = 'cash';
            $bankAccountId = null;
        }

        $normalized = [];
        foreach ($allocations as $row) {
            $invoiceId = (int) ($row['invoice_id'] ?? 0);
            $amount = round((float) ($row['amount'] ?? 0), 2);
            if ($invoiceId < 1 || $amount <= 0.009) {
                continue;
            }
            if (isset($normalized[$invoiceId])) {
                throw new InvalidArgumentException('Each invoice can only be allocated once.');
            }
            $normalized[$invoiceId] = $amount;
        }

        $allocatedTotal = round(array_sum($normalized), 2);
        if ($cashReceived <= 0.009 && $allocatedTotal <= 0.009) {
            throw new InvalidArgumentException('Enter cash received from the salesman or allocate at least one customer invoice.');
        }

        return DB::transaction(function () use ($data, $normalized, $cashReceived, $allocatedTotal, $method, $bankAccountId, $userId) {
            $salesman = Salesman::query()->whereKey($data['salesman_id'])->lockForUpdate()->firstOrFail();
            if ($salesman->organization_id) {
                app()->instance('current_organization_id', (int) $salesman->organization_id);
            }
            $opening = round((float) $salesman->advance_balance, 2);
            $openingReceivable = round(max(0, (float) $salesman->opening_balance), 2);
            $available = round($opening + $cashReceived, 2);

            if ($allocatedTotal - $available > 0.009) {
                throw new InvalidArgumentException(
                    'Allocated '.number_format($allocatedTotal, 2).' is more than available '
                    .number_format($available, 2).' (opening advance + cash received).'
                );
            }

            $leftover = round($available - $allocatedTotal, 2);
            $appliedToOpening = round(min($leftover, $openingReceivable), 2);
            $closing = round($leftover - $appliedToOpening, 2);
            $date = $data['settlement_date'] ?? now()->toDateString();

            $settlement = SalesmanSettlement::create([
                'organization_id' => $salesman->organization_id,
                'salesman_id' => $salesman->id,
                'settlement_no' => SalesmanSettlement::nextNumber(),
                'settlement_date' => $date,
                'cash_received' => $cashReceived,
                'allocated_amount' => $allocatedTotal,
                'opening_advance' => $opening,
                'closing_advance' => $closing,
                'opening_receivable' => $openingReceivable,
                'applied_to_opening' => $appliedToOpening,
                'payment_method' => $method,
                'bank_account_id' => $bankAccountId,
                'created_by' => $userId,
                'notes' => $data['notes'] ?? null,
            ]);

            $cashVoucher = null;
            if ($cashReceived > 0.009) {
                $cashVoucher = $this->vouchers->create([
                    'type' => 'receive',
                    'payment_method' => $method,
                    'bank_account_id' => $bankAccountId,
                    'account_type' => 'salesman',
                    'account_id' => $salesman->id,
                    'amount' => $cashReceived,
                    'voucher_date' => $date,
                    'reference' => $settlement->settlement_no,
                    'notes' => 'Cash received from salesman '.$salesman->name
                        .' · allocated '.number_format($allocatedTotal, 2)
                        .' · applied to opening balance '.number_format($appliedToOpening, 2)
                        .' · advance '.number_format($closing, 2),
                    'affects_cash' => true,
                    'salesman_settlement_id' => $settlement->id,
                ]);
                $settlement->update(['cash_voucher_id' => $cashVoucher->id]);
            }

            foreach ($normalized as $invoiceId => $amount) {
                $invoice = SalesInvoice::query()->whereKey($invoiceId)->lockForUpdate()->firstOrFail();
                $this->assertInvoiceBelongsToSalesman($invoice, $salesman);
                $due = $invoice->balanceDue();
                if ($amount - $due > 0.009) {
                    throw new InvalidArgumentException(
                        'Allocation for '.($invoice->invoice_no ?: 'invoice #'.$invoice->id)
                        .' cannot exceed due '.number_format($due, 2).'.'
                    );
                }

                $invoice->amount_paid = round((float) $invoice->amount_paid + $amount, 2);
                $invoice->status = $invoice->balanceDue() <= 0.009 ? 'paid' : 'issued';
                $invoice->save();

                $allocationVoucher = $this->vouchers->create([
                    'type' => 'receive',
                    'payment_method' => $method,
                    'bank_account_id' => $method === 'bank' ? $bankAccountId : null,
                    'account_type' => 'customer',
                    'account_id' => $invoice->customer_id,
                    'amount' => $amount,
                    'voucher_date' => $date,
                    'reference' => $invoice->invoice_no,
                    'notes' => 'Receive payment via salesman settlement '.$settlement->settlement_no
                        .' ('.$salesman->name.')',
                    'affects_cash' => false,
                    'salesman_settlement_id' => $settlement->id,
                    'sales_invoice_id' => $invoice->id,
                ]);

                SalesmanSettlementAllocation::create([
                    'organization_id' => $salesman->organization_id,
                    'salesman_settlement_id' => $settlement->id,
                    'sales_invoice_id' => $invoice->id,
                    'customer_id' => $invoice->customer_id,
                    'cash_voucher_id' => $allocationVoucher->id,
                    'amount' => $amount,
                ]);
            }

            $salesman->opening_balance = round($openingReceivable - $appliedToOpening, 2);
            $salesman->advance_balance = $closing;
            $salesman->save();

            return $settlement->fresh(['salesman', 'allocations.invoice', 'allocations.customer', 'allocations.cashVoucher', 'cashVoucher']);
        });
    }

    public function delete(SalesmanSettlement $settlement): void
    {
        DB::transaction(function () use ($settlement) {
            $settlement = SalesmanSettlement::query()->whereKey($settlement->id)->lockForUpdate()->firstOrFail();
            $salesman = Salesman::query()->whereKey($settlement->salesman_id)->lockForUpdate()->firstOrFail();

            $later = SalesmanSettlement::query()
                ->where('salesman_id', $salesman->id)
                ->where('id', '>', $settlement->id)
                ->exists();
            if ($later) {
                throw new RuntimeException('Delete newer settlements for this salesman first.');
            }

            foreach ($settlement->allocations()->get() as $allocation) {
                $invoice = $allocation->invoice;
                if ($invoice) {
                    $invoice = SalesInvoice::query()->whereKey($invoice->id)->lockForUpdate()->first();
                    if ($invoice) {
                        $invoice->amount_paid = round(max(0, (float) $invoice->amount_paid - (float) $allocation->amount), 2);
                        $invoice->status = $invoice->isDraft()
                            ? 'draft'
                            : ($invoice->balanceDue() <= 0.009 ? 'paid' : 'issued');
                        $invoice->save();
                    }
                }

                if ($allocation->cash_voucher_id) {
                    $voucher = CashVoucher::query()->whereKey($allocation->cash_voucher_id)->first();
                    if ($voucher) {
                        $this->vouchers->delete($voucher);
                    }
                }

                $allocation->delete();
            }

            if ($settlement->cash_voucher_id) {
                $cashVoucher = CashVoucher::query()->whereKey($settlement->cash_voucher_id)->first();
                $settlement->update(['cash_voucher_id' => null]);
                if ($cashVoucher) {
                    $this->vouchers->delete($cashVoucher);
                }
            }

            $salesman->opening_balance = round((float) $salesman->opening_balance + (float) $settlement->applied_to_opening, 2);
            $salesman->advance_balance = round((float) $settlement->opening_advance, 2);
            $salesman->save();

            $settlement->delete();
        });
    }

    private function assertInvoiceBelongsToSalesman(SalesInvoice $invoice, Salesman $salesman): void
    {
        if ((int) $invoice->salesman_id === (int) $salesman->id) {
            return;
        }

        $orderSalesmanId = $invoice->salesOrder()->value('salesman_id');
        if ((int) $orderSalesmanId === (int) $salesman->id) {
            return;
        }

        throw new InvalidArgumentException(
            'Invoice '.($invoice->invoice_no ?: '#'.$invoice->id).' does not belong to this salesman.'
        );
    }
}
