<?php

use App\Services\JazzSmsService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('sms:diagnose {--to= : Optional mobile to send a test SMS}', function (JazzSmsService $sms) {
    $this->info('Jazz SMS diagnose');
    $this->line('Enabled: '.($sms->enabled() ? 'yes' : 'no'));
    $this->line('Configured: '.($sms->configured() ? 'yes' : 'no'));
    $this->line('URL: '.config('services.jazz_sms.url'));
    $this->line('Force IPv4: '.(config('services.jazz_sms.force_ipv4') ? 'yes' : 'no'));
    $this->line('Relay: '.(trim((string) config('services.jazz_sms.relay_url')) ?: '(none — direct to Jazz)'));
    $this->line('HTTP proxy: '.(trim((string) config('services.jazz_sms.http_proxy')) ?: '(none)'));
    $this->line('Bind IP: '.(trim((string) config('services.jazz_sms.bind_ip')) ?: '(none)'));

    $ips = $sms->detectOutboundIps();
    $this->newLine();
    $this->warn('Outbound IPs Jazz/Cloudflare will see (send these to Jazz support to whitelist):');
    $this->line('  IPv4: '.($ips['ipv4'] ?: 'unknown'));
    $this->line('  IPv6: '.($ips['ipv6'] ?: 'none/unknown'));

    $to = trim((string) ($this->option('to') ?: $sms->defaultTestTo()));
    if ($to === '') {
        $this->newLine();
        $this->comment('Pass --to=03xxxxxxxxx to send a live test SMS.');

        return 0;
    }

    $this->newLine();
    $this->info('Sending test SMS to '.$to.' …');
    $result = $sms->send($to, 'CONTIMADE SMS diagnose '.now()->format('d-m-Y H:i:s'));
    $this->{$result['ok'] ? 'info' : 'error'}($result['message']);

    return $result['ok'] ? 0 : 1;
})->purpose('Show outbound IPs and optionally send a Jazz SMS test');