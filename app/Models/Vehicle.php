<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Vehicle
{
    public static function all(): array
    {
        return Database::connection()->query('SELECT * FROM vehicles ORDER BY code ASC')->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $st = Database::connection()->prepare('SELECT * FROM vehicles WHERE id = ? LIMIT 1');
        $st->execute([$id]);
        $r = $st->fetch();
        return $r ?: null;
    }

    public static function availableNear(float $lat, float $lng): ?array
    {
        // Strategi sederhana: jarak euclidean terkecil dari kendaraan available
        $st = Database::connection()->prepare(
            "SELECT *, ((latitude - ?) * (latitude - ?) + (longitude - ?) * (longitude - ?)) AS dist
             FROM vehicles WHERE status = 'available' AND current_order_id IS NULL
             ORDER BY dist ASC LIMIT 1"
        );
        $st->execute([$lat, $lat, $lng, $lng]);
        $r = $st->fetch();
        return $r ?: null;
    }
}
