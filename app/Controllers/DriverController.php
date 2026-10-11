<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Response;
use App\Models\Order;
use App\Services\DispatchService;

class DriverController extends Controller
{
    public function dashboard(array $p = []): void
    {
        $user = Auth::user();
        $pdo = Database::connection();
        // Pekerjaan tersedia: searching_driver tanpa kendaraan
        $jobs = $pdo->query("SELECT o.*, p.name AS package_name FROM orders o LEFT JOIN packages p ON p.id=o.package_id WHERE o.status='searching_driver' ORDER BY o.created_at ASC LIMIT 20")->fetchAll();
        $st = $pdo->prepare("SELECT o.*, p.name AS package_name FROM orders o LEFT JOIN packages p ON p.id=o.package_id WHERE o.vehicle_id IN (SELECT id FROM vehicles WHERE current_order_id IS NOT NULL) AND o.status IN ('assigned','driver_arriving','arrived','in_service') ORDER BY o.updated_at DESC LIMIT 20");
        $st->execute();
        // Sederhanakan: tugasku = order yang kendaraannya sedang dipakai & status aktif (demo: semua driver bisa lihat)
        $mine = $pdo->query("SELECT o.*, p.name AS package_name, v.code AS vehicle_code FROM orders o LEFT JOIN packages p ON p.id=o.package_id LEFT JOIN vehicles v ON v.id=o.vehicle_id WHERE o.status IN ('assigned','driver_arriving','arrived','in_service') AND o.vehicle_id IS NOT NULL ORDER BY o.updated_at DESC LIMIT 20")->fetchAll();
        $done = $pdo->query("SELECT COUNT(*) FROM orders WHERE status='completed'")->fetchColumn();
        $this->view('driver/index', ['title' => 'Driver Dashboard', 'user' => $user, 'jobs' => $jobs, 'mine' => $mine, 'done' => (int)$done], 'app');
    }

