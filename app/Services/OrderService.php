<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Order;
use App\Models\Package;

class OrderService
{
    public static function create(int $userId, array $in): array
    {
        // Idempotency: jika key sudah ada, kembalikan order lama
        if (!empty($in['idempotency_key'])) {
            $existing = Order::findByIdem($in['idempotency_key'], $userId);
            if ($existing) {
                return ['order' => $existing, 'duplicate' => true];
            }
        }
        $pkg = Package::findActive((int)$in['package_id']);
        if (!$pkg) {
            throw new \InvalidArgumentException('Paket tidak tersedia.');
        }
        $price = (int)$pkg['price'];
        return Database::transaction(function ($pdo) use ($userId, $in, $pkg, $price) {
            $code = Order::generateCode();
            $now = gmdate('Y-m-d H:i:s');
            $idem = $in['idempotency_key'] ?: bin2hex(random_bytes(16));
            $st = $pdo->prepare(
                'INSERT INTO orders(order_code,user_id,package_id,status,destination_address,destination_latitude,destination_longitude,note,package_name_snapshot,package_price_snapshot,subtotal,total,payment_status,progress,idempotency_key,created_at,updated_at)
                 VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?, ?,?)'
            );
            $st->execute([
                $code, $userId, $pkg['id'], 'pending',
                $in['address'], $in['latitude'], $in['longitude'], $in['note'] ?? null,
                $pkg['name'], $price, $price, $price, 'unpaid', 0, $idem, $now, $now,
            ]);
            $orderId = (int)$pdo->lastInsertId();
            DispatchService::log($pdo, $orderId, null, 'pending', $userId, 'Pesanan dibuat.');
            // pending -> searching_driver
            $pdo->prepare('UPDATE orders SET status = ?, updated_at = ? WHERE id = ?')->execute(['searching_driver', $now, $orderId]);
            DispatchService::log($pdo, $orderId, 'pending', 'searching_driver', $userId, 'Mencari kendaraan.');
            // payment simulasi
            $ref = 'SIM-' . strtoupper(bin2hex(random_bytes(4)));
            $pdo->prepare("INSERT INTO payments(order_id,method,amount,status,reference_code,is_simulated,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?)")
                ->execute([$orderId, $in['payment_method'], $price, 'unpaid', $ref, 1, $now, $now]);

            $order = $pdo->query('SELECT * FROM orders WHERE id = ' . $orderId)->fetch();
            $vehicle = DispatchService::dispatch($pdo, $order);
            $order = $pdo->query('SELECT * FROM orders WHERE id = ' . $orderId)->fetch();
            return ['order' => $order, 'vehicle' => $vehicle, 'duplicate' => false];
        });
    }

    public static function cancel(array $order, mixed $actorId, string $role = 'user'): void
    {
        if (!in_array($order['status'], Order::CANCELLABLE, true)) {
            throw new \RuntimeException('Pesanan pada status ' . $order['status'] . ' tidak dapat dibatalkan.', 409);
        }
        Database::transaction(function ($pdo) use ($order, $actorId) {
            $now = gmdate('Y-m-d H:i:s');
            $pdo->prepare("UPDATE orders SET status = 'cancelled', cancelled_at = ?, updated_at = ? WHERE id = ? AND status = ?")
                ->execute([$now, $now, $order['id'], $order['status']]);
            if ($pdo->query('SELECT changes()')->fetchColumn() == 0 && $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'sqlite') {
                // fallback rowCount check
            }
            DispatchService::log($pdo, (int)$order['id'], $order['status'], 'cancelled', $actorId, 'Dibatalkan pengguna.');
            if (!empty($order['vehicle_id'])) {
                $pdo->prepare("UPDATE vehicles SET status = 'available', current_order_id = NULL, updated_at = ? WHERE id = ?")
                    ->execute([$now, $order['vehicle_id']]);
            }
            $pdo->prepare("UPDATE payments SET status = 'refunded', updated_at = ? WHERE order_id = ? AND status IN ('unpaid','pending','paid')")
                ->execute([$now, $order['id']]);
        });
    }
}
