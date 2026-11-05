<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
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

        try {
            $response = $this->request($url, $query);
        } catch (ConnectionException $e) {
            Log::warning('jazz_sms.connection', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'message' => 'SMS failed for '.$to.'. Could not reach Jazz SMS gateway: '.$e->getMessage(),
                'response' => $e->getMessage(),
            ];
        }

        $body = trim((string) $response->body());
        $cloudflare = $this->cloudflareBlock($body, (int) $response->status());
        $ok = ! $cloudflare && $response->successful() && $this->looksSuccessful($body);
        $reply = $this->shortReply($body, (int) $response->status(), $cloudflare);

        Log::info('jazz_sms.send', [
            'to' => $to,
            'from' => $from,
            'status' => $response->status(),
            'ok' => $ok,
            'cloudflare' => $cloudflare,
            'body' => mb_substr(preg_replace('/\s+/', ' ', $body) ?? $body, 0, 400),
        ]);

        return [
            'ok' => $ok,
            'message' => $ok
                ? 'SMS accepted for '.$to.'. Jazz reply: '.$reply
                : 'SMS failed for '.$to.'. '.$reply,
            'response' => $reply,
        ];
    }

    /**
     * @param  array<string, string>  $query
     */
    private function request(string $url, array $query): \Illuminate\Http\Client\Response
    {
        $curl = [
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        ];

        if (defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4') && $this->forceIpv4()) {
            $curl[CURLOPT_IPRESOLVE] = CURL_IPRESOLVE_V4;
        }

        $http = Http::timeout(25)
            ->withOptions(['curl' => $curl, 'allow_redirects' => false])
            ->withHeaders([
                'User-Agent' => 'ContimadeERP-SMS/1.0 (+Laravel)',
                'Accept' => 'text/plain,text/html,*/*',
                'Connection' => 'close',
            ]);

        // Prefer GET (Jazz URL API). If blocked, retry once as form POST on same path.
        $response = $http->get($url, $query);
        if ($this->cloudflareBlock(trim((string) $response->body()), (int) $response->status())) {
            $response = $http->asForm()->post($url, $query);
        }

        return $response;
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

        // Drop leading 00 international prefix (0092…).
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        // Country code 92 + 10-digit mobile → 03xxxxxxxxx (11 digits).
        if (str_starts_with($digits, '92') && strlen($digits) === 12) {
            $digits = '0'.substr($digits, 2);
        }

        // Bare mobile without leading 0 (3xxxxxxxxx).
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
                || preg_match('/cf-ray[:\s]+([a-z0-9-]+)/i', $body, $m)) {
                $ray = $m[1];
            }

            $msg = 'Jazz/Cloudflare blocked this server IP from connect.jazzcmt.com (HTTP '.$status.'). '
                .'Ask Jazz CMT support to whitelist your server public IP, or host SMS from an allowlisted IP. '
                .'Local PC works because its IP is not blocked.';

            if ($ray) {
                $msg .= ' Ray ID: '.$ray.'.';
            }

            return $msg;
        }

        if ($body === '') {
            return '(empty response, HTTP '.$status.')';
        }

        if (str_contains(mb_strtolower($body), '<html')) {
            return 'Unexpected HTML reply from Jazz (HTTP '.$status.'). Check SMS_URL and server outbound access.';
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

        // Unknown Jazz text with HTTP 200 — treat as failure so the UI shows the reply.
        return false;
    }
}
