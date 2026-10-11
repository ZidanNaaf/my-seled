<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Database;
use App\Core\Response;

class RateLimit
{
    /** Rate limit sederhana berbasis file. Return true jika boleh lanjut. */
    public static function check(string $key, int $max = 5, int $windowSec = 600): bool
    {
        $dir = BASE_PATH . '/storage/cache';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $file = $dir . '/rl_' . preg_replace('/[^a-z0-9_\-]/i', '_', $key) . '.json';
        $now = time();
        $data = ['count' => 0, 'reset' => $now + $windowSec];
        if (is_file($file)) {
            $raw = json_decode((string)file_get_contents($file), true);
            if (is_array($raw) && ($raw['reset'] ?? 0) > $now) {
                $data = $raw;
            }
        }
        $data['count']++;
        file_put_contents($file, json_encode($data), LOCK_EX);
        if ($data['count'] > $max) {
            $retry = max(1, ($data['reset'] - $now));
            $isApi = str_starts_with(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/api/');
            if ($isApi) {
                header('Retry-After: ' . $retry);
                Response::json(['success' => false, 'message' => 'Terlalu banyak percobaan. Coba lagi dalam ' . $retry . ' detik.'], 429);
            }
            http_response_code(429);
            echo 'Terlalu banyak percobaan. Coba lagi dalam ' . $retry . ' detik.';
            exit;
        }
        return true;
    }

    public static function reset(string $key): void
    {
        $file = BASE_PATH . '/storage/cache/rl_' . preg_replace('/[^a-z0-9_\-]/i', '_', $key) . '.json';
        if (is_file($file)) unlink($file);
    }
}
