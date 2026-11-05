<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use RuntimeException;

class JazzSmsService
{
    /**
     * @return array{ok: bool, message: string, response: string}
     */
    public function send(string $to, string $message): array
    {
        if (! $this->enabled()) {
            throw new RuntimeException('SMS is disabled. Set SMS_ENABLED=true in .env.');
        }

        $username = trim((string) config('services.jazz_sms.username'));
        $password = (string) config('services.jazz_sms.password');
        $from = trim((string) config('services.jazz_sms.from'));
        $url = trim((string) config('services.jazz_sms.url'));

        if ($username === '' || $password === '' || $from === '' || $url === '') {
            throw new RuntimeException('SMS is not configured. Set SMS_URL, SMS_USERNAME, SMS_PASSWORD, and SMS_FROM in .env.');
        }

        $to = $this->normalizePhone($to);
        $message = trim($message);
        if ($message === '') {
            throw new RuntimeException('Message cannot be empty.');
        }

        $query = [
            'Username' => $username,
            'Password' => $password,
            'From' => $from,
            'To' => $to,
            'Message' => $message,
        ];

        $relay = trim((string) config('services.jazz_sms.relay_url'));
        $result = $relay !== ''
            ? $this->sendViaRelay($relay, $query)
            : $this->sendViaJazz($url, $query);

        Log::info('jazz_sms.send', [
            'to' => $to,
            'from' => $from,
            'via' => $relay !== '' ? 'relay' : 'direct',
            'status' => $result['status'],
            'ok' => $result['ok'],
            'cloudflare' => $result['cloudflare'],
            'body' => mb_substr(preg_replace('/\s+/', ' ', $result['body']) ?? $result['body'], 0, 400),
        ]);

        $reply = $this->shortReply($result['body'], $result['status'], $result['cloudflare']);

        return [
            'ok' => $result['ok'],
            'message' => $result['ok']
                ? 'SMS accepted for '.$to.'. Jazz reply: '.$reply
                : 'SMS failed for '.$to.'. '.$reply,
            'response' => $reply,
        ];
    }

    /**
     * @param  array<string, string>  $query
     * @return array{ok: bool, status: int, body: string, cloudflare: bool}
     */
    private function sendViaRelay(string $relayUrl, array $query): array
    {
        $secret = (string) config('services.jazz_sms.relay_secret');
        $payload = array_merge($query, [
            'Url' => trim((string) config('services.jazz_sms.url')),
            'Secret' => $secret,
        ]);

        $raw = $this->curlRequest($relayUrl, $payload, 'POST');
        $body = trim($raw['body']);
        $decoded = json_decode($body, true);

        if (is_array($decoded) && array_key_exists('ok', $decoded)) {
            $jazzBody = trim((string) ($decoded['response'] ?? $decoded['body'] ?? ''));
            $status = (int) ($decoded['status'] ?? $raw['status']);
            $cloudflare = $this->cloudflareBlock($jazzBody, $status)
                || $this->cloudflareBlock($body, $raw['status']);
            $ok = (bool) $decoded['ok'] && ! $cloudflare && $this->looksSuccessful($jazzBody !== '' ? $jazzBody : $body);

            return [
                'ok' => $ok,
                'status' => $status,
                'body' => $jazzBody !== '' ? $jazzBody : ($decoded['message'] ?? $body),
                'cloudflare' => $cloudflare,
            ];
        }

        $cloudflare = $this->cloudflareBlock($body, $raw['status']);

        return [
            'ok' => ! $cloudflare && $raw['status'] >= 200 && $raw['status'] < 300 && $this->looksSuccessful($body),
            'status' => $raw['status'],
            'body' => $body !== '' ? $body : $raw['error'],
            'cloudflare' => $cloudflare,
        ];
    }

