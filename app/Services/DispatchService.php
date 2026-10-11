<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Vehicle;
use PDO;

class DispatchService
{
    /**
     * Cari kendaraan available terdekat & tetapkan ke order.
     * Harus dipanggil di dalam transaksi. Return vehicle|null.
     */
    public static function dispatch(PDO $pdo, array $order): ?array
    {
        $st = $pdo->prepare(
            "SELECT *, ((latitude - :lat) * (latitude - :lat) + (longitude - :lng) * (longitude - :lng)) AS dist
             FROM vehicles WHERE status = 'available' AND current_order_id IS NULL
             ORDER BY dist ASC LIMIT 1"
        );
        $st->execute([':lat' => $order['destination_latitude'], ':lng' => $order['destination_longitude']]);
        $v = $st->fetch();
        if (!$v) {
            return null; // antrean: tetap searching_driver
        }
        // Tandai kendaraan + simpan titik awal perjalanan (origin = posisi kendaraan saat dispatch)
        $up = $pdo->prepare("UPDATE vehicles SET status = 'assigned', current_order_id = ?, updated_at = ? WHERE id = ? AND status = 'available' AND current_order_id IS NULL");
        $now = gmdate('Y-m-d H:i:s');
        $up->execute([$order['id'], $now, $v['id']]);
        if ($up->rowCount() !== 1) {
            return null; // kalah race
        }
        $pdo->prepare('UPDATE orders SET vehicle_id = ?, status = ?, origin_latitude = ?, origin_longitude = ?, updated_at = ? WHERE id = ?')
            ->execute([$v['id'], 'assigned', $v['latitude'], $v['longitude'], $now, $order['id']]);
        self::log($pdo, (int)$order['id'], $order['status'], 'assigned', $order['user_id'], 'Kendaraan ' . $v['code'] . ' ditetapkan (dispatch otomatis).');
        return $v;
    }

    public static function log(PDO $pdo, int $orderId, ?string $old, string $new, mixed $actor, ?string $note = null): void
    {
        $st = $pdo->prepare('INSERT INTO order_status_logs(order_id, actor_user_id, old_status, new_status, note, created_at) VALUES(?,?,?,?,?,?)');
        $st->execute([$orderId, $actor ? (int)$actor : null, $old, $new, $note, gmdate('Y-m-d H:i:s')]);
    }
}
