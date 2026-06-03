<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();

function nav_link($file, $label){
    $active = basename($_SERVER['PHP_SELF']) === $file ? ' active' : '';
    echo '<a href="' . $file . '" class="' . trim($active) . '">' . htmlspecialchars($label) . '</a>';
}
?>
<!doctype html>
<html lang="sw"><head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin · Xmapenzi</title>
<link rel="icon" type="image/png" href="../assets/logo.png">
<link rel="stylesheet" href="../assets/style.css?v=2">
</head><body>
<div class="admin-shell">
  <aside class="admin-side">
    <a href="/" class="brand">
      <img src="../assets/logo.png" width="40" height="40" alt="">
      <span>Xmapenzi</span>
    </a>
    <?php nav_link('index.php', '📊 Dashboard'); ?>
    <?php nav_link('videos.php', '🎬 Video'); ?>
    <?php nav_link('statuses.php', '💋 Status'); ?>
    <?php nav_link('payments.php', '💰 Malipo'); ?>
    <?php nav_link('settings.php', '⚙️ Settings'); ?>
    <a href="logout.php" style="color:var(--danger);margin-top:24px">↪ Logout</a>
  </aside>
  <main class="admin-main">