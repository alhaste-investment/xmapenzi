<?php
// ============================================================
// Xmapenzi — Configuration
// Badili thamani hizi kabla ya ku-upload kwenye cPanel.
// ============================================================

// --- MySQL Database ---
define('DB_HOST', 'localhost');
define('DB_NAME', 'mautjmej_xmapenzi');
define('DB_USER', 'mautjmej_xmapenzi');
define('DB_PASS', 'Alhaste4400@@');
define('DB_CHARSET', 'utf8mb4');

// --- Site ---
define('SITE_NAME', 'Xmapenzi');
define('SITE_URL', 'http://xmapenzi.online/'); // bila slash mwishoni

// --- Sessions / security ---
define('SESSION_NAME', 'xmpsid');

// --- Uploads ---
define('UPLOAD_DIR', __DIR__ . '/../uploads');
define('UPLOAD_URL', SITE_URL . '/uploads');
define('MAX_VIDEO_MB', 200);
define('MAX_IMAGE_MB', 8);

// --- Selcom defaults (zinasomwa kutoka kwenye settings table) ---
define('SELCOM_DEFAULT_BASE', 'https://apigw.selcommobile.com');

date_default_timezone_set('Africa/Dar_es_Salaam');
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');