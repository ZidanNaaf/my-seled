<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

class PaymentService
{
    public static function markPaid(int $orderId): void
    {
        $pdo = Database::connection();
        $pdo->prepare("UPDATE payments SET status = 'paid', updated_at = ? WHERE order_id = ?")
            ->execute([gmdate('Y-m-d H:i:s'), $orderId]);
        $pdo->prepare("UPDATE orders SET payment_status = 'paid', updated_at = ? WHERE id = ?")
            ->execute([gmdate('Y-m-d H:i:s'), $orderId]);
    }
}
