<?php require_once __DIR__ . '/../includes/auth.php'; require_admin();
$withdraw_msg = null;
$db = db();
$db->exec("CREATE TABLE IF NOT EXISTS `system_adjustments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `amount_tzs` INT NOT NULL,
  `admin` VARCHAR(64) NULL,
  `note` VARCHAR(255) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
$total_deposits = (int) $db->query("SELECT SUM(amount_tzs) FROM payments WHERE status='paid'")->fetchColumn();
$total_reductions = (int) $db->query("SELECT SUM(amount_tzs) FROM system_adjustments")->fetchColumn();
$system_balance = max(0, $total_deposits - $total_reductions);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reduce_system_balance') {
  csrf_check();
  try {
    $amount = (int) ($_POST['amount_tzs'] ?? 0);
    $note = trim($_POST['note'] ?? '');
    if ($amount <= 0) throw new RuntimeException('Kiasi lazima kiwe zaidi ya 0.');
    if ($amount > $system_balance) throw new RuntimeException('Huwezi kupunguza zaidi ya salio la mfumo.');

    $admin_user = $_SESSION['admin_user'] ?? 'admin';
    $stmt = $db->prepare('INSERT INTO system_adjustments (amount_tzs, admin, note) VALUES (?, ?, ?)');
    $stmt->execute([$amount, $admin_user, $note]);

    $withdraw_msg = ['ok', 'Salio la mfumo limepunguzwa kwa mafanikio.'];
    $total_reductions += $amount;
    $system_balance = max(0, $total_deposits - $total_reductions);
  } catch (Throwable $e) {
    $withdraw_msg = ['bad', $e->getMessage()];
  }
}

$rows = $db->query('SELECT * FROM payments ORDER BY created_at DESC LIMIT 200')->fetchAll();
require __DIR__ . '/_layout.php';
?>
<h1 style="margin-top:0;color:var(--gold)">Malipo</h1>
<?php if (!empty($withdraw_msg)): ?><div class="alert <?= $withdraw_msg[0] ?>"><?= e($withdraw_msg[1]) ?></div><?php endif; ?>
<div class="card">
  <h3 style="margin-top:0">Salio la Mfumo</h3>
  <p class="muted">Hii ni jumla ya malipo yote yaliyopokelewa kutoka kwa wateja yaliyohakikisha kama &quot;paid&quot;.</p>
  <p style="font-size:2rem;font-weight:700;margin:0"><?= fmt_tzs($system_balance) ?></p>
  <?php if ($total_reductions > 0): ?>
    <p class="muted" style="margin-top:8px;font-size:13px">Kupungua kwa mfumo: <?= fmt_tzs($total_reductions) ?></p>
  <?php endif; ?>
</div>
<div class="card">
  <h3 style="margin-top:0">Punguza Salio la Mfumo</h3>
  <p class="muted">Hii sehemu inaruhusu kupunguza salio la mfumo kwa ajili ya gharama au kurekebisha taarifa. Haitaongeza salio kwa njia yoyote.</p>
  <form method="post" class="form-grid" style="max-width:520px">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="reduce_system_balance">
    <div>
      <label>Kiasi cha Kupunguza (TZS)</label>
      <input name="amount_tzs" type="number" min="1" max="<?= e($system_balance) ?>" required>
    </div>
    <div>
      <label>Maelezo (hiari)</label>
      <input name="note" placeholder="Sababu / referencia (hiari)">
    </div>
    <div>
      <button class="btn btn-outline" type="submit">Punguza Salio la Mfumo</button>
    </div>
  </form>
</div>
<div class="card" style="overflow-x:auto">
  <table class="table">
    <thead><tr><th>Tarehe</th><th>Reference</th><th>Simu</th><th>Aina</th><th>Kiasi</th><th>Hali</th><th>Provider</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $p):
      $chip = $p['status']==='paid' ? 'chip-ok' : ($p['status']==='failed' ? 'chip-bad' : 'chip-warn'); ?>
      <tr>
        <td style="font-size:12px"><?= e($p['created_at']) ?></td>
        <td><code style="font-size:11px"><?= e($p['reference']) ?></code></td>
        <td><?= e($p['msisdn']) ?></td>
        <td><?= e($p['item_type']) ?></td>
        <td><?= fmt_tzs((int)$p['amount_tzs']) ?></td>
        <td><span class="chip <?= $chip ?>"><?= e($p['status']) ?></span></td>
        <?php
          $provider = 'Selcom';
          if (($p['selcom_message'] ?? '') === 'grebo') $provider = 'Grebo';
          elseif (!empty($p['selcom_reference']) && strpos($p['selcom_reference'], 'tx_') === 0) $provider = 'Grebo';
        ?>
        <td style="font-size:11px;color:var(--muted)"><?= e($provider) ?> <?= e($p['selcom_resultcode'] ?? '') ?> <?= e($p['selcom_message'] ?? '') ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="7" class="muted" style="text-align:center;padding:24px">Hakuna malipo bado.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php require __DIR__ . '/_layout_end.php'; ?>