<?php

namespace App\Services;

use App\Models\CashVoucher;
use App\Models\Customer;
use App\Models\SalesInvoice;
use App\Models\Salesman;
use App\Models\SmsLog;
use App\Models\Supplier;
use App\Models\Vendor;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class SmsNotifier
{
    public function __construct(
        private readonly JazzSmsService $sms,
        private readonly SettingsService $settings,
    ) {}

    /**
     * @param  array{customer?: bool, salesman?: bool}|null  $options
     */
    public function invoiceIssued(SalesInvoice $invoice, ?array $options = null): void
    {
        if (! $this->sms->enabled() || ! $this->flag('SMS_ON_INVOICE_ISSUE', true)) {
            return;
        }

        $sendCustomer = $options['customer'] ?? $this->flag('SMS_INVOICE_CUSTOMER', true);
        $sendSalesman = $options['salesman'] ?? $this->flag('SMS_INVOICE_SALESMAN', true);

        $invoice->loadMissing(['customer', 'salesman']);
        $message = $this->renderInvoiceMessage($invoice);

        if ($sendCustomer) {
            $this->sendToParty($invoice->customer, 'customer', $message, [
                'purpose' => 'invoice_issue',
                'sales_invoice_id' => $invoice->id,
            ]);
        }

        if ($sendSalesman) {
            $this->sendToParty($invoice->salesman, 'salesman', $message, [
                'purpose' => 'invoice_issue',
                'sales_invoice_id' => $invoice->id,
            ]);
        }
    }

    /**
     * Manual Dispatch from invoice list: SMS customer + salesman once, then lock the button.
     *
     * @return array{sent: int, skipped: list<string>, reply: string}
     */
    public function dispatchInvoice(SalesInvoice $invoice): array
    {
        if (! $this->sms->enabled()) {
            throw new RuntimeException('SMS is disabled.');
        }

        if ($invoice->isDraft()) {
            throw new RuntimeException('Issue the invoice before dispatching SMS.');
        }

        if ($invoice->sms_dispatched_at) {
            throw new RuntimeException('SMS already dispatched for this invoice.');
        }

        $invoice->loadMissing(['customer', 'salesman']);
        $message = $this->renderInvoiceMessage($invoice);
        $sent = 0;
        $skipped = [];
        $lastReply = '';

        $targets = [
            'customer' => $invoice->customer,
            'salesman' => $invoice->salesman,
        ];

        foreach ($targets as $label => $party) {
            $context = [
                'purpose' => 'invoice_dispatch',
                'recipient_type' => $label,
                'recipient_name' => $this->partyLabel($label, $party),
                'sales_invoice_id' => $invoice->id,
            ];

            $phones = $this->phonesFrom($party);
            if ($phones === []) {
                $skipped[] = $label.' (no phone/mobile)';
                $this->recordLog('', $message, SmsLog::STATUS_SKIPPED, 'No phone/mobile on '.$label, $context);
                continue;
            }

            foreach ($phones as $phone) {
                $result = $this->safeSend($phone, $message, $context);
                $lastReply = $result['response'] !== '' ? $result['response'] : $lastReply;
                if ($result['ok']) {
                    $sent++;
                } else {
                    $skipped[] = $label.' '.$phone.' ('.$result['message'].')';
                }
            }
        }

        if ($sent < 1) {
            throw new RuntimeException(
                'No SMS sent. '.($skipped !== [] ? implode('; ', $skipped) : 'Add customer or salesman mobile numbers.')
            );
        }

        $invoice->sms_dispatched_at = now();
        $invoice->save();

        return [
            'sent' => $sent,
            'skipped' => $skipped,
            'reply' => $lastReply,
        ];
    }

    private function renderInvoiceMessage(SalesInvoice $invoice): string
    {
        $template = $this->settings->smsInvoiceMessage();
        $date = $invoice->invoice_date?->format('d-m-Y')
            ?: ($invoice->issued_at?->format('d-m-Y') ?: now()->format('d-m-Y'));

        $replacements = [
            '{mask}' => $this->mask(),
            '{company}' => $this->settings->companyName(),
            '{invoice_no}' => (string) ($invoice->invoice_no ?: ('#'.$invoice->id)),
            '{amount}' => number_format((float) $invoice->total, 2),
            '{customer}' => $invoice->customer?->displayName() ?: 'Customer',
            '{salesman}' => $invoice->salesman?->name ?: '',
            '{date}' => $date,
        ];

        $message = str_replace(array_keys($replacements), array_values($replacements), $template);
        $message = trim(preg_replace('/\s+/', ' ', $message) ?? $message);

        return mb_substr($message, 0, 480);
    }

    /**
     * @param  array{send?: bool}|null  $options
     */
    public function cashVoucherCreated(CashVoucher $voucher, ?array $options = null): void
    {
        if (! $this->sms->enabled()) {
            return;
        }

        if ($voucher->isSettlementLinked() || ! $voucher->affectsCashBalance()) {
            return;
        }

        $isReceive = $voucher->type === 'receive';
        $envKey = $isReceive ? 'SMS_ON_CASH_RECEIVE' : 'SMS_ON_CASH_PAYMENT';
        $send = $options['send'] ?? $this->flag($envKey, true);
        if (! $send) {
            return;
        }

        if (! in_array($voucher->account_type, ['customer', 'supplier', 'vendor'], true)) {
            return;
        }

        $party = $this->resolveParty($voucher->account_type, (int) $voucher->account_id);
        $phones = $this->phonesFrom($party);
        if ($phones === []) {
            return;
        }

        $mask = $this->mask();
        $amount = number_format((float) $voucher->amount, 2);
        $ref = $voucher->voucher_no ?: ('#'.$voucher->id);
        $method = ($voucher->payment_method ?? 'cash') === 'bank' ? 'Bank' : 'Cash';
        $name = $this->partyLabel($voucher->account_type, $party);

        $text = $isReceive
            ? $mask.': Received Rs '.$amount.' ('.$method.') from '.$name.'. Ref '.$ref.'.'
            : $mask.': Paid Rs '.$amount.' ('.$method.') to '.$name.'. Ref '.$ref.'.';

        foreach ($phones as $phone) {
            $this->safeSend($phone, $text, [
                'purpose' => $isReceive ? 'cash_receive' : 'cash_payment',
                'recipient_type' => $voucher->account_type,
                'recipient_name' => $name,
                'cash_voucher_id' => $voucher->id,
            ]);
        }
    }

    /**
     * @param  array{purpose?: string, recipient_type?: string, recipient_name?: string, sales_invoice_id?: int|null, cash_voucher_id?: int|null}  $context
     */
    private function sendToParty(mixed $party, string $type, string $message, array $context): void
    {
        $context['recipient_type'] = $type;
        $context['recipient_name'] = $this->partyLabel($type, $party);

        $phones = $this->phonesFrom($party);
        if ($phones === []) {
            $this->recordLog('', $message, SmsLog::STATUS_SKIPPED, 'No phone/mobile on '.$type, $context);

            return;
        }

        foreach ($phones as $phone) {
            $this->safeSend($phone, $message, $context);
        }
    }

    /**
     * @param  array{purpose?: string, recipient_type?: string, recipient_name?: string, sales_invoice_id?: int|null, cash_voucher_id?: int|null}  $context
     * @return array{ok: bool, message: string, response: string}
     */
    private function safeSend(string $to, string $message, array $context = []): array
    {
        try {
            $result = $this->sms->send($to, $message);
            if (! $result['ok']) {
                Log::warning('sms_notifier.failed', [
                    'to' => $to,
                    'reply' => $result['response'],
                ]);
            }

            $this->recordLog(
                $to,
                $message,
                $this->statusFromResult($result),
                $result['response'] !== '' ? $result['response'] : $result['message'],
                $context
            );

            return $result;
        } catch (Throwable $e) {
            Log::warning('sms_notifier.exception', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);

            $this->recordLog($to, $message, SmsLog::STATUS_FAILED, $e->getMessage(), $context);

            return [
                'ok' => false,
                'message' => $e->getMessage(),
                'response' => '',
            ];
        }
    }

    /**
     * @param  array{ok: bool, message: string, response: string}  $result
     */
    private function statusFromResult(array $result): string
    {
        if ($result['ok']) {
            return SmsLog::STATUS_SENT;
        }

        $blob = mb_strtolower(trim(($result['response'] ?? '').' '.($result['message'] ?? '')));
        if (str_contains($blob, 'cloudflare')
            || str_contains($blob, 'blocked this')
            || str_contains($blob, 'you have been blocked')) {
            return SmsLog::STATUS_BLOCKED;
        }

        return SmsLog::STATUS_FAILED;
    }

    /**
     * @param  array{purpose?: string, recipient_type?: string, recipient_name?: string, sales_invoice_id?: int|null, cash_voucher_id?: int|null}  $context
     */
    private function recordLog(string $phone, string $message, string $status, string $reply, array $context = []): void
    {
        try {
            SmsLog::query()->create([
                'purpose' => $context['purpose'] ?? 'sms',
                'recipient_type' => $context['recipient_type'] ?? null,
                'recipient_name' => $context['recipient_name'] ?? null,
                'phone' => $phone !== '' ? $phone : '—',
                'message' => mb_substr($message, 0, 1000),
                'status' => $status,
                'provider_reply' => mb_substr(trim($reply), 0, 2000),
                'sales_invoice_id' => $context['sales_invoice_id'] ?? null,
                'cash_voucher_id' => $context['cash_voucher_id'] ?? null,
                'user_id' => auth()->id(),
            ]);
        } catch (Throwable $e) {
            Log::warning('sms_notifier.log_failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Collect phone and/or mobile for SMS.
     * Uses whichever is filled; if both differ, both are returned so SMS goes to each.
     *
     * @return list<string>
     */
    private function phonesFrom(mixed $party): array
    {
        if (! is_object($party)) {
            return [];
        }

        $numbers = [];
        foreach (['phone', 'mobile'] as $field) {
            $value = trim((string) ($party->{$field} ?? ''));
            if ($value === '') {
                continue;
            }

            $key = preg_replace('/\D+/', '', $value) ?: $value;
            if (isset($numbers[$key])) {
                continue;
            }

            $numbers[$key] = $value;
        }

        return array_values($numbers);
    }

    private function resolveParty(string $type, int $id): mixed
    {
        if ($id < 1) {
            return null;
        }

        return match ($type) {
            'customer' => Customer::query()->find($id),
            'supplier' => Supplier::query()->find($id),
            'vendor' => Vendor::query()->find($id),
            default => null,
        };
    }

    private function partyLabel(string $type, mixed $party): string
    {
        if ($party instanceof Customer) {
            return $party->displayName();
        }

        if ($party instanceof Salesman) {
            return (string) $party->name;
        }

        if (is_object($party) && isset($party->name) && trim((string) $party->name) !== '') {
            return (string) $party->name;
        }

        return ucfirst($type);
    }

    private function mask(): string
    {
        $from = trim((string) config('services.jazz_sms.from'));
        if ($from !== '') {
            return rtrim($from, '.');
        }

        return $this->settings->companyName() ?: 'CONTIMADE';
    }

    private function flag(string $envKey, bool $default): bool
    {
        $map = [
            'SMS_ON_INVOICE_ISSUE' => 'on_invoice_issue',
            'SMS_INVOICE_CUSTOMER' => 'invoice_customer',
            'SMS_INVOICE_SALESMAN' => 'invoice_salesman',
            'SMS_ON_CASH_RECEIVE' => 'on_cash_receive',
            'SMS_ON_CASH_PAYMENT' => 'on_cash_payment',
        ];

        $configKey = $map[$envKey] ?? null;
        if ($configKey === null) {
            return $default;
        }

        $value = config('services.jazz_sms.'.$configKey);
        if ($value === null) {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
