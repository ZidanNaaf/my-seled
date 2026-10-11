<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Response;
use App\Core\View;

class AuthMiddleware
{
    public static function handle(): bool
    {
        if (!Auth::check()) {
            self::deny();
        }
        // Verifikasi user masih ada di DB dan aktif.
        // Mencegah stale session (mis. DB di-reset setelah login) yang membuat
        // Auth::user() me-return null dan memicu warning di controller.
        $u = Auth::user();
        if (!$u || (int)($u['is_active'] ?? 0) !== 1) {
            Auth::logout();
            self::deny();
        }
        return true;
    }

    private static function deny(): void
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        if (str_starts_with($path, '/api/')) {
            Response::json(['success' => false, 'message' => 'Belum login'], 401);
        }
        $_SESSION['intended'] = $_SERVER['REQUEST_URI'] ?? '/home';
        header('Location: /login');
        exit;
    }

    public static function guest(): bool
    {
        if (Auth::check()) {
            header('Location: /home');
            exit;
        }
        return true;
    }
}
