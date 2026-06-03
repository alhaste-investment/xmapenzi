<?php require_once __DIR__ . '/../includes/auth.php'; require_admin();
require_once __DIR__ . '/_upload.php';

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    try {
        $action = $_POST['action'] ?? '';
        if ($action === 'delete') {
            db()->prepare('DELETE FROM videos WHERE id = ?')->execute([$_POST['id']]);
            $msg = ['ok','Imefutwa.'];
        } else {
            $thumb_url = admin_handle_upload('thumbnail_file', 'thumbnails', MAX_IMAGE_MB, ['jpg','jpeg','png','webp']);
            $video_url = admin_handle_upload('video_file', 'videos', MAX_VIDEO_MB, ['mp4','mov','webm','m4v']);
            $thumb = $thumb_url ?: trim($_POST['thumbnail_url'] ?? '');
            $vid   = $video_url ?: trim($_POST['video_url'] ?? '');
            $is_paid = !empty($_POST['is_paid']) ? 1 : 0;
            $price   = (int)($_POST['price_tzs'] ?? 0);
            $data = [
                ':title'=>trim($_POST['title'] ?? ''),
                ':description'=>trim($_POST['description'] ?? ''),
                ':category'=>trim($_POST['category'] ?? 'general') ?: 'general',
                ':thumbnail_url'=>$thumb,
                ':video_url'=>$vid,
                ':is_paid'=>$is_paid,
                ':price_tzs'=>$is_paid ? $price : 0,
                ':is_active'=>!empty($_POST['is_active']) ? 1 : 0,
            ];
            if ($action === 'update' && !empty($_POST['id'])) {
                $sql = 'UPDATE videos SET title=:title, description=:description, category=:category,
                    thumbnail_url=COALESCE(NULLIF(:thumbnail_url,""), thumbnail_url),
                    video_url=COALESCE(NULLIF(:video_url,""), video_url),
                    is_paid=:is_paid, price_tzs=:price_tzs, is_active=:is_active WHERE id=:id';
                $data[':id'] = $_POST['id'];
                db()->prepare($sql)->execute($data);
                $msg = ['ok','Imehifadhiwa.'];
            } else {
                if (!$data[':title'] || !$data[':video_url']) throw new RuntimeException('Title na video ni lazima.');
                $sql = 'INSERT INTO videos (id,title,description,category,thumbnail_url,video_url,is_paid,price_tzs,is_active)
                        VALUES (:id,:title,:description,:category,:thumbnail_url,:video_url,:is_paid,:price_tzs,:is_active)';
                $data[':id'] = uuidv4();
                db()->prepare($sql)->execute($data);
                $msg = ['ok','Imeongezwa.'];
            }
        }
    } catch (Throwable $e) { $msg = ['bad', $e->getMessage()]; }
}

$edit = null;
if (!empty($_GET['edit'])) {
    $s = db()->prepare('SELECT * FROM videos WHERE id=?'); $s->execute([$_GET['edit']]); $edit = $s->fetch();
}
$rows = db()->query('SELECT * FROM videos ORDER BY created_at DESC')->fetchAll();

require __DIR__ . '/_layout.php';
?>
<h1 style="margin-top:0;color:var(--gold)">Video</h1>
<?php if ($msg): ?><div class="alert <?= $msg[0] ?>"><?= e($msg[1]) ?></div><?php endif; ?>

<div class="card">
  <h3 style="margin-top:0"><?= $edit ? 'Hariri Video' : 'Ongeza Video Mpya' ?></h3>
  <form method="post" enctype="multipart/form-data" class="form-grid">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="<?= $edit ? 'update' : 'create' ?>">
    <?php if ($edit): ?><input type="hidden" name="id" value="<?= e($edit['id']) ?>"><?php endif; ?>
    <div class="row-2">
      <div><label>Title</label><input name="title" required value="<?= e($edit['title'] ?? '') ?>"></div>
      <div><label>Category</label><input name="category" value="<?= e($edit['category'] ?? 'general') ?>"></div>
    </div>
    <div><label>Description</label><textarea name="description"><?= e($edit['description'] ?? '') ?></textarea></div>
    <div class="row-2">
      <div><label>Video URL (au pakia hapo chini)</label><input name="video_url" value="<?= e($edit['video_url'] ?? '') ?>"></div>
      <div><label>Thumbnail URL (au pakia hapo chini)</label><input name="thumbnail_url" value="<?= e($edit['thumbnail_url'] ?? '') ?>"></div>
    </div>
    <div class="row-2">
      <div><label>Pakia Video (max <?= MAX_VIDEO_MB ?>MB)</label><input type="file" name="video_file" accept="video/*"></div>
      <div><label>Pakia Thumbnail (max <?= MAX_IMAGE_MB ?>MB)</label><input type="file" name="thumbnail_file" accept="image/*"></div>
    </div>
    <div class="row-2">
      <div><label><input type="checkbox" name="is_paid" value="1" <?= !empty($edit['is_paid']) ? 'checked' : '' ?>> Video ya kulipia</label></div>
      <div><label>Bei (TZS)</label><input type="number" min="0" name="price_tzs" value="<?= (int)($edit['price_tzs'] ?? 0) ?>"></div>
    </div>
    <div><label><input type="checkbox" name="is_active" value="1" <?= !$edit || !empty($edit['is_active']) ? 'checked' : '' ?>> Inaonekana kwa watumiaji</label></div>
    <button class="btn btn-gold" type="submit"><?= $edit ? 'Hifadhi' : 'Ongeza' ?></button>
    <?php if ($edit): ?><a class="btn btn-outline" href="videos.php">Ghairi</a><?php endif; ?>
  </form>
</div>

<div class="card" style="overflow-x:auto">
  <h3 style="margin-top:0">Orodha ya Video (<?= count($rows) ?>)</h3>
  <table class="table">
    <thead><tr><th>Thumbnail</th><th>Title</th><th>Aina</th><th>Bei</th><th>Hali</th><th>Views</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $v): ?>
      <tr>
        <td><?php if ($v['thumbnail_url']): ?><img src="<?= e($v['thumbnail_url']) ?>" style="width:60px;height:34px;object-fit:cover;border-radius:6px"><?php else: ?>—<?php endif; ?></td>
        <td><strong><?= e($v['title']) ?></strong><br><span class="muted" style="font-size:11px"><?= e($v['category']) ?></span></td>
        <td><?= $v['is_paid'] ? '<span class="chip chip-warn">Kulipia</span>' : '<span class="chip chip-ok">Bure</span>' ?></td>
        <td><?= $v['is_paid'] ? fmt_tzs((int)$v['price_tzs']) : '—' ?></td>
        <td><?= $v['is_active'] ? '<span class="chip chip-ok">Hai</span>' : '<span class="chip chip-bad">Imefichwa</span>' ?></td>
        <td><?= (int)$v['views'] ?></td>
        <td style="white-space:nowrap">
          <a class="btn btn-outline" href="?edit=<?= e($v['id']) ?>" style="padding:6px 10px;font-size:12px">Hariri</a>
          <form method="post" style="display:inline" onsubmit="return confirm('Una uhakika?')">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= e($v['id']) ?>">
            <button class="btn btn-outline" style="padding:6px 10px;font-size:12px;color:var(--danger)" type="submit">Futa</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require __DIR__ . '/_layout_end.php'; ?>