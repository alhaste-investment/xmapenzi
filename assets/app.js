(function(){
'use strict';

// ---- Splash ----
window.addEventListener('load', () => {
  setTimeout(() => {
    const el = document.getElementById('splash');
    if (el) { el.classList.add('hide'); setTimeout(()=>el.remove(), 600); }
  }, 1500);
});

// ---- Local unlocks (14 days) ----
const UNLOCK_KEY = 'xmp_unlocks';
const TWO_WEEKS = 14*24*60*60*1000;
function readUnlocks(){ try { return JSON.parse(localStorage.getItem(UNLOCK_KEY)||'{}'); } catch { return {}; } }
function writeUnlocks(s){ localStorage.setItem(UNLOCK_KEY, JSON.stringify(s)); }
function getUnlock(id){
  const s = readUnlocks(); const u = s[id];
  if (!u) return null;
  if (u.expiresAt < Date.now()) { delete s[id]; writeUnlocks(s); return null; }
  return u.reference;
}
function saveUnlock(id, ref){
  const s = readUnlocks();
  s[id] = { reference: ref, expiresAt: Date.now() + TWO_WEEKS };
  writeUnlocks(s);
}

// ---- Toast ----
window.toast = function(msg, kind){
  const t = document.createElement('div');
  t.className = 'toast' + (kind ? ' '+kind : '');
  t.textContent = msg;
  document.body.appendChild(t);
  setTimeout(()=>t.remove(), 3500);
}

// ---- Search ----
const search = document.getElementById('search');
if (search){
  search.addEventListener('input', e => {
    const q = e.target.value.trim().toLowerCase();
    document.querySelectorAll('.video-card').forEach(c => {
      c.style.display = (!q || c.dataset.title.includes(q)) ? '' : 'none';
    });
  });
}

// ---- Modal helpers ----
const payModal   = document.getElementById('pay-modal');
const videoModal = document.getElementById('video-modal');
const stages = ['input','pending','success','failed'].reduce((a,k) => {
  const el = document.getElementById('stage-'+k);
  if (el) a[k] = el;
  return a;
}, {});

let payState = { itemType:'', itemId:'', amount:0, title:'', onPaid:null, polling:false, stop:false, timer:null };

function showStage(name){
  for (const k in stages) {
    if (stages[k]) stages[k].hidden = (k !== name);
  }
}
function openPay(opts){
  if (!payModal) return;
  payState = { ...payState, ...opts, polling:false, stop:false };
  const payTitle = document.getElementById('pay-title');
  const payAmount = document.getElementById('pay-amount');
  const msisdn = document.getElementById('msisdn');
  const pendingMsg = document.getElementById('pending-msg');
  const failMsg = document.getElementById('fail-msg');

  if (payTitle) payTitle.textContent = opts.title;
  if (payAmount) payAmount.textContent = new Intl.NumberFormat('sw-TZ').format(opts.amount);
  if (msisdn) msisdn.value = '';
  if (pendingMsg) pendingMsg.textContent = 'Angalia simu yako, ingiza PIN kukamilisha malipo.';
  if (failMsg) failMsg.textContent = '';
  showStage('input');
  payModal.hidden = false;
}
function closePay(force){
  if (!payModal) return;
  // Block close while polling unless via cancel/X
  if (payState.polling && !force) { toast("Bonyeza 'Ghairi' kufunga.", 'bad'); return; }
  payState.stop = true;
  if (payState.timer) { clearInterval(payState.timer); payState.timer = null; }
  payModal.hidden = true;
}

// Click on backdrop should NOT close while pending. Always require X/Cancel.
if (payModal) {
  payModal.addEventListener('click', e => {
    if (e.target === payModal) {
      if (payState.polling) toast("Bonyeza 'Ghairi' kufunga.", 'bad');
    }
  });
}
const payX = document.getElementById('pay-x');
if (payX) payX.addEventListener('click', () => closePay(true));
const payCancel = document.getElementById('pay-cancel');
if (payCancel) payCancel.addEventListener('click', () => closePay(true));
const payClose = document.getElementById('pay-close');
if (payClose) payClose.addEventListener('click', () => closePay(true));
const payRetry = document.getElementById('pay-retry');
if (payRetry) payRetry.addEventListener('click', () => showStage('input'));

document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    if (payModal && !payModal.hidden && !payState.polling) closePay(true);
    if (videoModal && !videoModal.hidden) closeVideo();
  }
});

// ---- Pay submit ----
const paySubmit = document.getElementById('pay-submit');
if (paySubmit) paySubmit.addEventListener('click', async () => {
  const phone = document.getElementById('msisdn').value.replace(/\s/g,'');
  if (!/^(0\d{9}|255\d{9}|\+255\d{9})$/.test(phone)) { toast('Weka namba sahihi (07XXXXXXXX)', 'bad'); return; }
  const btn = document.getElementById('pay-submit');
  btn.disabled = true;
  btn.querySelector('.btn-text').hidden = true;
  btn.querySelector('.btn-spin').hidden = false;
  try {
    const res = await fetch('api/initiate-payment.php', {
      method:'POST', headers:{'Content-Type':'application/json'},
      body: JSON.stringify({ itemType: payState.itemType, itemId: payState.itemId, msisdn: phone })
    });
    const j = await res.json();
    if (!res.ok) throw new Error(j.error || 'Imeshindikana');
    payState.reference = j.reference;
    document.getElementById('ref').textContent = j.reference.slice(-10);
    document.getElementById('pending-msg').textContent = j.message || 'Angalia simu yako, ingiza PIN kukamilisha malipo.';
    showStage('pending');
    startTimer();
    pollLoop(j.reference);
  } catch(e) {
    document.getElementById('fail-msg').textContent = e.message || 'Hitilafu.';
    showStage('failed');
  } finally {
    btn.disabled = false;
    btn.querySelector('.btn-text').hidden = false;
    btn.querySelector('.btn-spin').hidden = true;
  }
});

