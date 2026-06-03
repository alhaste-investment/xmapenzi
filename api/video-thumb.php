<?php
/**
 * Generate or serve a thumbnail for a video record.
 * Usage: /api/video-thumb.php?id=<video-id>
 *
 * This attempts to serve an existing thumbnail if `thumbnail_url` is set or a generated
 * thumbnail file exists under `uploads/thumbnails/<id>.jpg`. If not present and `ffmpeg`
 * is available, it will attempt to generate a thumbnail (frame at 3s) and serve it.
 */
require_once __DIR__ . '/../includes/db.php';

$id = $_GET['id'] ?? '';
if (!$id) { http_response_code(400); echo 'Missing id'; exit; }

$s = db()->prepare('SELECT video_url, thumbnail_url FROM videos WHERE id = ? AND is_active = 1');
$s->execute([$id]); $v = $s->fetch();
if (!$v) { http_response_code(404); echo 'Not found'; exit; }

// If thumbnail_url is set and looks like a full URL, proxy or redirect to it
if (!empty($v['thumbnail_url'])) {
    // If it's a remote URL, redirect to it so browser loads directly
    header('Location: ' . $v['thumbnail_url']);
    exit;
}

$thumbDir = __DIR__ . '/../uploads/thumbnails';
if (!is_dir($thumbDir)) { @mkdir($thumbDir, 0755, true); }
$thumbPath = $thumbDir . '/' . preg_replace('/[^a-zA-Z0-9_-]/','', $id) . '.jpg';

if (is_file($thumbPath) && filesize($thumbPath) > 0) {
    header('Content-Type: image/jpeg');
    header('Cache-Control: public, max-age=86400');
    readfile($thumbPath);
    exit;
}

http_response_code(404); echo 'No thumbnail'; exit;
