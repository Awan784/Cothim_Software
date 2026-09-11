<?php

namespace App\Support;

class AmountInWords
{
    private const ONES = [
        '', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
        'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen',
        'Seventeen', 'Eighteen', 'Nineteen',
    ];

    private const TENS = [
        '', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety',
    ];

    public static function rupees(float $amount): string
    {
        $rupees = (int) floor($amount);
        $paisa = (int) round(($amount - $rupees) * 100);

        if ($rupees === 0 && $paisa === 0) {
            return 'Rupees Zero Only';
        }

        $words = 'Rupees '.trim(self::convert($rupees));

        if ($paisa > 0) {
            $words .= ' and '.trim(self::convert($paisa)).' Paisa';
        }

        return $words.' Only';
    }

    private static function convert(int $number): string
    {
        if ($number === 0) {
            return '';
        }

        if ($number < 20) {
            return self::ONES[$number];
        }

        if ($number < 100) {
            return trim(self::TENS[(int) floor($number / 10)].' '.self::ONES[$number % 10]);
        }

        if ($number < 1000) {
            return trim(self::ONES[(int) floor($number / 100)].' Hundred '.self::convert($number % 100));
        }

        if ($number < 100000) {
            return trim(self::convert((int) floor($number / 1000)).' Thousand '.self::convert($number % 1000));
        }

        if ($number < 10000000) {
            return trim(self::convert((int) floor($number / 100000)).' Lakh '.self::convert($number % 100000));
        }

        return trim(self::convert((int) floor($number / 10000000)).' Crore '.self::convert($number % 10000000));
    }
}
