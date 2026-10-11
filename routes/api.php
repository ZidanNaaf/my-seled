<?php
declare(strict_types=1);

use App\Controllers\ApiController;
use App\Controllers\AuthController;
use App\Controllers\DriverController;
use App\Controllers\OrderController;
use App\Controllers\TrackingController;

/** @var \App\Core\Router $router */

$router->post('/api/auth/login', [AuthController::class, 'login']);
$router->post('/api/auth/register', [AuthController::class, 'register']);
$router->post('/api/auth/logout', [AuthController::class, 'logout']);
$router->get('/api/packages', [ApiController::class, 'packages']);
$router->get('/api/orders', [OrderController::class, 'index'], ['auth']);
$router->post('/api/orders', [OrderController::class, 'store'], ['auth']);
$router->get('/api/orders/{orderCode}', [TrackingController::class, 'api'], ['auth']);
$router->post('/api/orders/{orderCode}/cancel', [OrderController::class, 'cancel'], ['auth']);
$router->get('/api/tracking/{orderCode}', [TrackingController::class, 'api'], ['auth']);
$router->post('/api/driver/availability', [DriverController::class, 'availability'], ['auth', 'role:driver,admin']);
$router->post('/api/driver/orders/{id}/accept', [DriverController::class, 'accept'], ['auth', 'role:driver,admin']);
$router->post('/api/driver/orders/{id}/status', [DriverController::class, 'updateStatus'], ['auth', 'role:driver,admin']);
$router->get('/api/admin/orders', [ApiController::class, 'adminOrders'], ['auth', 'role:admin']);
$router->get('/api/admin/vehicles', [ApiController::class, 'adminVehicles'], ['auth', 'role:admin']);
$router->get('/api/admin/packages', [ApiController::class, 'packages'], ['auth', 'role:admin']);