function startTimer(){
  let s = 0;
  if (payState.timer) clearInterval(payState.timer);
  document.getElementById('timer').textContent = '00:00';
  payState.timer = setInterval(() => {
    s++;
    document.getElementById('timer').textContent =
      String(Math.floor(s/60)).padStart(2,'0') + ':' + String(s%60).padStart(2,'0');
  }, 1000);
}

async function pollLoop(ref){
  payState.polling = true;
  payState.stop = false;
  for (let i = 0; i < 40 && !payState.stop; i++) {
    await new Promise(r => setTimeout(r, 4000));
    if (payState.stop) break;
    try {
      const r = await fetch('api/poll-payment.php', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ reference: ref })
      });
      const j = await r.json();
      if (j.status === 'paid') {
        payState.polling = false;
        if (payState.timer) clearInterval(payState.timer);
        showStage('success');
        setTimeout(() => {
          if (payState.onPaid) payState.onPaid(ref);
          payModal.hidden = true;
        }, 1400);
        return;
      }
      if (j.status === 'failed' || j.status === 'cancelled') {
        payState.polling = false;
        const failMsg = document.getElementById('fail-msg');
        if (failMsg) failMsg.textContent = 'Malipo hayakukamilika au yamekataliwa. Tafadhali jaribu tena.';
        showStage('failed');
        return;
      }
    } catch{}
  }
  if (!payState.stop) {
    payState.polling = false;
    const failMsg = document.getElementById('fail-msg');
    if (failMsg) failMsg.textContent = 'Muda umeisha bila kupokea uthibitisho. Jaribu tena.';
    showStage('failed');
  }
}

// ---- Pay buttons on status cards ----
function toWa(num){
  const d = (num||'').replace(/\D/g,'');
  return d.startsWith('0') ? '255' + d.slice(1) : d;
}
document.addEventListener('click', e => {
  const el = e.target.closest('[data-action="pay"]');
  if (!el) return;
  e.preventDefault();
  const phone = toWa(el.dataset.wa);
  const kind = el.dataset.kind;
  const name = el.dataset.name;
  openPay({
    itemType: el.dataset.type,
    itemId: el.dataset.id,
    amount: parseInt(el.dataset.amount,10) || 0,
    title: el.dataset.title,
    onPaid: () => {
      if (kind === 'call') {
        window.open('tel:' + phone, '_blank');
      } else {
        const msg = encodeURIComponent('Habari ' + name + ', nimelipia chat.');
        window.open('https://wa.me/' + phone + '?text=' + msg, '_blank');
      }
    }
  });
});

// ---- Video play ----
const videoEl = document.getElementById('video-el');
const videoTitle = document.getElementById('video-title');
function openVideo(url, title){
  if (!videoEl || !videoTitle || !videoModal) return;
  videoTitle.textContent = title;
  videoEl.src = url;
  videoModal.hidden = false;
}
function closeVideo(){
  if (!videoEl || !videoModal) return;
  videoEl.pause(); videoEl.removeAttribute('src'); videoEl.load();
  videoModal.hidden = true;
}
const videoClose = document.getElementById('video-x');
if (videoClose) videoClose.addEventListener('click', closeVideo);
if (videoModal) videoModal.addEventListener('click', e => { /* outside click: do nothing */ });

async function fetchVideoUrl(id, ref){
  const url = 'api/video-url.php?id=' + encodeURIComponent(id) + (ref ? '&ref=' + encodeURIComponent(ref) : '');
  const r = await fetch(url); const j = await r.json();
  if (!r.ok) throw new Error(j.error || 'Imeshindikana kupata video');
  return j;
}

document.addEventListener('click', async e => {
  const el = e.target.closest('[data-action="open-video"]');
  if (!el) return;
  const id = el.dataset.id;
  const paid = el.dataset.paid === '1';
  const price = parseInt(el.dataset.price,10) || 0;
  const name = el.dataset.name;

  if (!paid) {
    try { const r = await fetchVideoUrl(id); openVideo(r.url, r.title); }
    catch(err){ toast(err.message, 'bad'); }
    return;
  }
  const existing = getUnlock(id);
  if (existing) {
    try { const r = await fetchVideoUrl(id, existing); openVideo(r.url, r.title); return; }
    catch{ /* expired server-side, fall through to pay */ }
  }
  openPay({
    itemType:'video', itemId:id, amount:price, title:name,
    onPaid: async (ref) => {
      saveUnlock(id, ref);
      try { const r = await fetchVideoUrl(id, ref); openVideo(r.url, r.title); }
      catch(err){ toast(err.message, 'bad'); }
    }
  });
});

})();