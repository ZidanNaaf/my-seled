<?php
declare(strict_types=1);

use App\Controllers\AdminController;
use App\Controllers\ApiController;
use App\Controllers\AuthController;
use App\Controllers\DriverController;
use App\Controllers\HomeController;
use App\Controllers\OrderController;
use App\Controllers\ProfileController;
use App\Controllers\TrackingController;

/** @var \App\Core\Router $router */

// Publik
$router->get('/', [HomeController::class, 'landing']);
$router->get('/login', [AuthController::class, 'showLogin'], ['guest']);
$router->post('/login', [AuthController::class, 'login'], ['guest']);
$router->get('/register', [AuthController::class, 'showRegister'], ['guest']);
$router->post('/register', [AuthController::class, 'register'], ['guest']);
$router->get('/logout', [AuthController::class, 'logout']);

// User
$router->get('/home', [HomeController::class, 'home'], ['auth']);
$router->get('/order', [OrderController::class, 'createPage'], ['auth', 'role:user,driver,admin']);
$router->post('/orders', [OrderController::class, 'store'], ['auth', 'role:user,driver,admin']);
$router->get('/orders', [OrderController::class, 'index'], ['auth']);
$router->get('/orders/{orderCode}', [OrderController::class, 'detail'], ['auth']);
$router->post('/orders/{orderCode}/cancel', [OrderController::class, 'cancel'], ['auth']);
$router->get('/tracking/{orderCode}', [TrackingController::class, 'show'], ['auth']);
$router->get('/profile', [ProfileController::class, 'show'], ['auth']);
$router->post('/profile', [ProfileController::class, 'update'], ['auth']);
$router->post('/profile/password', [ProfileController::class, 'password'], ['auth']);

// Driver
$router->get('/driver', [DriverController::class, 'dashboard'], ['auth', 'role:driver,admin']);
$router->get('/driver/orders', [DriverController::class, 'orders'], ['auth', 'role:driver,admin']);
$router->post('/driver/availability', [DriverController::class, 'availability'], ['auth', 'role:driver,admin']);
$router->post('/driver/orders/{id}/accept', [DriverController::class, 'accept'], ['auth', 'role:driver,admin']);
$router->post('/driver/orders/{id}/status', [DriverController::class, 'updateStatus'], ['auth', 'role:driver,admin']);

// Admin
$router->get('/admin', [AdminController::class, 'dashboard'], ['auth', 'role:admin']);
$router->get('/admin/orders', [AdminController::class, 'orders'], ['auth', 'role:admin']);
$router->get('/admin/orders/{code}', [AdminController::class, 'adminOrderDetail'], ['auth', 'role:admin']);
$router->get('/admin/users', [AdminController::class, 'users'], ['auth', 'role:admin']);
$router->post('/admin/users/role', [AdminController::class, 'updateUserRole'], ['auth', 'role:admin']);
$router->post('/admin/users/toggle', [AdminController::class, 'toggleUser'], ['auth', 'role:admin']);
$router->get('/admin/vehicles', [AdminController::class, 'vehicles'], ['auth', 'role:admin']);
$router->post('/admin/vehicles/save', [AdminController::class, 'saveVehicle'], ['auth', 'role:admin']);
$router->get('/admin/packages', [AdminController::class, 'packages'], ['auth', 'role:admin']);
$router->post('/admin/packages/save', [AdminController::class, 'savePackage'], ['auth', 'role:admin']);
$router->get('/admin/settings', [AdminController::class, 'settings'], ['auth', 'role:admin']);
$router->post('/admin/settings', [AdminController::class, 'settings'], ['auth', 'role:admin']);
