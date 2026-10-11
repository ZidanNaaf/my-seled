<?php
declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

use App\Core\Router;

$router = new Router();
require BASE_PATH . '/routes/web.php';
require BASE_PATH . '/routes/api.php';

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
// Normalisasi trailing slash (kecuali root)
if ($uri !== '/' && str_ends_with($uri, '/')) {
    $uri = rtrim($uri, '/');
}
$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $uri);
