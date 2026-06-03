<?php require_once __DIR__ . '/_layout.php';
$stats = [
  'videos'   => (int)db()->query('SELECT COUNT(*) FROM videos')->fetchColumn(),
  'statuses' => (int)db()->query('SELECT COUNT(*) FROM statuses')->fetchColumn(),
  'paid'     => (int)db()->query("SELECT COUNT(*) FROM payments WHERE status='paid'")->fetchColumn(),
  // revenue = paid payments minus withdrawals (admin payouts)
  'revenue'  => (int)db()->query("SELECT COALESCE((SELECT SUM(amount_tzs) FROM payments WHERE status='paid'),0) - COALESCE((SELECT SUM(amount_tzs) FROM withdrawals),0)")->fetchColumn(),
];
?>
<h1 style="margin-top:0;color:var(--gold)">Dashboard</h1>
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-bottom:24px">
  <div class="card"><p class="muted" style="margin:0">Video</p><p style="font-size:1.8rem;margin:6px 0 0;font-weight:700"><?= $stats['videos'] ?></p></div>
  <div class="card"><p class="muted" style="margin:0">Status</p><p style="font-size:1.8rem;margin:6px 0 0;font-weight:700"><?= $stats['statuses'] ?></p></div>
  <div class="card"><p class="muted" style="margin:0">Malipo Yamekamilika</p><p style="font-size:1.8rem;margin:6px 0 0;font-weight:700"><?= $stats['paid'] ?></p></div>
  <div class="card"><p class="muted" style="margin:0">Mapato</p><p style="font-size:1.8rem;margin:6px 0 0;font-weight:700;color:var(--gold)"><?= fmt_tzs($stats['revenue']) ?></p></div>
</div>
<div class="card">
  <h3 style="margin-top:0">Karibu</h3>
  <p class="muted">Tumia menu kushoto kusimamia video, status, malipo na Selcom settings.</p>
</div>
<?php require __DIR__ . '/_layout_end.php'; ?>