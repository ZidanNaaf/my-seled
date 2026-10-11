<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Order;
use App\Models\Package;

/** Endpoint API JSON ringan (dipakai fetch frontend + integrasi). */
class ApiController extends Controller
{
    public function packages(array $p = []): void
    {
        $this->json(['success' => true, 'data' => Package::active()]);
    }

    public function adminOrders(array $p = []): void
    {
        $rows = Database::connection()->query("SELECT o.*, u.name AS user_name FROM orders o LEFT JOIN users u ON u.id=o.user_id ORDER BY o.created_at DESC LIMIT 50")->fetchAll();
        $this->json(['success' => true, 'data' => $rows]);
    }

    public function adminVehicles(array $p = []): void
    {
        $rows = Database::connection()->query('SELECT * FROM vehicles ORDER BY code ASC')->fetchAll();
        $this->json(['success' => true, 'data' => $rows]);
    }
}
