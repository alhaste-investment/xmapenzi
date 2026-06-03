<?php
require_once __DIR__ . '/db.php';

function grebo_config(): array {
    return [
        'base' => rtrim(setting('grebo_base_url', 'https://grebo.tesloty.com'), '/'),
        'key'  => setting('grebo_api_key'),
        'webhook_secret' => setting('grebo_webhook_secret'),
    ];
}

function grebo_http(string $method, string $url, array $cfg, ?array $body = null): array {
    $ch = curl_init($url);
    $headers = [
        'Accept: application/json',
        'Content-Type: application/json',
        'Authorization: Bearer ' . ($cfg['key'] ?? ''),
    ];
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_TIMEOUT        => 30,
    ]);
    if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    $res  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);
    if ($res === false) return ['status' => 0, 'json' => ['error' => $err]];
    $j = json_decode($res, true);
    return ['status' => $code, 'json' => is_array($j) ? $j : ['raw' => $res]];
}

function grebo_deposit(array $cfg, array $args): array {
    // args: amount, phone, reference, callback_url
    $body = [
        'amount' => (int)$args['amount'],
        'method' => 'mobile',
        'phone'  => $args['phone'],
        'reference' => $args['reference'],
        'callback_url' => $args['callback_url'],
    ];
    return grebo_http('POST', $cfg['base'] . '/api/v1/deposits', $cfg, $body);
}

function grebo_verify_signature(string $secret, string $rawBody, string $signature): bool {
    if ($secret === '') return false;
    $expected = hash_hmac('sha256', $rawBody, $secret);
    return hash_equals($expected, $signature);
}

function grebo_transaction_lookup(array $cfg, string $reference): array {
    // Try to find transaction by reference via transactions endpoint
    $url = $cfg['base'] . '/api/v1/transactions?reference=' . urlencode($reference) . '&limit=1';
    return grebo_http('GET', $url, $cfg);
}
