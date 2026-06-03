<?php
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');
$id = $_GET['id'] ?? '';
$ref = $_GET['ref'] ?? '';

$s = db()->prepare('SELECT id, title, video_url, is_paid FROM videos WHERE id = ? AND is_active = 1');
$s->execute([$id]); $v = $s->fetch();
if (!$v) json_out(['error' => 'Not found'], 404);

if ((int)$v['is_paid'] === 0) {
  // Free video — increment views
  db()->prepare('UPDATE videos SET views = views + 1 WHERE id = ?')->execute([$id]);
  json_out(['url' => $v['video_url'], 'title' => $v['title']]);
}

if (!$ref) json_out(['error' => 'Payment required'], 402);
$p = db()->prepare('SELECT status FROM payments WHERE reference = ? AND item_id = ? AND item_type = "video"');
$p->execute([$ref, $id]); $row = $p->fetch();
if (!$row || $row['status'] !== 'paid') json_out(['error' => 'Payment required'], 402);

// Increment views for paid video
db()->prepare('UPDATE videos SET views = views + 1 WHERE id = ?')->execute([$id]);
json_out(['url' => $v['video_url'], 'title' => $v['title']]);