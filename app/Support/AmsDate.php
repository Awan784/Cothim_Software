<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use DateTimeInterface;
use InvalidArgumentException;

class AmsDate
{
    public const DISPLAY = 'd-m-y';

    public const DISPLAY_DATETIME = 'd-m-y H:i';

    public const INPUT = 'd-m-y';

    /**
     * @param  CarbonInterface|DateTimeInterface|string|null  $date
     */
    public static function format(mixed $date, ?string $format = null): string
    {
        if ($date === null || $date === '') {
            return '';
        }

        $carbon = self::toCarbon($date);

        return $carbon ? $carbon->format($format ?? self::DISPLAY) : '';
    }

    /**
     * @param  CarbonInterface|DateTimeInterface|string|null  $date
     */
    public static function formatDateTime(mixed $date): string
    {
        return self::format($date, self::DISPLAY_DATETIME);
    }

    /**
     * @param  CarbonInterface|DateTimeInterface|string|null  $date
     */
    public static function inputValue(mixed $date): string
    {
        return self::format($date, self::INPUT);
    }

    /**
     * @param  CarbonInterface|DateTimeInterface|string|null  $date
     */
    public static function inputDateTimeValue(mixed $date): string
    {
        return self::format($date, self::DISPLAY_DATETIME);
    }

    public static function parse(?string $value): ?Carbon
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $value = trim($value);

        foreach (['d-m-y', 'd-m-Y', 'Y-m-d', 'd/m/y', 'd/m/Y'] as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, $value);
                if ($parsed !== false) {
                    return $parsed->startOfDay();
                }
            } catch (InvalidArgumentException) {
                continue;
            }
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    public static function parseDateTime(?string $value): ?Carbon
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $value = trim(str_replace('T', ' ', $value));

        foreach (['d-m-y H:i', 'd-m-Y H:i', 'Y-m-d H:i', 'Y-m-d H:i:s', 'd-m-y H:i:s', 'd-m-Y H:i:s'] as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, $value);
                if ($parsed !== false) {
                    return $parsed;
                }
            } catch (InvalidArgumentException) {
                continue;
            }
        }

        try {
            return Carbon::parse($value);
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    /**
     * @param  CarbonInterface|DateTimeInterface|string|null  $date
     */
    private static function toCarbon(mixed $date): ?Carbon
    {
        if ($date instanceof CarbonInterface) {
            return $date->copy();
        }

        if ($date instanceof DateTimeInterface) {
            return Carbon::instance($date);
        }

        if (is_string($date)) {
            return self::parse($date) ?? (self::parseDateTime($date));
        }

        return null;
    }
}
