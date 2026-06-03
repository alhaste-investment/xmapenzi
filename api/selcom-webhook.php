<?php
require_once __DIR__ . '/../includes/db.php';

$token = $_GET['token'] ?? ($_SERVER['HTTP_X_WEBHOOK_TOKEN'] ?? '');
$expected = setting('selcom_webhook_token');
if (!$expected || !hash_equals($expected, (string)$token)) {
    http_response_code(401); die('Unauthorized');
}

$body = file_get_contents('php://input');
$payload = json_decode($body, true);
if (!is_array($payload)) { parse_str($body, $payload); }
if (!is_array($payload)) $payload = $_POST;

$reference = $payload['transid'] ?? ($payload['reference'] ?? ($payload['utilityref'] ?? ''));
$code = (string)($payload['resultcode'] ?? ($payload['result_code'] ?? ''));
$result = strtoupper((string)($payload['result'] ?? ''));
$msg = $payload['message'] ?? null;

if (!$reference) { http_response_code(400); die('Missing reference'); }

$s = db()->prepare('SELECT id, status FROM payments WHERE reference = ?');
$s->execute([$reference]); $p = $s->fetch();
if (!$p) { http_response_code(404); die('Unknown'); }
if ($p['status'] === 'paid') { echo 'ok'; exit; }

if ($code === '000' || $result === 'SUCCESS') {
    $token = bin2hex(random_bytes(16));
    db()->prepare('UPDATE payments SET status="paid", paid_at=NOW(), selcom_resultcode=?, selcom_message=?, unlock_token=? WHERE reference=?')
        ->execute([$code, $msg, $token, $reference]);
        // credit user wallet
        // make sure wallets table exists (for older installs)
        db()->exec("CREATE TABLE IF NOT EXISTS `wallets` (
            `msisdn` VARCHAR(20) NOT NULL,
            `balance_tzs` INT NOT NULL DEFAULT 0,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`msisdn`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        // get payment details
        $s2 = db()->prepare('SELECT msisdn, amount_tzs FROM payments WHERE reference = ?'); $s2->execute([$reference]); $pd = $s2->fetch();
        if ($pd) {
            db()->prepare('INSERT INTO wallets (msisdn, balance_tzs) VALUES (?, ?) ON DUPLICATE KEY UPDATE balance_tzs = balance_tzs + VALUES(balance_tzs)')
                ->execute([$pd['msisdn'], (int)$pd['amount_tzs']]);
        }
} elseif ($code && !in_array($code, ['111','927','999'], true)) {
    db()->prepare('UPDATE payments SET status="failed", selcom_resultcode=?, selcom_message=? WHERE reference=?')
        ->execute([$code, $msg, $reference]);
}
echo 'ok';