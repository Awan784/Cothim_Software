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
        'expense_date',
        'from_date',
        'to_date',
    ];

    /** @var list<string> */
    private const DATETIME_FIELDS = [
        'moved_at',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $normalized = [];

        foreach (self::DATE_FIELDS as $field) {
            if (! $request->has($field)) {
                continue;
            }

            $value = $request->input($field);
            if (! is_string($value) || trim($value) === '') {
                continue;
            }

            $parsed = AmsDate::parse($value);
            if ($parsed) {
                $normalized[$field] = $parsed->format('Y-m-d');
            }
        }

        foreach (self::DATETIME_FIELDS as $field) {
            if (! $request->has($field)) {
                continue;
            }

            $value = $request->input($field);
            if (! is_string($value) || trim($value) === '') {
                continue;
            }

            $parsed = AmsDate::parseDateTime($value);
            if ($parsed) {
                $normalized[$field] = $parsed->format('Y-m-d H:i:s');
            }
        }

        if ($normalized !== []) {
            $request->merge($normalized);
        }

        return $next($request);
    }
}
