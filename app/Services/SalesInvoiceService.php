<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceLine;
use App\Models\StockItem;
use App\Models\User;
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
                'subtotal' => $computed['subtotal'],
                'vat_amount' => $computed['vat_amount'],
                'total' => $computed['total'],
            ];

            if ($invoice) {
                if (! $invoice->isDraft()) {
                    throw new InvalidArgumentException('Issued invoices cannot be edited. Create a credit note instead.');
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
                    'description' => $line['description'] ?? 'Item',
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'vat_rate' => $line['vat_rate'],
                    'line_net' => $line['line_net'],
                    'vat_amount' => $line['vat_amount'],
                    'line_total' => $line['line_total'],
                    'sort_order' => $line['sort_order'],
                ]);
            }

            return $invoice->fresh(['lines', 'customer']);
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
            $invoice->invoice_no = $invoice->invoice_no ?: $this->nextNumber();
            $invoice->status = 'issued';
            $invoice->issued_at = now();
            $invoice->zatca_uuid = $invoice->uuid;
            $invoice->zatca_qr_payload = $this->zatca->payload($invoice, $this->settings);
            $invoice->zatca_status = filled($this->settings->get('company_vat_number'))
                ? 'phase1'
                : 'phase1';
            $invoice->save();

            Customer::whereKey($invoice->customer_id)->increment('current_balance', (float) $invoice->total);

            $invoice->load('lines');

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
                ]);
            }

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
            ]);

            $invoice->amount_paid = round((float) $invoice->amount_paid + $amount, 2);
            $invoice->status = $invoice->balanceDue() <= 0.009 ? 'paid' : 'issued';
            $invoice->save();

            return $invoice->fresh(['customer']);
        });
    }

    private function nextNumber(): string
    {
        $year = now()->format('Y');
        $count = SalesInvoice::query()
            ->whereYear('created_at', $year)
            ->whereNotNull('invoice_no')
            ->count() + 1;

        return 'INV-'.$year.'-'.str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }
}
