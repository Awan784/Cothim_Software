<?php

use App\Support\AmsDate;

if (! function_exists('ams_date')) {
    function ams_date(mixed $date, ?string $format = null): string
    {
        return AmsDate::format($date, $format);
    }
}

if (! function_exists('ams_datetime')) {
    function ams_datetime(mixed $date): string
    {
        return AmsDate::formatDateTime($date);
    }
}

if (! function_exists('ams_date_input')) {
    function ams_date_input(mixed $date): string
    {
        return AmsDate::inputValue($date);
    }
}

if (! function_exists('ams_datetime_input')) {
    function ams_datetime_input(mixed $date): string
    {
        return AmsDate::inputDateTimeValue($date);
    }
}

if (! function_exists('ams_num')) {
    function ams_num(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '0';
        }

        $number = (float) $value;
        if (abs($number - round($number)) < 0.0000001) {
            return number_format($number, 0, '.', ',');
        }

        return rtrim(rtrim(number_format($number, 2, '.', ','), '0'), '.');
    }
}

if (! function_exists('can_module')) {
    function can_module(string $module, string $action): bool
    {
        $user = auth()->user();

        return $user ? $user->canModule($module, $action) : false;
    }
}

if (! function_exists('wafi_whatsapp_url')) {
    function wafi_whatsapp_url(?string $text = null, ?string $number = null): string
    {
        $digits = preg_replace('/\D+/', '', (string) ($number ?: config('ams.whatsapp')));
        $url = 'https://wa.me/'.$digits;
        if (filled($text)) {
            $url .= '?text='.rawurlencode($text);
        }

        return $url;
    }
}

if (! function_exists('wafi_locale')) {
    function wafi_locale(): string
    {
        return app()->getLocale() === 'ar' ? 'ar' : 'en';
    }
}

if (! function_exists('wafi_is_rtl')) {
    function wafi_is_rtl(): bool
    {
        return wafi_locale() === 'ar';
    }
}

if (! function_exists('organization_id')) {
    function organization_id(): ?int
    {
        static $resolving = false;

        if (app()->bound('current_organization_id')) {
            $id = app('current_organization_id');

            return $id ? (int) $id : null;
        }

        if ($resolving) {
            return null;
        }

        $resolving = true;
        try {
            $id = auth()->user()?->organization_id
                ?? auth('salesman')->user()?->organization_id
                ?? null;
        } finally {
            $resolving = false;
        }

        return $id ? (int) $id : null;
    }
}

if (! function_exists('current_organization')) {
    function current_organization(): ?\App\Models\Organization
    {
        if (app()->bound('current_organization')) {
            return app('current_organization');
        }

        $id = organization_id();
        if (! $id) {
            return null;
        }

        $org = \App\Models\Organization::query()->find($id);
        if ($org) {
            app()->instance('current_organization', $org);
        }

        return $org;
    }
}
