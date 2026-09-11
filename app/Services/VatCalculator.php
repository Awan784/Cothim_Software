<?php

namespace App\Services;

class VatCalculator
{
    public const STANDARD_RATE = 15.0;

    /**
     * @return array{net: float, vat: float, gross: float, rate: float}
     */
    public function line(float $quantity, float $unitPrice, float $vatRate): array
    {
        $net = round($quantity * $unitPrice, 2);
        $vat = round($net * ($vatRate / 100), 2);

        return [
            'net' => $net,
            'vat' => $vat,
            'gross' => round($net + $vat, 2),
            'rate' => $vatRate,
        ];
    }

    /**
     * @param  list<array{quantity?: mixed, unit_price?: mixed, vat_rate?: mixed}>  $lines
     * @return array{subtotal: float, vat_amount: float, total: float, lines: list<array<string, mixed>>}
     */
    public function document(array $lines, float $defaultRate = self::STANDARD_RATE): array
    {
        $computed = [];
        $subtotal = 0.0;
        $vatAmount = 0.0;

        foreach ($lines as $index => $line) {
            $qty = (float) ($line['quantity'] ?? 0);
            $price = (float) ($line['unit_price'] ?? 0);
            $rate = isset($line['vat_rate']) && $line['vat_rate'] !== ''
                ? (float) $line['vat_rate']
                : $defaultRate;
            $calc = $this->line($qty, $price, $rate);
            $subtotal += $calc['net'];
            $vatAmount += $calc['vat'];
            $computed[] = array_merge($line, [
                'quantity' => $qty,
                'unit_price' => $price,
                'vat_rate' => $rate,
                'line_net' => $calc['net'],
                'vat_amount' => $calc['vat'],
                'line_total' => $calc['gross'],
                'sort_order' => $index,
            ]);
        }

        return [
            'subtotal' => round($subtotal, 2),
            'vat_amount' => round($vatAmount, 2),
            'total' => round($subtotal + $vatAmount, 2),
            'lines' => $computed,
        ];
    }
}
