<?php
declare(strict_types=1);

define('APP_NAME', 'SIDEQUEST');
// Automatically detect the folder where this project is installed under XAMPP/Apache.
// This keeps CSS, JS, images, and page links working even if the folder is renamed
// or placed inside another folder under htdocs.
$app_root = realpath(__DIR__ . '/..');
$document_root = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;

// Build a URL path from the actual XAMPP document root. This avoids broken
// CSS/JS/image links when SIDEQUEST is placed in a normal htdocs subfolder.
if ($document_root && $app_root) {
    $document_root = str_replace('\\', '/', $document_root);
    $app_root = str_replace('\\', '/', $app_root);
    if (str_starts_with($app_root, $document_root)) {
        $relative_root = substr($app_root, strlen($document_root));
        define('BASE_URL', rtrim($relative_root, '/'));
    } else {
        define('BASE_URL', '/SIDEQUEST');
    }
} else {
    define('BASE_URL', '/SIDEQUEST');
}

define('DB_HOST', getenv('MYSQLHOST') ?: getenv('DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('MYSQLDATABASE') ?: getenv('DB_NAME') ?: 'sidequest');
define('DB_USER', getenv('MYSQLUSER') ?: getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('MYSQLPASSWORD') ?: getenv('DB_PASS') ?: '');
define('DB_PORT', getenv('MYSQLPORT') ?: getenv('DB_PORT') ?: '3306');

date_default_timezone_set('Asia/Manila');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function base_url(string $path = ''): string {
    return BASE_URL . ($path ? '/' . ltrim($path, '/') : '');
}

function redirect(string $path): never {
    header('Location: ' . (str_starts_with($path, 'http') ? $path : base_url($path)));
    exit;
}
