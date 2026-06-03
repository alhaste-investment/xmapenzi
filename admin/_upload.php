<?php
// Shared upload helper for admin pages
function admin_handle_upload(string $field, string $subdir, int $max_mb, array $allowed_ext): ?string {
    if (empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    $f = $_FILES[$field];
    if ($f['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Upload error code ' . $f['error']);
    if ($f['size'] > $max_mb * 1024 * 1024) throw new RuntimeException("Faili kubwa sana (max {$max_mb}MB)");
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_ext, true)) throw new RuntimeException('Aina ya faili hairuhusiwi');
    $dir = UPLOAD_DIR . '/' . $subdir;
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    $name = bin2hex(random_bytes(8)) . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $name)) throw new RuntimeException('Imeshindikana kuhifadhi faili');
    return UPLOAD_URL . '/' . $subdir . '/' . $name;
}