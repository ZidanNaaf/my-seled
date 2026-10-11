<?php
declare(strict_types=1);

namespace App\Core;

class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(self::token(), ENT_QUOTES) . '">';
    }

    public static function check(?string $token): bool
    {
        if (empty($_SESSION['_csrf']) || empty($token)) {
            return false;
        }
        return hash_equals((string)$_SESSION['_csrf'], (string)$token);
    }

    public static function requireValid(): void
    {
        $t = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!self::check($t)) {
            $isApi = str_starts_with(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/api/');
            if ($isApi) {
                Response::json(['success' => false, 'message' => 'Token CSRF tidak valid. Muat ulang halaman.'], 403);
            }
            http_response_code(403);
            View::render('errors/403', ['title' => 'Akses Ditolak', 'message' => 'Token keamanan tidak valid. Silakan muat ulang dan coba lagi.'], 'guest');
            exit;
        }
    }
}
