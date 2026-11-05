<?php

/**
 * Jazz SMS relay for Hostinger / Cloudflare IP blocks.
 *
 * Run on any PC/VPS whose IP is NOT blocked by Jazz (e.g. your office machine):
 *
 *   php -S 0.0.0.0:8787 tools/jazz-sms-relay.php
 *
 * Then expose that host (router port-forward, Cloudflare Tunnel, ngrok, etc.) and set
 * on the blocked production server:
 *
 *   SMS_RELAY_URL=https://your-relay-host:8787/
 *   SMS_RELAY_SECRET=change-me-long-random
 *
 * Keep SMS_USERNAME / SMS_PASSWORD / SMS_FROM on production (or override below).
 */

declare(strict_types=1);

const RELAY_SECRET = ''; // optional hard-coded secret; production SMS_RELAY_SECRET must match

header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'POST only']);
    exit;
}

$raw = file_get_contents('php://input') ?: '';
$params = [];
parse_str($raw, $params);
if ($params === []) {
    $params = $_POST;
}

$secret = (string) ($params['Secret'] ?? '');
$expected = RELAY_SECRET !== '' ? RELAY_SECRET : (string) getenv('SMS_RELAY_SECRET');
if ($expected !== '' && ! hash_equals($expected, $secret)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'Invalid relay secret']);
    exit;
}

$username = trim((string) ($params['Username'] ?? ''));
$password = (string) ($params['Password'] ?? '');
$from = trim((string) ($params['From'] ?? ''));
$to = trim((string) ($params['To'] ?? ''));
$message = trim((string) ($params['Message'] ?? ''));
$url = trim((string) ($params['Url'] ?? 'https://connect.jazzcmt.com/sendsms_url.html'));

if ($username === '' || $password === '' || $from === '' || $to === '' || $message === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Missing Username, Password, From, To, or Message']);
    exit;
}

$query = http_build_query([
    'Username' => $username,
    'Password' => $password,
    'From' => $from,
    'To' => $to,
    'Message' => $message,
]);

$target = $url.(str_contains($url, '?') ? '&' : '?').$query;
$ch = curl_init($target);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_CONNECTTIMEOUT => 15,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
    CURLOPT_HTTPHEADER => [
        'User-Agent: ContimadeERP-SMS-Relay/1.0',
        'Accept: text/plain,text/html,*/*',
    ],
]);
$body = curl_exec($ch);
$error = curl_error($ch) ?: '';
$status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$body = is_string($body) ? trim($body) : '';
$lower = mb_strtolower($body);
$cloudflare = str_contains($lower, 'cloudflare')
    || str_contains($lower, 'sorry, you have been blocked')
    || str_contains($lower, 'attention required');
$ok = ! $cloudflare
    && $status >= 200
    && $status < 300
    && $body !== ''
    && ! str_contains($lower, 'fail')
    && ! str_contains($lower, 'not allowed')
    && (str_contains($lower, 'success')
        || str_contains($lower, 'message sent')
        || preg_match('/\b(0|00|000|200|300)\b/', $body) === 1);

echo json_encode([
    'ok' => $ok,
    'status' => $status,
    'response' => $body !== '' ? $body : $error,
    'message' => $ok ? 'Relay accepted' : ($cloudflare ? 'Relay host also Cloudflare-blocked' : ($body ?: $error)),
], JSON_UNESCAPED_UNICODE);
