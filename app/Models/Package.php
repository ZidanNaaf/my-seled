<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Package
{
    public static function active(): array
    {
        $st = Database::connection()->query("SELECT * FROM packages WHERE is_active = 1 ORDER BY price ASC");
        return $st->fetchAll();
    }

    public static function all(): array
    {
        return Database::connection()->query('SELECT * FROM packages ORDER BY price ASC')->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $st = Database::connection()->prepare('SELECT * FROM packages WHERE id = ? LIMIT 1');
        $st->execute([$id]);
        $r = $st->fetch();
        return $r ?: null;
    }

    public static function findActive(int $id): ?array
    {
        $st = Database::connection()->prepare('SELECT * FROM packages WHERE id = ? AND is_active = 1 LIMIT 1');
        $st->execute([$id]);
        $r = $st->fetch();
        return $r ?: null;
    }
}
