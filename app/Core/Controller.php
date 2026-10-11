<?php
declare(strict_types=1);

namespace App\Core;

class Controller
{
    protected function view(string $page, array $data = [], string $layout = 'app'): void
    {
        View::render($page, $data, $layout);
    }

    protected function json(array $data, int $code = 200): void
    {
        Response::json($data, $code);
    }

    protected function input(string $key, mixed $default = null): mixed
    {
        // Jangan pakai $_REQUEST sembarangan: prioritaskan POST lalu GET
        if (isset($_POST[$key])) return is_string($_POST[$key]) ? trim($_POST[$key]) : $_POST[$key];
        if (isset($_GET[$key])) return is_string($_GET[$key]) ? trim($_GET[$key]) : $_GET[$key];
        $json = $this->jsonBody();
        if (is_array($json) && array_key_exists($key, $json)) {
            return is_string($json[$key]) ? trim($json[$key]) : $json[$key];
        }
        return $default;
    }

    protected function jsonBody(): ?array
    {
        static $cache = null;
        if ($cache !== null) return $cache;
        $ct = $_SERVER['CONTENT_TYPE'] ?? '';
        if (str_contains($ct, 'application/json')) {
            $raw = file_get_contents('php://input');
            $cache = json_decode($raw ?: '{}', true);
            return is_array($cache) ? $cache : [];
        }
        return $cache = [];
    }

    protected function flash(string $type, string $message): void
    {
        $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
    }
}
