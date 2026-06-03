<?php require_once __DIR__ . '/../includes/auth.php'; require_admin();
require_once __DIR__ . '/_upload.php';

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    try {
        $action = $_POST['action'] ?? '';
        if ($action === 'delete') {
            db()->prepare('DELETE FROM statuses WHERE id = ?')->execute([$_POST['id']]);
            $msg = ['ok','Imefutwa.'];
        } else {
            $photo = admin_handle_upload('photo_file', 'status-photos', MAX_IMAGE_MB, ['jpg','jpeg','png','webp']);
            $data = [
              ':name'=>trim($_POST['name'] ?? ''),
              ':phone'=>trim($_POST['phone'] ?? ''),
              ':whatsapp'=>trim($_POST['whatsapp'] ?? '') ?: null,
              ':photo_url'=>$photo ?: trim($_POST['photo_url'] ?? ''),
              ':subtitle'=>trim($_POST['subtitle'] ?? '') ?: null,
              ':description'=>trim($_POST['description'] ?? '') ?: null,
              ':call_price_tzs'=>(int)($_POST['call_price_tzs'] ?? 1000),
              ':chat_price_tzs'=>(int)($_POST['chat_price_tzs'] ?? 500),
              ':is_active'=>!empty($_POST['is_active']) ? 1 : 0,
              ':sort_order'=>(int)($_POST['sort_order'] ?? 0),
            ];
            if ($action === 'update' && !empty($_POST['id'])) {
                $sql = 'UPDATE statuses SET name=:name, phone=:phone, whatsapp=:whatsapp,
                  photo_url=COALESCE(NULLIF(:photo_url,""), photo_url),
                  subtitle=:subtitle, description=:description, call_price_tzs=:call_price_tzs,
                  chat_price_tzs=:chat_price_tzs, is_active=:is_active, sort_order=:sort_order WHERE id=:id';
                $data[':id']=$_POST['id'];
                db()->prepare($sql)->execute($data);
                $msg=['ok','Imehifadhiwa.'];
            } else {
                if (!$data[':name'] || !$data[':phone']) throw new RuntimeException('Name na phone ni lazima.');
                $sql = 'INSERT INTO statuses (id,name,phone,whatsapp,photo_url,subtitle,description,call_price_tzs,chat_price_tzs,is_active,sort_order)
                  VALUES (:id,:name,:phone,:whatsapp,:photo_url,:subtitle,:description,:call_price_tzs,:chat_price_tzs,:is_active,:sort_order)';
                $data[':id']=uuidv4();
                db()->prepare($sql)->execute($data);
                $msg=['ok','Imeongezwa.'];
            }
        }
    } catch (Throwable $e) { $msg=['bad',$e->getMessage()]; }
}

$edit=null;
if (!empty($_GET['edit'])) { $s=db()->prepare('SELECT * FROM statuses WHERE id=?'); $s->execute([$_GET['edit']]); $edit=$s->fetch(); }
$rows = db()->query('SELECT * FROM statuses ORDER BY sort_order ASC, created_at DESC')->fetchAll();
require __DIR__ . '/_layout.php';
?>
<h1 style="margin-top:0;color:var(--gold)">Status</h1>
<?php if ($msg): ?><div class="alert <?= $msg[0] ?>"><?= e($msg[1]) ?></div><?php endif; ?>

<div class="card">
  <h3 style="margin-top:0"><?= $edit ? 'Hariri Status' : 'Ongeza Status Mpya' ?></h3>
  <form method="post" enctype="multipart/form-data" class="form-grid">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="<?= $edit ? 'update' : 'create' ?>">
    <?php if ($edit): ?><input type="hidden" name="id" value="<?= e($edit['id']) ?>"><?php endif; ?>
    <div class="row-2">
      <div><label>Jina</label><input name="name" required value="<?= e($edit['name'] ?? '') ?>"></div>
      <div><label>Phone (WhatsApp)</label><input name="phone" required placeholder="0712345678" value="<?= e($edit['phone'] ?? '') ?>"></div>
    </div>
    <div class="row-2">
      <div><label>WhatsApp (kama tofauti)</label><input name="whatsapp" value="<?= e($edit['whatsapp'] ?? '') ?>"></div>
      <div><label>Sort order</label><input type="number" name="sort_order" value="<?= (int)($edit['sort_order'] ?? 0) ?>"></div>
    </div>
    <div><label>Subtitle</label><input name="subtitle" value="<?= e($edit['subtitle'] ?? '') ?>" placeholder="Online"></div>
    <div><label>Description</label><textarea name="description"><?= e($edit['description'] ?? '') ?></textarea></div>
    <div class="row-2">
      <div><label>Photo URL (au pakia)</label><input name="photo_url" value="<?= e($edit['photo_url'] ?? '') ?>"></div>
      <div><label>Pakia picha</label><input type="file" name="photo_file" accept="image/*"></div>
    </div>
    <div class="row-2">
      <div><label>Bei ya Simu (TZS)</label><input type="number" name="call_price_tzs" value="<?= (int)($edit['call_price_tzs'] ?? 1000) ?>"></div>
      <div><label>Bei ya Chat (TZS)</label><input type="number" name="chat_price_tzs" value="<?= (int)($edit['chat_price_tzs'] ?? 500) ?>"></div>
    </div>
    <div><label><input type="checkbox" name="is_active" value="1" <?= !$edit || !empty($edit['is_active']) ? 'checked' : '' ?>> Inaonekana</label></div>
    <button class="btn btn-gold" type="submit"><?= $edit ? 'Hifadhi' : 'Ongeza' ?></button>
    <?php if ($edit): ?><a class="btn btn-outline" href="statuses.php">Ghairi</a><?php endif; ?>
  </form>
</div>

<div class="card" style="overflow-x:auto">
  <h3 style="margin-top:0">Orodha (<?= count($rows) ?>)</h3>
  <table class="table">
    <thead><tr><th>Picha</th><th>Jina</th><th>Phone</th><th>Simu</th><th>Chat</th><th>Hali</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $s): ?>
      <tr>
        <td><?php if ($s['photo_url']): ?><img src="<?= e($s['photo_url']) ?>" style="width:40px;height:40px;border-radius:50%;object-fit:cover"><?php else: ?>—<?php endif; ?></td>
        <td><strong><?= e($s['name']) ?></strong><br><span class="muted" style="font-size:11px"><?= e($s['subtitle'] ?? '') ?></span></td>
        <td><?= e($s['phone']) ?></td>
        <td><?= fmt_tzs((int)$s['call_price_tzs']) ?></td>
        <td><?= fmt_tzs((int)$s['chat_price_tzs']) ?></td>
        <td><?= $s['is_active'] ? '<span class="chip chip-ok">Hai</span>' : '<span class="chip chip-bad">Off</span>' ?></td>
        <td style="white-space:nowrap">
          <a class="btn btn-outline" href="?edit=<?= e($s['id']) ?>" style="padding:6px 10px;font-size:12px">Hariri</a>
          <form method="post" style="display:inline" onsubmit="return confirm('Futa?')">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= e($s['id']) ?>">
            <button class="btn btn-outline" style="padding:6px 10px;font-size:12px;color:var(--danger)">Futa</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require __DIR__ . '/_layout_end.php'; ?>