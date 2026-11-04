<?php

namespace App\Services;

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

        $response = Http::timeout(20)
            ->accept('*/*')
            ->get($url, [
                'Username' => $username,
                'Password' => $password,
                'From' => $from,
                'To' => $to,
                'Message' => $message,
            ]);

        $body = trim((string) $response->body());
        $ok = $response->successful() && $this->looksSuccessful($body);
        $reply = $body !== '' ? $body : '(empty response, HTTP '.$response->status().')';

        Log::info('jazz_sms.send', [
            'to' => $to,
            'from' => $from,
            'status' => $response->status(),
            'ok' => $ok,
            'body' => mb_substr($body, 0, 500),
        ]);

        return [
            'ok' => $ok,
            'message' => $ok
                ? 'SMS accepted for '.$to.'. Jazz reply: '.$reply
                : 'SMS failed for '.$to.'. Jazz reply: '.$reply,
            'response' => $body,
        ];
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

    private function looksSuccessful(string $body): bool
    {
        if ($body === '') {
            return false;
        }

        $lower = mb_strtolower($body);

        if (str_contains($lower, 'error')
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

        // Unknown Jazz text with HTTP 200 — treat as failure so the dashboard shows the reply.
        return false;
    }
}