    /**
     * @param  array<string, string>  $query
     * @return array{ok: bool, status: int, body: string, cloudflare: bool}
     */
    private function sendViaJazz(string $url, array $query): array
    {
        $raw = $this->curlRequest($url, $query, 'GET');
        $body = trim($raw['body']);
        $cloudflare = $this->cloudflareBlock($body, $raw['status']);

        if ($cloudflare) {
            $raw = $this->curlRequest($url, $query, 'POST');
            $body = trim($raw['body']);
            $cloudflare = $this->cloudflareBlock($body, $raw['status']);
        }

        $ok = ! $cloudflare
            && $raw['status'] >= 200
            && $raw['status'] < 300
            && $this->looksSuccessful($body);

        return [
            'ok' => $ok,
            'status' => $raw['status'],
            'body' => $body !== '' ? $body : $raw['error'],
            'cloudflare' => $cloudflare,
        ];
    }

    /**
     * @param  array<string, string>  $params
     * @return array{status: int, body: string, error: string}
     */
    private function curlRequest(string $url, array $params, string $method): array
    {
        if (! function_exists('curl_init')) {
            throw new RuntimeException('PHP cURL extension is required for SMS.');
        }

        $method = strtoupper($method);
        $query = http_build_query($params);
        $target = $url;

        if ($method === 'GET') {
            $target .= (str_contains($url, '?') ? '&' : '?').$query;
        }

        $ch = curl_init($target);
        $headers = [
            'User-Agent: ContimadeERP-SMS/1.1',
            'Accept: text/plain,text/html,application/json,*/*',
            'Connection: close',
        ];

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ];

        if ($this->forceIpv4() && defined('CURL_IPRESOLVE_V4')) {
            $options[CURLOPT_IPRESOLVE] = CURL_IPRESOLVE_V4;
        }

        $bindIp = trim((string) config('services.jazz_sms.bind_ip'));
        if ($bindIp !== '') {
            $options[CURLOPT_INTERFACE] = $bindIp;
        }

        $proxy = trim((string) config('services.jazz_sms.http_proxy'));
        if ($proxy !== '') {
            $options[CURLOPT_PROXY] = $proxy;
        }

        if ($method === 'POST') {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = $query;
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
            $options[CURLOPT_HTTPHEADER] = $headers;
        }