    public function availability(array $p = []): void
    {
        Csrf::requireValid();
        $user = Auth::user();
        $online = $this->input('online', '0') === '1' ? 1 : 0;
        Database::connection()->prepare('UPDATE users SET is_online=?, updated_at=? WHERE id=?')->execute([$online, gmdate('Y-m-d H:i:s'), $user['id']]);
        $isApi = str_starts_with(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/api/');
        if ($isApi) $this->json(['success' => true, 'data' => ['online' => (bool)$online]]);
        $_SESSION['flash'][] = ['type' => 'success', 'message' => $online ? 'Kamu ONLINE. Siap menerima orderan.' : 'Kamu OFFLINE.'];
        Response::redirect('/driver');
    }

    public function accept(array $p = []): void
    {
        Csrf::requireValid();
        $user = Auth::user();
        $id = (int)($p['id'] ?? $this->input('id', 0));
        $isApi = str_starts_with(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/api/');
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare("SELECT * FROM orders WHERE id=? LIMIT 1");
            $st->execute([$id]);
            $order = $st->fetch();
            if (!$order) {
                $pdo->rollBack();
                if ($isApi) $this->json(['success' => false, 'message' => 'Pesanan tidak ditemukan'], 404);
                Response::redirect('/driver');
                return;
            }
            if ($order['status'] !== 'searching_driver') {
                $pdo->rollBack();
                if ($isApi) $this->json(['success' => false, 'message' => 'Pesanan sudah diambil driver lain'], 409);
                $_SESSION['flash'][] = ['type' => 'error', 'message' => 'Pesanan sudah diambil / tidak tersedia.'];
                Response::redirect('/driver');
                return;
            }
            // Ambil kendaraan available untuk driver ini (simulasi: kendaraan pool)
            $v = $pdo->query("SELECT * FROM vehicles WHERE status='available' AND current_order_id IS NULL LIMIT 1")->fetch();
            if (!$v) {
                $pdo->rollBack();
                if ($isApi) $this->json(['success' => false, 'message' => 'Tidak ada kendaraan tersedia'], 409);
                $_SESSION['flash'][] = ['type' => 'error', 'message' => 'Tidak ada kendaraan tersedia.'];
                Response::redirect('/driver');
                return;
            }
            $now = gmdate('Y-m-d H:i:s');
            // Conditional update anti-race
            $up = $pdo->prepare("UPDATE orders SET status='assigned', vehicle_id=?, origin_latitude=?, origin_longitude=?, updated_at=? WHERE id=? AND status='searching_driver'");
            $up->execute([$v['id'], $v['latitude'], $v['longitude'], $now, $id]);
            if ($up->rowCount() !== 1) {
                $pdo->rollBack();
                if ($isApi) $this->json(['success' => false, 'message' => 'Kalah cepat, pesanan sudah diambil'], 409);
                $_SESSION['flash'][] = ['type' => 'error', 'message' => 'Kalah cepat! Pesanan sudah diambil.'];
                Response::redirect('/driver');
                return;
            }
            $pdo->prepare("UPDATE vehicles SET status='assigned', current_order_id=?, updated_at=? WHERE id=?")->execute([$id, $now, $v['id']]);
            DispatchService::log($pdo, $id, 'searching_driver', 'assigned', $user['id'], 'Diambil driver ' . $user['name'] . ' (' . $v['code'] . ').');
            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
        if ($isApi) $this->json(['success' => true, 'message' => 'Pesanan diterima']);
        $_SESSION['flash'][] = ['type' => 'success', 'message' => 'Pesanan diterima. Gaskeun!'];
        Response::redirect('/driver');
    }

    public function updateStatus(array $p = []): void
    {
        Csrf::requireValid();
        $user = Auth::user();
        $id = (int)($p['id'] ?? $this->input('id', 0));
        $to = (string)$this->input('status', '');
        $isApi = str_starts_with(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/api/');
        $pdo = Database::connection();
        $st = $pdo->prepare('SELECT * FROM orders WHERE id=? LIMIT 1');
        $st->execute([$id]);
        $order = $st->fetch();
        if (!$order) {
            if ($isApi) $this->json(['success' => false, 'message' => 'Tidak ditemukan'], 404);
            Response::redirect('/driver');
            return;
        }
        if (!Order::canTransition($order['status'], $to)) {
            if ($isApi) $this->json(['success' => false, 'message' => 'Transisi ' . $order['status'] . ' -> ' . $to . ' tidak valid'], 409);
            $_SESSION['flash'][] = ['type' => 'error', 'message' => 'Transisi status tidak valid.'];
            Response::redirect('/driver');
            return;
        }
        $pdo->beginTransaction();
        try {
            $now = gmdate('Y-m-d H:i:s');
            $up = $pdo->prepare('UPDATE orders SET status=?, updated_at=? WHERE id=? AND status=?');
            $up->execute([$to, $now, $id, $order['status']]);
            if ($up->rowCount() !== 1) {
                $pdo->rollBack();
                if ($isApi) $this->json(['success' => false, 'message' => 'Konflik status'], 409);
                Response::redirect('/driver');
                return;
            }
            DispatchService::log($pdo, $id, $order['status'], $to, $user['id'], 'Update oleh driver.');
            if ($to === 'completed') {
                $pdo->prepare('UPDATE orders SET completed_at=?, progress=1 WHERE id=?')->execute([$now, $id]);
                if ($order['vehicle_id']) {
                    $pdo->prepare("UPDATE vehicles SET status='available', current_order_id=NULL, updated_at=? WHERE id=?")->execute([$now, $order['vehicle_id']]);
                }
                $pdo->prepare("UPDATE payments SET status='paid', updated_at=? WHERE order_id=?")->execute([$now, $id]);
            } elseif ($to === 'cancelled') {
                $pdo->prepare('UPDATE orders SET cancelled_at=? WHERE id=?')->execute([$now, $id]);
                if ($order['vehicle_id']) {
                    $pdo->prepare("UPDATE vehicles SET status='available', current_order_id=NULL, updated_at=? WHERE id=?")->execute([$now, $order['vehicle_id']]);
                }
            } else {
                if ($order['vehicle_id']) {
                    $vs = $to === 'assigned' ? 'assigned' : 'on_trip';
                    $pdo->prepare('UPDATE vehicles SET status=?, updated_at=? WHERE id=?')->execute([$vs, $now, $order['vehicle_id']]);
                }
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
        if ($isApi) $this->json(['success' => true, 'message' => 'Status diperbarui', 'data' => ['status' => $to]]);
        $_SESSION['flash'][] = ['type' => 'success', 'message' => 'Status → ' . $to];
        Response::redirect('/driver');
    }

    public function orders(array $p = []): void
    {
        $this->dashboard($p);
    }
}
