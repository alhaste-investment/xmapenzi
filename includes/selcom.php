<?php
require_once __DIR__ . '/db.php';

function selcom_config(): array {
    return [
        'base'   => rtrim(setting('selcom_base_url', SELCOM_DEFAULT_BASE), '/'),
        'key'    => setting('selcom_api_key'),
        'secret' => setting('selcom_api_secret'),
        'vendor' => setting('selcom_vendor'),
        'token'  => setting('selcom_webhook_token'),
    ];
}

function selcom_normalize_msisdn(string $input): string {
    $d = preg_replace('/\D/', '', $input);
    if (strlen($d) === 12 && strpos($d, '255') === 0) return $d;
    if (strlen($d) === 10 && $d[0] === '0') return '255' . substr($d, 1);
    if (strlen($d) === 9) return '255' . $d;
    return $d;
}

function selcom_iso_timestamp(): string {
    $d = new DateTime('now');
    return $d->format('Y-m-d\TH:i:sP');
}

function selcom_build_headers(array $cfg, array $body, array $signed): array {
    $ts = selcom_iso_timestamp();
    $signing = 'timestamp=' . $ts;
    foreach ($signed as $f) {
        $signing .= '&' . $f . '=' . ($body[$f] ?? '');
    }
    $digest = base64_encode(hash_hmac('sha256', $signing, $cfg['secret'], true));
    return [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: SELCOM ' . base64_encode($cfg['key']),
        'Digest-Method: HS256',
        'Digest: ' . $digest,
        'Timestamp: ' . $ts,
        'Signed-Fields: ' . implode(',', $signed),
    ];
}

function selcom_http(string $method, string $url, array $headers, ?array $body = null): array {
    $ch = curl_init($url);
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

function selcom_push_ussd(array $cfg, array $args): array {
    $body = [
        'transid'    => $args['transid'],
        'utilityref' => $args['utilityref'],
        'amount'     => (int)$args['amount'],
        'vendor'     => $cfg['vendor'],
        'msisdn'     => $args['msisdn'],
    ];
    $headers = selcom_build_headers($cfg, $body, ['transid','utilityref','amount','vendor','msisdn']);
    return selcom_http('POST', $cfg['base'] . '/v1/wallet/pushussd', $headers, $body);
}

function selcom_query_status(array $cfg, string $transid): array {
    $headers = selcom_build_headers($cfg, ['transid' => $transid], ['transid']);
    return selcom_http('GET', $cfg['base'] . '/v1/c2b/query-status?transid=' . urlencode($transid), $headers);
}