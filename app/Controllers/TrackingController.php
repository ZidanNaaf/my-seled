<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Models\Order;
use App\Services\TrackingService;

class TrackingController extends Controller
{
    private function getOwned(string $code, array $user): ?array
    {
        $order = Order::findByCode($code);
        if (!$order) return null;
        // user hanya boleh lihat miliknya; driver/admin boleh semua (dicek di caller)
        if ($user['role'] === 'user' && (int)$order['user_id'] !== (int)$user['id']) {
            return null;
        }
        return $order;
    }

    public function show(array $p = []): void
    {
        $user = Auth::user();
        if (!$user) {
            Auth::logout();
            \App\Core\Response::redirect('/login');
        }
        $order = $this->getOwned($p['orderCode'] ?? '', $user);
        if (!$order) {
            http_response_code(404);
            $this->view('errors/404', ['title' => 'Tracking Tidak Ditemukan', 'message' => 'Pesanan tidak ditemukan atau bukan milikmu.'], 'app');
            return;
        }
        $pdo = Database::connection();
        $veh = null;
        if ($order['vehicle_id']) {
            $s = $pdo->prepare('SELECT * FROM vehicles WHERE id=?');
            $s->execute([$order['vehicle_id']]);
            $veh = $s->fetch() ?: null;
        }
        $logs = Order::logs((int)$order['id']);
        $this->view('tracking/show', ['title' => 'Lacak ' . $order['order_code'], 'order' => $order, 'vehicle' => $veh, 'logs' => $logs], 'app');
    }

    public function api(array $p = []): void
    {
        $user = Auth::user();
        if (!$user) {
            $this->json(['success' => false, 'message' => 'Belum login'], 401);
        }
        $order = $this->getOwned($p['orderCode'] ?? $p['code'] ?? '', $user);
        if (!$order) {
            $this->json(['success' => false, 'message' => 'Pesanan tidak ditemukan'], 404);
        }
        $snap = TrackingService::advance($order);
        // Refresh order + vehicle + logs
        $fresh = Order::findByCode($order['order_code']);
        $pdo = Database::connection();
        $veh = null;
        if ($fresh['vehicle_id']) {
            $s = $pdo->prepare('SELECT code,name,status FROM vehicles WHERE id=?');
            $s->execute([$fresh['vehicle_id']]);
            $veh = $s->fetch() ?: null;
        }
        $this->json(['success' => true, 'data' => [
            'order_code' => $fresh['order_code'],
            'status' => $fresh['status'],
            'progress' => $snap['progress'],
            'vehicle' => $veh,
            'vehicle_lat' => $snap['vehicle_lat'],
            'vehicle_lng' => $snap['vehicle_lng'],
            'origin_lat' => $snap['origin_lat'],
            'origin_lng' => $snap['origin_lng'],
            'dest_lat' => $snap['dest_lat'],
            'dest_lng' => $snap['dest_lng'],
            'distance_km' => $snap['distance_km'],
            'eta_min' => $snap['eta_min'],
            'is_simulated' => true,
            'logs' => Order::logs((int)$fresh['id']),
        ]]);
    }
}
