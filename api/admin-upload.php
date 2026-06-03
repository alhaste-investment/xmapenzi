<?php
// AJAX file upload for admin with progress support
require_once __DIR__ . '/../includes/auth.php'; require_admin();
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['error'=>'Method not allowed']); exit;
}

$csrf = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF'] ?? '');
if (!hash_equals(csrf_token(), (string)$csrf)) {
    http_response_code(401); echo json_encode(['error'=>'Invalid CSRF']); exit;
}

$subdir = $_POST['subdir'] ?? 'uploads';
$allowed = ['videos','thumbnails','images','thumbnails'];
if (!in_array($subdir, $allowed, true)) $subdir = 'videos';

if (empty($_FILES['file']) || ($_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
    http_response_code(400); echo json_encode(['error'=>'No file']); exit;
}

 $f = $_FILES['file'];
 if ($f['error'] !== UPLOAD_ERR_OK) {
        $msg = 'Upload error';
        switch ($f['error']) {
            case UPLOAD_ERR_INI_SIZE: case UPLOAD_ERR_FORM_SIZE: $msg = 'File too large (server)'; break;
            case UPLOAD_ERR_PARTIAL: $msg = 'File only partially uploaded'; break;
            case UPLOAD_ERR_NO_TMP_DIR: $msg = 'Missing temporary folder'; break;
            case UPLOAD_ERR_CANT_WRITE: $msg = 'Failed to write file to disk'; break;
            case UPLOAD_ERR_EXTENSION: $msg = 'Upload stopped by extension'; break;
            default: $msg = 'Upload error code ' . (int)$f['error'];
        }
        error_log('admin-upload error: ' . $msg . ' (' . ($f['name'] ?? '') . ')');
        http_response_code(400); echo json_encode(['error'=>$msg]); exit;
 }

// basic size check (50MB per default, rely on server limits)
if ($f['size'] > 500 * 1024 * 1024) { http_response_code(400); echo json_encode(['error'=>'File too large']); exit; }

$ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
$dir = UPLOAD_DIR . '/' . $subdir;
$dir = UPLOAD_DIR . '/' . $subdir;
if (!is_dir($dir)) {
    if (!@mkdir($dir, 0775, true)) {
        http_response_code(500); error_log('admin-upload: failed to create dir ' . $dir); echo json_encode(['error'=>'Cannot create upload directory']); exit;
    }
}
if (!is_writable($dir)) { http_response_code(500); error_log('admin-upload: dir not writable ' . $dir); echo json_encode(['error'=>'Upload directory not writable']); exit; }
if (!is_uploaded_file($f['tmp_name'])) { http_response_code(400); error_log('admin-upload: tmp file missing '.($f['tmp_name'] ?? '')); echo json_encode(['error'=>'Temporary upload file missing']); exit; }

$name = bin2hex(random_bytes(8)) . '.' . $ext;
$target = $dir . '/' . $name;
if (!move_uploaded_file($f['tmp_name'], $target)) {
    // try fallback copy
    if (!@copy($f['tmp_name'], $target)) {
        error_log('admin-upload: move_uploaded_file failed, copy also failed. tmp=' . ($f['tmp_name'] ?? '') . ' target=' . $target);
        http_response_code(500); echo json_encode(['error'=>'Failed to save uploaded file (permissions?)']); exit;
    }
}

$url = UPLOAD_URL . '/' . $subdir . '/' . $name;
echo json_encode(['url'=>$url]);
exit;
