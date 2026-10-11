<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Order
{
    public const ACTIVE = ['pending', 'searching_driver', 'assigned', 'driver_arriving', 'arrived', 'in_service'];
    public const CANCELLABLE = ['pending', 'searching_driver', 'assigned'];

    public const TRANSITIONS = [
        'pending' => ['searching_driver', 'cancelled'],
        'searching_driver' => ['assigned', 'cancelled'],
        'assigned' => ['driver_arriving', 'cancelled'],
        'driver_arriving' => ['arrived', 'cancelled'],
        'arrived' => ['in_service', 'cancelled'],
        'in_service' => ['completed'],
        'completed' => [],
        'cancelled' => [],
    ];

    public static function canTransition(string $from, string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    public static function findByCode(string $code): ?array
    {
        $st = Database::connection()->prepare('SELECT * FROM orders WHERE order_code = ? LIMIT 1');
        $st->execute([$code]);
        $r = $st->fetch();
        return $r ?: null;
    }

    public static function findByIdem(string $key, int $userId): ?array
    {
        $st = Database::connection()->prepare('SELECT * FROM orders WHERE idempotency_key = ? AND user_id = ? LIMIT 1');
        $st->execute([$key, $userId]);
        $r = $st->fetch();
        return $r ?: null;
    }

    public static function forUser(int $userId, ?string $status = null, ?string $q = null): array
    {
        $sql = 'SELECT o.*, p.name AS package_name, v.code AS vehicle_code, v.name AS vehicle_name
                FROM orders o LEFT JOIN packages p ON p.id = o.package_id
                LEFT JOIN vehicles v ON v.id = o.vehicle_id
                WHERE o.user_id = ?';
        $params = [$userId];
        if ($status && $status !== 'all') {
            if ($status === 'active') {
                $sql .= " AND o.status IN ('pending','searching_driver','assigned','driver_arriving','arrived','in_service')";
            } else {
                $sql .= ' AND o.status = ?';
                $params[] = $status;
            }
        }
        if ($q) {
            $sql .= ' AND o.order_code LIKE ?';
            $params[] = '%' . $q . '%';
        }
        $sql .= ' ORDER BY o.created_at DESC LIMIT 100';
        $st = Database::connection()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    public static function logs(int $orderId): array
    {
        $st = Database::connection()->prepare('SELECT * FROM order_status_logs WHERE order_id = ? ORDER BY created_at ASC, id ASC');
        $st->execute([$orderId]);
        return $st->fetchAll();
    }

    public static function generateCode(): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        for ($i = 0; $i < 10; $i++) {
            $code = 'MS-';
            for ($j = 0; $j < 6; $j++) {
                $code .= $chars[random_int(0, strlen($chars) - 1)];
            }
            $st = Database::connection()->prepare('SELECT 1 FROM orders WHERE order_code = ? LIMIT 1');
            $st->execute([$code]);
            if (!$st->fetch()) return $code;
        }
        return 'MS-' . strtoupper(bin2hex(random_bytes(3)));
    }
}
