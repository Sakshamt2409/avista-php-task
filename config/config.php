<?php
declare(strict_types=1);

$sessionPath = dirname(__DIR__) . '/storage/sessions';
if (!is_dir($sessionPath)) {
    mkdir($sessionPath, 0775, true);
}
session_save_path($sessionPath);
session_start();

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'avista_php_task');
define('DB_USER', 'root');
define('DB_PASS', '');

$basePath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
if (substr($basePath, -4) === '/api') {
    $basePath = dirname($basePath);
}
$basePath = rtrim($basePath, '/');
define('BASE_URL', ($basePath === '.' || $basePath === '/') ? '' : $basePath);
define('UPLOAD_DIR', dirname(__DIR__) . '/uploads/products');
define('UPLOAD_URL', BASE_URL . '/uploads/products');

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}
