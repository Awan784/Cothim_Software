@php
    $footerSettings = $settings ?? app(\App\Services\SettingsService::class);
    $printFooterText = trim((string) ($printFooter ?? $footerSettings->printFooter()));
@endphp
<div class="footer">
    @if($printFooterText !== '')
        <div class="print-credit">{{ $printFooterText }}</div>
    @endif
    @isset($printedAt)
        <div>Printed {{ ams_datetime($printedAt) }}</div>
    @endisset
</div>
