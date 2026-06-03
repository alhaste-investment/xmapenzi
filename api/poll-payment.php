<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/selcom.php';

header('Content-Type: application/json');
$in = require_post();
$reference = $in['reference'] ?? '';
if (!$reference) json_out(['error' => 'Bad reference'], 400);

$s = db()->prepare('SELECT * FROM payments WHERE reference = ?');
$s->execute([$reference]); $p = $s->fetch();
if (!$p) json_out(['error' => 'Not found'], 404);

if ($p['status'] === 'paid') json_out(['status' => 'paid', 'unlock_token' => $p['unlock_token']]);
if (in_array($p['status'], ['failed','cancelled'], true)) json_out(['status' => $p['status']]);

// If this payment was initiated via Grebo we rely on webhooks; return pending
if (($p['selcom_message'] ?? '') === 'grebo') {
    json_out(['status' => 'pending']);
}

// Query Selcom for others
$cfg = selcom_config();
$res = selcom_query_status($cfg, $reference);
$code = (string)($res['json']['resultcode'] ?? $res['json']['result_code'] ?? '');
$result = strtoupper((string)($res['json']['result'] ?? ''));
$msg = $res['json']['message'] ?? null;

if ($code === '000' || $result === 'SUCCESS') {
    $token = bin2hex(random_bytes(16));
    db()->prepare('UPDATE payments SET status="paid", paid_at=NOW(), selcom_resultcode=?, selcom_message=?, unlock_token=? WHERE reference=?')
        ->execute([$code, $msg, $token, $reference]);
    json_out(['status' => 'paid', 'unlock_token' => $token]);
}
if ($code && !in_array($code, ['111','927','999'], true)) {
    db()->prepare('UPDATE payments SET status="failed", selcom_resultcode=?, selcom_message=? WHERE reference=?')
        ->execute([$code, $msg, $reference]);
    json_out(['status' => 'failed']);
}
json_out(['status' => 'pending']);