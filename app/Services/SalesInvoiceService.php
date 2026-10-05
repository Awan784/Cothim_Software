<?php

namespace App\Services;

use App\Models\CashVoucher;
use App\Models\Customer;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceLine;
use App\Models\Salesman;
use App\Models\SalesmanSettlementAllocation;
use App\Models\SalesOrder;
use App\Models\StockItem;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class SalesInvoiceService
{
    public function __construct(
        private VatCalculator $vat,
        private SettingsService $settings,
        private ZatcaQrService $zatca,
        private CashVoucherService $vouchers,
        private InventoryService $inventory,
        private CommissionService $commission,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  list<array<string, mixed>>  $lines
     */
    public function saveDraft(?SalesInvoice $invoice, array $data, array $lines, User $user): SalesInvoice
    {
        $computed = $this->vat->document($lines, $this->settings->vatRate());
        if ($computed['lines'] === []) {
            throw new InvalidArgumentException('Add at least one invoice line.');
        }

        return DB::transaction(function () use ($invoice, $data, $computed, $user) {
            $payload = [
                'customer_id' => $data['customer_id'],
                'invoice_date' => $data['invoice_date'],
                'due_date' => $data['due_date'] ?? null,
                'type' => $data['type'] ?? 'simplified',
                'notes' => $data['notes'] ?? null,
                'mode' => array_key_exists('mode', $data)
                    ? (trim((string) $data['mode']) === '' ? null : trim((string) $data['mode']))
                    : ($invoice?->mode),
                'subtotal' => $computed['subtotal'],
                'discount_amount' => $computed['discount_amount'],
                'vat_amount' => $computed['vat_amount'],
                'total' => $computed['total'],
            ];

            foreach ([
                'salesman_id',
                'sales_order_id',
                'company_retain_percent',
                'salesman_commission_percent',
                'company_retain_amount',
                'salesman_commission_amount',
                'builty_postal',
                'builty_exp',
            ] as $field) {
                if (array_key_exists($field, $data)) {
                    $payload[$field] = $data[$field];
                }
            }

            $payload = array_merge($payload, $this->salesmanSnapshot($data, (float) $computed['total']));
            $issued = false;

            if ($invoice) {
                $invoice = SalesInvoice::query()->lockForUpdate()->findOrFail($invoice->id);
                $issued = ! $invoice->isDraft();

                if ($issued) {
                    if (SalesmanSettlementAllocation::query()->where('sales_invoice_id', $invoice->id)->exists()) {
                        throw new InvalidArgumentException('This invoice is in a salesman settlement. Delete the settlement first.');
                    }
                    if ((float) $invoice->amount_paid > (float) $computed['total'] + 0.009) {
                        throw new InvalidArgumentException(
                            'Invoice total cannot be less than amount already paid ('.number_format((float) $invoice->amount_paid, 2).').'
                        );
                    }
                    if ((float) $invoice->amount_paid > 0.009 && (int) $data['customer_id'] !== (int) $invoice->customer_id) {
                        throw new InvalidArgumentException('Remove invoice payments before changing the customer.');
                    }

                    $this->inventory->revertSalesInvoice($invoice);
                    Customer::whereKey($invoice->customer_id)->decrement('current_balance', (float) $invoice->total);
                }

                $invoice->update($payload);
                $invoice->lines()->delete();
            } else {
                $invoice = SalesInvoice::create($payload + [
                    'uuid' => (string) Str::uuid(),
                    'status' => 'draft',
                    'zatca_status' => 'none',
                    'created_by' => $user->id,
                    'amount_paid' => 0,
                ]);
            }

            foreach ($computed['lines'] as $line) {
                SalesInvoiceLine::create([
                    'sales_invoice_id' => $invoice->id,
                    'stock_item_id' => $line['stock_item_id'] ?? null,
                    'stock_item_lot_id' => $line['stock_item_lot_id'] ?? null,
                    'batch_no' => $line['batch_no'] ?? null,
                    'description' => $line['description'] ?? 'Item',
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'discount_rate' => $line['discount_rate'],
                    'discount_amount' => $line['discount_amount'],
                    'vat_rate' => $line['vat_rate'],
                    'line_net' => $line['line_net'],
                    'vat_amount' => $line['vat_amount'],
                    'line_total' => $line['line_total'],
                    'sort_order' => $line['sort_order'],
                ]);
            }

            $invoice = $invoice->fresh(['lines', 'customer']);

            if ($issued) {
                Customer::whereKey($invoice->customer_id)->increment('current_balance', (float) $invoice->total);
                $this->issueStockLines($invoice);
                $invoice->zatca_qr_payload = $this->zatca->payload($invoice, $this->settings);
                $invoice->status = ((float) $invoice->total - (float) $invoice->amount_paid) <= 0.009
                    ? 'paid'
                    : 'issued';
                $invoice->save();
            }

            return $invoice->fresh(['lines', 'customer']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<array<string, mixed>>  $lines
     */
    public function generate(array $data, array $lines, User $user): SalesInvoice
    {
        return DB::transaction(function () use ($data, $lines, $user) {
            $invoice = $this->saveDraft(null, $data, $lines, $user);

            return $this->issue($invoice);
        });
    }

    public function issue(SalesInvoice $invoice): SalesInvoice
    {
        if (! $invoice->isDraft()) {
            throw new InvalidArgumentException('This invoice is already issued.');
        }
        if ($invoice->lines()->count() === 0) {
            throw new InvalidArgumentException('Add lines before issuing.');
        }

        return DB::transaction(function () use ($invoice) {
            $invoice->status = 'issued';
            $invoice->issued_at = now();
            $invoice->zatca_uuid = $invoice->uuid;
            $invoice->zatca_qr_payload = $this->zatca->payload($invoice, $this->settings);
            $invoice->zatca_status = filled($this->settings->get('company_vat_number'))
                ? 'phase1'
                : 'phase1';
            $this->assignInvoiceNo($invoice);

            Customer::whereKey($invoice->customer_id)->increment('current_balance', (float) $invoice->total);

            $invoice->load('lines');
            $this->issueStockLines($invoice);

            return $invoice->fresh(['lines', 'customer']);
        });
    }

    public function recordPayment(SalesInvoice $invoice, float $amount, string $method = 'cash', ?int $bankAccountId = null): SalesInvoice
    {
        if ($invoice->isDraft()) {
            throw new InvalidArgumentException('Issue the invoice before recording a payment.');
        }

        $due = $invoice->balanceDue();
        if ($amount <= 0 || $amount > $due + 0.009) {
            throw new InvalidArgumentException('Payment must be greater than 0 and not more than '.number_format($due, 2).'.');
        }

        return DB::transaction(function () use ($invoice, $amount, $method, $bankAccountId) {
            $this->vouchers->create([
                'type' => 'receive',
                'payment_method' => $method === 'bank' ? 'bank' : 'cash',
                'bank_account_id' => $bankAccountId,
                'account_type' => 'customer',
                'account_id' => $invoice->customer_id,
                'amount' => $amount,
                'voucher_date' => now()->toDateString(),
                'reference' => $invoice->invoice_no,
                'notes' => 'Payment for invoice '.$invoice->invoice_no,
                'sales_invoice_id' => $invoice->id,
                'affects_cash' => true,
            ]);

            $invoice->amount_paid = round((float) $invoice->amount_paid + $amount, 2);
            $invoice->status = $invoice->balanceDue() <= 0.009 ? 'paid' : 'issued';
            $invoice->save();

            return $invoice->fresh(['customer']);
        });
    }

    public function softDelete(SalesInvoice $invoice, bool $cascadeOrder = true): void
    {
        if ($invoice->trashed()) {
            return;
        }

        if (SalesmanSettlementAllocation::query()->where('sales_invoice_id', $invoice->id)->exists()) {
            throw new InvalidArgumentException('This invoice is in a salesman settlement. Delete the settlement first.');
        }

        DB::transaction(function () use ($invoice, $cascadeOrder) {
            $linkedOrder = SalesOrder::linkedToInvoice($invoice);

            if (! $invoice->isDraft()) {
                $this->inventory->revertSalesInvoice($invoice);

                foreach (CashVoucher::query()->where('sales_invoice_id', $invoice->id)->orderBy('id')->get() as $voucher) {
                    if ($voucher->isSettlementLinked()) {
                        throw new InvalidArgumentException('This invoice has settlement cash entries. Delete the settlement first.');
                    }
                    $this->vouchers->delete($voucher);
                }

                Customer::whereKey($invoice->customer_id)->decrement('current_balance', (float) $invoice->total);
            }

            $invoice->delete();

            if ($cascadeOrder && $linkedOrder && ! $linkedOrder->trashed()) {
                $linkedOrder->delete();
            }
        });
    }

    private function assignInvoiceNo(SalesInvoice $invoice): void
    {
        if (filled($invoice->invoice_no)) {
            $invoice->save();

            return;
        }

        for ($attempt = 0; $attempt < 8; $attempt++) {
            $invoice->invoice_no = $this->nextNumber();

            try {
                $invoice->save();

                return;
            } catch (QueryException $e) {
                if (! $this->isInvoiceNoCollision($e) || $attempt === 7) {
                    throw $e;
                }

                $invoice->invoice_no = null;
            }
        }
    }

    private function nextNumber(): string
    {
        $max = $this->settings->invoiceSeries();

        $numbers = SalesInvoice::query()
            ->withTrashed()
            ->whereNotNull('invoice_no')
            ->lockForUpdate()
            ->pluck('invoice_no');

        foreach ($numbers as $invoiceNo) {
            $invoiceNo = trim((string) $invoiceNo);
            if ($invoiceNo !== '' && preg_match('/^\d+$/', $invoiceNo) && (int) $invoiceNo > $max) {
                $max = (int) $invoiceNo;
            }
        }

        $next = $max + 1;
        while (SalesInvoice::query()->withTrashed()->where('invoice_no', (string) $next)->exists()) {
            $next++;
        }

        return (string) $next;
    }

    private function isInvoiceNoCollision(QueryException $e): bool
    {
        if ((string) $e->getCode() !== '23000') {
            return false;
        }

        return str_contains($e->getMessage(), 'invoice_no')
            || str_contains($e->getMessage(), 'sales_invoices_organization_id_invoice_no_unique');
    }

    private function issueStockLines(SalesInvoice $invoice): void
    {
        $invoice->loadMissing('lines');

        foreach ($invoice->lines as $line) {
            $stockItemId = $line->stock_item_id;
            if (! $stockItemId && $line->description) {
                $stockItemId = StockItem::query()->where('name', $line->description)->value('id');
            }
            if (! $stockItemId) {
                continue;
            }

            $this->inventory->issue((int) $stockItemId, (float) $line->quantity, [
                'unit_cost' => $line->unit_price,
                'moved_at' => $invoice->invoice_date,
                'reference' => $invoice->invoice_no,
                'notes' => $line->description,
                'source_type' => 'sales_invoice',
                'source_id' => $invoice->id,
                'stock_item_lot_id' => $line->stock_item_lot_id,
                'batch_no' => $line->batch_no,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function salesmanSnapshot(array $data, float $total): array
    {
        if (! array_key_exists('salesman_id', $data)) {
            return [];
        }

        $salesmanId = $data['salesman_id'];
        if (! filled($salesmanId)) {
            return [
                'salesman_id' => null,
                'company_retain_percent' => 0,
                'salesman_commission_percent' => 0,
                'company_retain_amount' => 0,
                'salesman_commission_amount' => 0,
            ];
        }

        $salesman = Salesman::query()->find($salesmanId);
        if (! $salesman) {
            throw new InvalidArgumentException('Salesman not found.');
        }

        return $this->commission->snapshot($total, $salesman) + [
            'salesman_id' => $salesman->id,
        ];
    }
}
