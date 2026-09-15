<?php

namespace App\Services;

class VatCalculator
{
    public const STANDARD_RATE = 15.0;

    /**
     * @return array{net: float, vat: float, gross: float, rate: float, discount_rate: float, discount_amount: float}
     */
    public function line(float $quantity, float $unitPrice, float $vatRate, float $discountRate = 0): array
    {
        $gross = round($quantity * $unitPrice, 2);
        $discountRate = max(0, min(100, $discountRate));
        $discount = round($gross * ($discountRate / 100), 2);
        $net = round($gross - $discount, 2);
        $vat = round($net * ($vatRate / 100), 2);

        return [
            'net' => $net,
            'vat' => $vat,
            'gross' => round($net + $vat, 2),
            'rate' => $vatRate,
            'discount_rate' => $discountRate,
            'discount_amount' => $discount,
        ];
    }

    /**
     * @param  list<array{quantity?: mixed, unit_price?: mixed, vat_rate?: mixed, discount_rate?: mixed}>  $lines
     * @return array{subtotal: float, discount_amount: float, vat_amount: float, total: float, lines: list<array<string, mixed>>}
     */
    public function document(array $lines, float $defaultRate = self::STANDARD_RATE): array
    {
        $computed = [];
        $subtotal = 0.0;
        $discountAmount = 0.0;
        $vatAmount = 0.0;

        foreach ($lines as $index => $line) {
            $qty = (float) ($line['quantity'] ?? 0);
            $price = (float) ($line['unit_price'] ?? 0);
            $rate = isset($line['vat_rate']) && $line['vat_rate'] !== ''
                ? (float) $line['vat_rate']
                : $defaultRate;
            $discountRate = isset($line['discount_rate']) && $line['discount_rate'] !== ''
                ? (float) $line['discount_rate']
                : 0.0;
            $calc = $this->line($qty, $price, $rate, $discountRate);
            $subtotal += $calc['net'];
            $discountAmount += $calc['discount_amount'];
            $vatAmount += $calc['vat'];
            $computed[] = array_merge($line, [
                'quantity' => $qty,
                'unit_price' => $price,
                'discount_rate' => $calc['discount_rate'],
                'discount_amount' => $calc['discount_amount'],
                'vat_rate' => $rate,
                'line_net' => $calc['net'],
                'vat_amount' => $calc['vat'],
                'line_total' => $calc['gross'],
                'sort_order' => $index,
            ]);
        }

        return [
            'subtotal' => round($subtotal, 2),
            'discount_amount' => round($discountAmount, 2),
            'vat_amount' => round($vatAmount, 2),
            'total' => round($subtotal + $vatAmount, 2),
            'lines' => $computed,
        ];
    }
}
