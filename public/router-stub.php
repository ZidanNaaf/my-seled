<?php
// Router stub untuk `php -S localhost:8000 -t public public/router-stub.php`
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$file = __DIR__ . $path;
if ($path !== '/' && is_file($file)) {
    return false; // sajikan file statis
}
require __DIR__ . '/index.php';
