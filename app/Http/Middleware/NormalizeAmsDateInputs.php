<?php

namespace App\Http\Middleware;

use App\Support\AmsDate;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeAmsDateInputs
{
    /** @var list<string> */
    private const DATE_FIELDS = [
        'voucher_date',
        'po_date',
        'return_date',
        'expense_date',
        'from_date',
        'to_date',
        'order_date',
        'invoice_date',
        'due_date',
        'bill_date',
        'extracted_date',
        'settlement_date',
        'expiry_date',
        'manufactured_at',
    ];

    /** @var list<string> */
    private const DATETIME_FIELDS = [
        'moved_at',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $request->merge($this->normalize($request->all()));

        return $next($request);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalize(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->normalize($value);
                continue;
            }

            if (! is_string($value) || trim($value) === '') {
                continue;
            }

            if (in_array($key, self::DATE_FIELDS, true)) {
                $parsed = AmsDate::parse($value);
                if ($parsed) {
                    $data[$key] = $parsed->format('Y-m-d');
                }
            }

            if (in_array($key, self::DATETIME_FIELDS, true)) {
                $parsed = AmsDate::parseDateTime($value);
                if ($parsed) {
                    $data[$key] = $parsed->format('Y-m-d H:i:s');
                }
            }
        }

        return $data;
    }
}
