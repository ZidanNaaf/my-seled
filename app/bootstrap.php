<?php
declare(strict_types=1);

if (defined('BASE_PATH')) {
    return;
}
define('BASE_PATH', dirname(__DIR__));

spl_autoload_register(function ($class) {
    if (str_starts_with($class, 'App\\')) {
        $rel = str_replace('\\', '/', substr($class, 4));
        $file = BASE_PATH . '/app/' . $rel . '.php';
        if (is_file($file)) require $file;
    }
});

// Load .env sederhana (tanpa dependency)
$envFile = BASE_PATH . '/.env';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        [$k, $v] = array_pad(explode('=', $line, 2), 2, '');
        $k = trim($k);
        $v = trim(trim($v), '"\'');
        if ($k !== '' && getenv($k) === false) {
            putenv("$k=$v");
            $_ENV[$k] = $v;
        }
    }
}

$config = require BASE_PATH . '/config/app.php';

// Session aman
$secure = (getenv('SESSION_SECURE') ?: 'false') === 'true' || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
session_name($config['session_name'] ?? 'MYSELEDSESSID');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $secure,
    'httponly' => true,
    'samesite' => 'Lax',
]);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

set_exception_handler(function ($e) use ($config) {
    $logDir = BASE_PATH . '/storage/logs';
    if (!is_dir($logDir)) mkdir($logDir, 0755, true);
    error_log('[' . gmdate('c') . '] ' . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n", 3, $logDir . '/app.log');
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    if (str_starts_with($path, '/api/')) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan internal.']);
        exit;
    }
    http_response_code(500);
    if (!empty($config['debug'])) {
        echo '<h1>500</h1><p>Terjadi kesalahan. Cek storage/logs/app.log</p>';
    } else {
        require BASE_PATH . '/pages/errors/500.php';
    }
    exit;
});

if (!function_exists('app_log')) {
function app_log(string $msg): void
{
    $dir = BASE_PATH . '/storage/logs';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    error_log('[' . gmdate('c') . '] ' . $msg . "\n", 3, $dir . '/app.log');
}
}