        curl_setopt_array($ch, $options);
        $body = curl_exec($ch);
        $error = curl_error($ch) ?: '';
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false) {
            return [
                'status' => 0,
                'body' => '',
                'error' => $error !== '' ? $error : 'cURL request failed',
            ];
        }

        return [
            'status' => $status,
            'body' => (string) $body,
            'error' => $error,
        ];
    }

    /**
     * @return array{ipv4: ?string, ipv6: ?string}
     */
    public function detectOutboundIps(): array
    {
        $ipv4 = $this->fetchText('https://api.ipify.org', true);
        $ipv6 = $this->fetchText('https://api64.ipify.org', false);

        return [
            'ipv4' => $ipv4,
            'ipv6' => $ipv6 && $ipv6 !== $ipv4 ? $ipv6 : null,
        ];
    }

    private function fetchText(string $url, bool $forceIpv4): ?string
    {
        if (! function_exists('curl_init')) {
            return null;
        }

        $ch = curl_init($url);
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => 'ContimadeERP-SMS/1.1',
        ];
        if ($forceIpv4 && defined('CURL_IPRESOLVE_V4')) {
            $options[CURLOPT_IPRESOLVE] = CURL_IPRESOLVE_V4;
        }
        curl_setopt_array($ch, $options);
        $body = curl_exec($ch);
        curl_close($ch);

        $text = is_string($body) ? trim($body) : '';

        return $text !== '' ? $text : null;
    }

    public function enabled(): bool
    {
        return (bool) config('services.jazz_sms.enabled');
    }

    public function configured(): bool
    {
        return $this->enabled()
            && trim((string) config('services.jazz_sms.username')) !== ''
            && (string) config('services.jazz_sms.password') !== ''
            && trim((string) config('services.jazz_sms.from')) !== ''
            && trim((string) config('services.jazz_sms.url')) !== '';
    }

    public function defaultTestTo(): string
    {
        return trim((string) config('services.jazz_sms.test_to'));
    }

    /**
     * Normalize Pakistan mobiles for Jazz: 92300… / 0092300… / 300… → 0300…
     */
    public function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits === '') {
            throw new RuntimeException('Enter a valid mobile number.');
        }

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (str_starts_with($digits, '92') && strlen($digits) === 12) {
            $digits = '0'.substr($digits, 2);
        }

        if (str_starts_with($digits, '3') && strlen($digits) === 10) {
            $digits = '0'.$digits;
        }

        return $digits;
    }

    private function forceIpv4(): bool
    {
        $value = config('services.jazz_sms.force_ipv4', true);
        if ($value === null) {
            return true;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    private function cloudflareBlock(string $body, int $status): bool
    {
        if ($body === '') {
            return false;
        }

        $lower = mb_strtolower($body);

        return str_contains($lower, 'cloudflare')
            || str_contains($lower, 'cf-error-details')
            || str_contains($lower, 'attention required')
            || str_contains($lower, 'sorry, you have been blocked')
            || ($status === 403 && str_contains($lower, 'unable to access'));
    }

    private function shortReply(string $body, int $status, bool $cloudflare): string
    {
        if ($cloudflare) {
            $ray = null;
            if (preg_match('/Cloudflare Ray ID:\s*<strong[^>]*>\s*([a-z0-9]+)\s*<\/strong>/i', $body, $m)
                || preg_match('/\bRay ID:\s*([a-z0-9]+)/i', $body, $m)
                || preg_match('/cf-ray[:\s]+([a-z0-9-]+)/i', $body, $m)) {
                $ray = $m[1];
            }

            $ips = $this->detectOutboundIps();
            $ipHint = $ips['ipv4'] ? 'Server public IPv4: '.$ips['ipv4'].'. ' : '';
            if (! empty($ips['ipv6'])) {
                $ipHint .= 'IPv6: '.$ips['ipv6'].'. ';
            }

            $msg = 'Jazz/Cloudflare blocked this hosting IP (HTTP '.$status.'). '
                .$ipHint
                .'Email Jazz CMT support to whitelist these IPs, or set SMS_RELAY_URL to a PC/VPS that is not blocked.';

            if ($ray) {
                $msg .= ' Ray ID: '.$ray.'.';
            }

            return $msg;
        }

        if ($body === '') {
            return '(empty response, HTTP '.$status.')';
        }

        if (str_contains(mb_strtolower($body), '<html')) {
            return 'Unexpected HTML reply from Jazz (HTTP '.$status.'). Check SMS_URL / SMS_RELAY_URL.';
        }

        return mb_substr(preg_replace('/\s+/', ' ', $body) ?? $body, 0, 300);
    }

    private function looksSuccessful(string $body): bool
    {
        if ($body === '') {
            return false;
        }

        $lower = mb_strtolower($body);

        if (str_contains($lower, '<html')
            || str_contains($lower, 'error')
            || str_contains($lower, 'fail')
            || str_contains($lower, 'invalid')
            || str_contains($lower, 'denied')
            || str_contains($lower, 'unauthorized')
            || str_contains($lower, 'not allowed')
            || str_contains($lower, 'not authorised')
            || str_contains($lower, 'not authorized')
            || str_contains($lower, 'insufficient')
            || str_contains($lower, 'blocked')
            || str_contains($lower, 'rejected')) {
            return false;
        }

        if (str_contains($lower, 'success')
            || str_contains($lower, 'message sent')
            || str_contains($lower, 'accepted')
            || preg_match('/\b(0|00|000|200|300)\b/', $body) === 1) {
            return true;
        }

        return false;
    }
}
