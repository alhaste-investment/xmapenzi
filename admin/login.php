<?php
require_once __DIR__ . '/../includes/auth.php';

if (is_admin()) { header('Location: index.php'); exit; }

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim($_POST['username'] ?? '');
    $p = $_POST['password'] ?? '';
    if (admin_login($u, $p)) { header('Location: index.php'); exit; }
    $err = 'Username au password si sahihi.';
}
?>
<!doctype html><html lang="sw"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Login · Xmapenzi</title>
<link rel="icon" type="image/png" href="../assets/logo.png">
<link rel="stylesheet" href="../assets/style.css">
</head><body class="login-shell">
<div class="login-card card">
  <div style="text-align:center;margin-bottom:16px">
    <img src="../assets/logo.png" alt="" width="64" height="64" style="border-radius:14px;margin:0 auto 8px">
    <h2 style="margin:0;color:var(--gold)">Admin Login</h2>
  </div>
  <?php if ($err): ?><div class="alert bad"><?= e($err) ?></div><?php endif; ?>
  <form method="post" class="form-grid">
    <div><label>Username</label><input name="username" required autofocus></div>
    <div><label>Password</label><input name="password" type="password" required></div>
    <button class="btn btn-gold btn-block" type="submit">Ingia</button>
  </form>
</div>
</body></html>