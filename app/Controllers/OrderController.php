<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Response;
use App\Core\Validator;
use App\Models\Order;
use App\Models\Package;
use App\Models\Payment;
use App\Services\GeoService;
use App\Services\OrderService;

class OrderController extends Controller
{
    public function createPage(array $p = []): void
    {
        $packages = Package::active();
        $this->view('order/create', ['title' => 'Pesan Toilet — My Seled', 'packages' => $packages, 'idem' => bin2hex(random_bytes(16))], 'app');
    }

    public function store(array $p = []): void
    {
        Csrf::requireValid();
        $user = Auth::user();
        $isApi = str_starts_with(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/api/');
        $in = [
            'package_id' => (int)$this->input('package_id', 0),
            'latitude' => $this->input('latitude', ''),
            'longitude' => $this->input('longitude', ''),
            'address' => GeoService::normalizeAddress((string)$this->input('address', '')),
            'note' => substr(trim((string)$this->input('note', '')), 0, 500),
            'payment_method' => (string)$this->input('payment_method', 'cash_on_arrival'),
            'idempotency_key' => (string)$this->input('idempotency_key', ''),
        ];
        $errors = [];
        if (!Package::findActive($in['package_id'])) $errors['package_id'] = 'Paket tidak tersedia.';
        if (!Validator::lat($in['latitude'])) $errors['latitude'] = 'Koordinat lokasi tidak valid.';
        if (!Validator::lng($in['longitude'])) $errors['longitude'] = 'Koordinat lokasi tidak valid.';
        if (mb_strlen($in['address']) < 5) $errors['address'] = 'Alamat/note lokasi minimal 5 karakter.';
        if (!array_key_exists($in['payment_method'], Payment::METHODS)) $errors['payment_method'] = 'Metode pembayaran tidak valid.';
        if ($errors) {
            if ($isApi) $this->json(['success' => false, 'message' => 'Data pesanan tidak valid', 'errors' => $errors], 422);
            $packages = Package::active();
            $this->view('order/create', ['title' => 'Pesan Toilet — My Seled', 'packages' => $packages, 'errors' => $errors, 'old' => $in, 'idem' => $in['idempotency_key'] ?: bin2hex(random_bytes(16))], 'app');
            return;
        }
        $in['latitude'] = (float)$in['latitude'];
        $in['longitude'] = (float)$in['longitude'];
        // Cegah spam order ganda: max 1 order aktif? Izinkan beberapa, tapi throttle 30 detik
        try {
            $res = OrderService::create((int)$user['id'], $in);
        } catch (\InvalidArgumentException $e) {
            if ($isApi) $this->json(['success' => false, 'message' => $e->getMessage()], 422);
            $this->view('order/create', ['title' => 'Pesan Toilet', 'packages' => Package::active(), 'errors' => ['package_id' => $e->getMessage()], 'old' => $in, 'idem' => bin2hex(random_bytes(16))], 'app');
            return;
        }
        $order = $res['order'];
        if ($isApi) {
            $this->json(['success' => true, 'message' => $res['duplicate'] ? 'Pesanan sudah dibuat sebelumnya' : 'Pesanan berhasil dibuat', 'data' => ['order_code' => $order['order_code'], 'status' => $order['status']]], $res['duplicate'] ? 200 : 201);
        }
        Response::redirect('/tracking/' . $order['order_code']);
    }

    public function index(array $p = []): void
    {
        $user = Auth::user();
        $status = $_GET['status'] ?? 'all';
        $q = trim($_GET['q'] ?? '');
        $orders = Order::forUser((int)$user['id'], $status, $q ?: null);
        $this->view('orders/index', ['title' => 'Riwayat Pesanan', 'orders' => $orders, 'status' => $status, 'q' => $q], 'app');
    }

    public function detail(array $p = []): void
    {
        $user = Auth::user();
        $code = $p['orderCode'] ?? $p['code'] ?? '';
        $order = Order::findByCode($code);
        if (!$order || (int)$order['user_id'] !== (int)$user['id']) {
            http_response_code(404);
            $this->view('errors/404', ['title' => 'Pesanan Tidak Ditemukan'], 'app');
            return;
        }
        $pdo = Database::connection();
        $pkg = $pdo->prepare('SELECT * FROM packages WHERE id=?');
        $pkg->execute([$order['package_id']]);
        $veh = null;
        if ($order['vehicle_id']) {
            $s = $pdo->prepare('SELECT * FROM vehicles WHERE id=?');
            $s->execute([$order['vehicle_id']]);
            $veh = $s->fetch() ?: null;
        }
        $pay = $pdo->prepare('SELECT * FROM payments WHERE order_id=?');
        $pay->execute([$order['id']]);
        $logs = Order::logs((int)$order['id']);
        $this->view('orders/detail', ['title' => 'Pesanan ' . $order['order_code'], 'order' => $order, 'vehicle' => $veh, 'logs' => $logs, 'payment' => $pay->fetch() ?: null], 'app');
    }

    public function cancel(array $p = []): void
    {
        Csrf::requireValid();
        $user = Auth::user();
        $code = $p['orderCode'] ?? $p['code'] ?? (string)$this->input('order_code', '');
        $isApi = str_starts_with(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/api/');
        $order = Order::findByCode($code);
        if (!$order || (int)$order['user_id'] !== (int)$user['id']) {
            if ($isApi) $this->json(['success' => false, 'message' => 'Pesanan tidak ditemukan'], 404);
            http_response_code(404);
            $this->view('errors/404', ['title' => 'Tidak Ditemukan'], 'app');
            return;
        }
        try {
            OrderService::cancel($order, $user['id']);
        } catch (\RuntimeException $e) {
            if ($isApi) $this->json(['success' => false, 'message' => $e->getMessage()], 409);
            $_SESSION['flash'][] = ['type' => 'error', 'message' => $e->getMessage()];
            Response::redirect('/orders/' . $order['order_code']);
            return;
        }
        if ($isApi) $this->json(['success' => true, 'message' => 'Pesanan dibatalkan', 'data' => ['order_code' => $code, 'status' => 'cancelled']]);
        $_SESSION['flash'][] = ['type' => 'success', 'message' => 'Pesanan ' . $code . ' dibatalkan.'];
        Response::redirect('/orders/' . $code);
    }
}
