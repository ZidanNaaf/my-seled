<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Response;
use App\Core\View;

class RoleMiddleware
{
    public static function handle(array $roles): bool
    {
        $user = Auth::user();
        if (!$user) {
            $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
            if (str_starts_with($path, '/api/')) {
                Response::json(['success' => false, 'message' => 'Belum login'], 401);
            }
            header('Location: /login');
            exit;
        }
        if (!in_array($user['role'], $roles, true) || (int)($user['is_active'] ?? 1) !== 1) {
            $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
            if (str_starts_with($path, '/api/')) {
                Response::json(['success' => false, 'message' => 'Tidak memiliki akses'], 403);
            }
            http_response_code(403);
            View::render('errors/403', ['title' => 'Tidak Ada Akses', 'message' => 'Peran kamu tidak diizinkan membuka halaman ini.'], 'guest');
            exit;
        }
        return true;
    }
}
