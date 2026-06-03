<?php
require_once __DIR__ . '/db.php';

function start_session_once(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_start();
    }
}

function admin_login(string $username, string $password): bool {
    $stmt = db()->prepare('SELECT id, password_hash FROM admins WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $row = $stmt->fetch();
    if (!$row) return false;
    if (!password_verify($password, $row['password_hash'])) return false;
    start_session_once();
    $_SESSION['admin_id'] = (int)$row['id'];
    $_SESSION['admin_user'] = $username;
    return true;
}

function admin_logout(): void {
    start_session_once();
    $_SESSION = [];
    session_destroy();
}

function is_admin(): bool {
    start_session_once();
    return !empty($_SESSION['admin_id']);
}

function require_admin(): void {
    if (!is_admin()) {
        header('Location: ' . SITE_URL . '/admin/login.php');
        exit;
    }
}

function csrf_token(): string {
    start_session_once();
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function csrf_check(): void {
    start_session_once();
    $t = $_POST['csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf'] ?? '', (string)$t)) {
        http_response_code(403); die('Bad CSRF token');
    }
}