<?php

namespace App\Services;

use App\Models\SalesInvoice;
use Carbon\Carbon;

class ZatcaQrService
{
    /**
     * ZATCA Phase 1 TLV QR payload (seller, VAT number, timestamp, total, VAT).
     */
    public function payload(SalesInvoice $invoice, SettingsService $settings): string
    {
        $issued = $invoice->issued_at instanceof Carbon
            ? $invoice->issued_at
            : now();

        $tags = [
            1 => $settings->companyName(),
            2 => (string) $settings->get('company_vat_number', ''),
            3 => $issued->copy()->timezone('Asia/Riyadh')->format('Y-m-d\TH:i:s\Z'),
            4 => number_format((float) $invoice->total, 2, '.', ''),
            5 => number_format((float) $invoice->vat_amount, 2, '.', ''),
        ];

        $binary = '';
        foreach ($tags as $tag => $value) {
            $bytes = mb_convert_encoding((string) $value, 'UTF-8');
            $binary .= chr($tag).chr(strlen($bytes)).$bytes;
        }

        return base64_encode($binary);
    }
}
