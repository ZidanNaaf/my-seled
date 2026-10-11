<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class User
{
    public static function findById(int $id): ?array
    {
        $st = Database::connection()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $st->execute([$id]);
        $r = $st->fetch();
        return $r ?: null;
    }

    public static function findByLogin(string $login): ?array
    {
        $st = Database::connection()->prepare('SELECT * FROM users WHERE email = ? OR username = ? LIMIT 1');
        $st->execute([$login, $login]);
        $r = $st->fetch();
        return $r ?: null;
    }

    public static function create(array $d): int
    {
        $st = Database::connection()->prepare(
            'INSERT INTO users(name,username,email,phone,password_hash,role,is_active,created_at,updated_at) VALUES(?,?,?,?,?,?,1,?,?)'
        );
        $now = gmdate('Y-m-d H:i:s');
        $st->execute([$d['name'], $d['username'], $d['email'], $d['phone'] ?? null, $d['password_hash'], $d['role'] ?? 'user', $now, $now]);
        return (int)Database::connection()->lastInsertId();
    }

    public static function countAll(): int
    {
        return (int)Database::connection()->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }
}
