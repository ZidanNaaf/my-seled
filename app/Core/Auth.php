<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\User;

class Auth
{
    public static function user(): ?array
    {
        if (empty($_SESSION['user_id'])) {
            return null;
        }
        return User::findById((int)$_SESSION['user_id']);
    }

    public static function check(): bool
    {
        return !empty($_SESSION['user_id']);
    }

    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['login_at'] = time();
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    public static function role(): ?string
    {
        return $_SESSION['user_role'] ?? null;
    }
}
