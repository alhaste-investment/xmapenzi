<?php
require_once __DIR__ . '/includes/db.php';

$videos = db()->query("SELECT id, title, category, thumbnail_url, views, is_paid, price_tzs
    FROM videos WHERE is_active = 1 ORDER BY created_at DESC")->fetchAll();

$statuses = db()->query("SELECT id, name, phone, whatsapp, photo_url, subtitle, description, call_price_tzs, chat_price_tzs
    FROM statuses WHERE is_active = 1 ORDER BY sort_order ASC, created_at DESC")->fetchAll();
?>
<!doctype html>
<html lang="sw">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>Xmapenzi — Burudani, Status na Video za Mapenzi</title>
<meta name="description" content="Tazama video za mapenzi, piga simu na chat na masista wapendwa. Malipo salama kupitia Selcom.">
<link rel="icon" type="image/png" href="assets/logo.png">
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div id="splash" class="xmp-splash">
  <div class="xmp-splash-inner">
    <div class="xmp-splash-logo">
      <span class="xmp-splash-ring"></span>
      <img src="assets/logo.png" alt="">
    </div>
    <div class="xmp-splash-text">XMAPENZI</div>
    <div class="xmp-splash-bar"></div>
  </div>
</div>

<header class="header">
  <div class="container header-inner">
    <a href="/" class="brand">
      <img src="assets/logo.png" alt="Xmapenzi" width="40" height="40">
      <span>Xmapenzi</span>
    </a>
    <div class="search">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
      <input id="search" type="search" placeholder="Tafuta video...">
    </div>
  </div>
</header>

<main class="container">
  <section>
    <h2 class="section-title"><span class="dot"></span> Simu Status</h2>
    <?php if (!$statuses): ?>
      <p class="muted">Hakuna status kwa sasa.</p>
    <?php else: ?>
      <div class="marquee-wrap">
        <div class="marquee" id="marquee">
          <?php for ($pass = 0; $pass < 2; $pass++): foreach ($statuses as $s): ?>
            <article class="status-card">
              <div class="status-head">
                <div class="avatar">
                  <?php if ($s['photo_url']): ?>
                    <img src="<?= e($s['photo_url']) ?>" alt="<?= e($s['name']) ?>" loading="lazy">
                  <?php else: ?><span>💋</span><?php endif; ?>
                </div>
                <div class="status-meta">
                  <p class="status-name"><?= e($s['name']) ?></p>
                  <p class="status-sub"><?= e($s['subtitle'] ?: 'Online') ?></p>
                </div>
              </div>
              <?php if ($s['description']): ?>
                <p class="status-desc"><?= e($s['description']) ?></p>
              <?php endif; ?>
              <div class="status-actions">
                <button class="btn btn-secondary"
                  data-action="pay"
                  data-type="status_call"
                  data-id="<?= e($s['id']) ?>"
                  data-amount="<?= (int)$s['call_price_tzs'] ?>"
                  data-title="Piga simu — <?= e($s['name']) ?>"
                  data-wa="<?= e(preg_replace('/\D/','', $s['whatsapp'] ?: $s['phone'])) ?>"
                  data-name="<?= e($s['name']) ?>"
                  data-kind="call">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                  <?= fmt_tzs((int)$s['call_price_tzs']) ?>
                </button>
                <button class="btn btn-gold"
                  data-action="pay"
                  data-type="status_chat"
                  data-id="<?= e($s['id']) ?>"
                  data-amount="<?= (int)$s['chat_price_tzs'] ?>"
                  data-title="Chat — <?= e($s['name']) ?>"
                  data-wa="<?= e(preg_replace('/\D/','', $s['whatsapp'] ?: $s['phone'])) ?>"
                  data-name="<?= e($s['name']) ?>"
                  data-kind="chat">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                  <?= fmt_tzs((int)$s['chat_price_tzs']) ?>
                </button>
              </div>
            </article>
          <?php endforeach; endfor; ?>
        </div>
      </div>
    <?php endif; ?>
  </section>

  <section>
    <h2 class="section-title"><span class="dot"></span> Video Mpya</h2>
    <div class="grid" id="video-grid">
      <?php foreach ($videos as $v): ?>
            <article class="video-card"
          data-title="<?= e(strtolower($v['title'] . ' ' . $v['category'])) ?>"
          data-action="open-video"
          data-id="<?= e($v['id']) ?>"
          data-paid="<?= (int)$v['is_paid'] ?>"
          data-price="<?= (int)$v['price_tzs'] ?>"
          data-name="<?= e($v['title']) ?>">
          <div class="thumb">
            <?php $thumbSrc = $v['thumbnail_url'] ?: (SITE_URL . '/api/video-thumb.php?id=' . $v['id']); ?>
            <img src="<?= e($thumbSrc) ?>" alt="<?= e($v['title']) ?>" loading="lazy"
              onload="this.nextElementSibling.style.display='none'"
              onerror="this.style.display='none'; this.nextElementSibling.style.display='grid'">
            <div class="thumb-fallback" style="display:grid">🎬</div>
            <div class="thumb-overlay">
              <?php if ((int)$v['is_paid']): ?>
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
              <?php else: ?>
                <svg width="40" height="40" viewBox="0 0 24 24" fill="currentColor"><polygon points="6 4 20 12 6 20"/></svg>
              <?php endif; ?>
            </div>
            <span class="badge <?= (int)$v['is_paid'] ? 'badge-gold' : 'badge-primary' ?>">
              <?= (int)$v['is_paid'] ? fmt_tzs((int)$v['price_tzs']) : 'BURE' ?>
            </span>
          </div>
          <div class="video-meta">
            <p class="video-title"><?= e($v['title']) ?></p>
            <p class="video-sub"><?= e($v['category']) ?> · <?= (int)$v['views'] ?> views</p>
          </div>
        </article>
      <?php endforeach; ?>
      <?php if (!$videos): ?><p class="muted">Hakuna video.</p><?php endif; ?>
    </div>
  </section>
</main>

<!-- Footer hidden for WebView use in mobile app -->
<!-- <footer class="footer">© <?= date('Y') ?> Xmapenzi · Malipo yanafanyika kwa usalama kupitia Selcom Mobile</footer> -->

<!-- Payment modal -->
<div id="pay-modal" class="modal" hidden>
  <div class="modal-card">
    <div class="modal-head">
      <div class="modal-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
      <div>
        <h3 id="pay-title">Malipo</h3>
        <p class="modal-sub">Malipo salama · Selcom Mobile</p>
      </div>
      <button class="modal-close" id="pay-x" type="button" aria-label="Funga">×</button>
    </div>
    <div class="amount-box">
      <p class="amount-label">Kiasi cha kulipa</p>
      <p class="amount-value"><span id="pay-amount">0</span> <small>TZS</small></p>
    </div>
    <div class="modal-body">
      <!-- INPUT -->
      <div id="stage-input">
        <label for="msisdn">Namba ya simu inayolipa</label>
        <div class="input-wrap">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12" y2="18"/></svg>
          <input id="msisdn" inputmode="tel" placeholder="07XXXXXXXX">
        </div>
        <p class="hint">M-Pesa · Tigo Pesa · Airtel Money · Halopesa</p>
        <button id="pay-submit" class="btn btn-gold btn-block">
          <span class="btn-text">Tuma ombi la malipo</span>
          <span class="btn-spin" hidden></span>
        </button>
      </div>
      <!-- PENDING -->
      <div id="stage-pending" hidden class="center">
        <div class="big-spinner"><span></span></div>
        <p class="status-msg"><strong>Inasubiri uthibitisho wa malipo…</strong></p>
        <p id="pending-msg" class="muted">Angalia simu yako, ingiza PIN kukamilisha malipo.</p>
        <div class="info-row"><span>Muda: <code id="timer">00:00</code></span><span>Ref: <code id="ref"></code></span></div>
        <button id="pay-cancel" class="btn btn-outline btn-block">Ghairi malipo</button>
      </div>
      <!-- SUCCESS -->
      <div id="stage-success" hidden class="center">
        <div class="circle-check ok pop">
          <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
        </div>
        <p class="result ok">Malipo yamekamilika!</p>
        <p class="muted">Asante. Unaelekezwa sasa…</p>
      </div>
      <!-- FAILED -->
      <div id="stage-failed" hidden class="center">
        <div class="circle-check bad pop">
          <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </div>
        <p class="result bad">Malipo hayakukamilika</p>
        <p id="fail-msg" class="muted"></p>
        <div class="row-2">
          <button id="pay-close" class="btn btn-outline">Funga</button>
          <button id="pay-retry" class="btn btn-gold">Jaribu tena</button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Video player -->
<div id="video-modal" class="modal" hidden>
  <div class="modal-card video-card-modal">
    <div class="modal-head">
      <h3 id="video-title">Video</h3>
      <button class="modal-close" id="video-x" type="button" aria-label="Funga">×</button>
    </div>
    <div class="video-wrap">
      <video id="video-el" controls autoplay playsinline controlsList="nodownload noplaybackrate noremoteplayback" disablepictureinpicture oncontextmenu="return false"></video>
    </div>
  </div>
</div>

<script>window.XMP = { siteUrl: <?= json_encode(SITE_URL) ?> };</script>
<script src="assets/app.js"></script>
</body>
</html>