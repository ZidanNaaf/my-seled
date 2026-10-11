<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Response;
use App\Models\Order;
use App\Services\DispatchService;

class AdminController extends Controller
{
    public function dashboard(array $p = []): void
    {
        $pdo = Database::connection();
        $stats = [
            'users' => (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
            'orders' => (int)$pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
            'active' => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status IN ('pending','searching_driver','assigned','driver_arriving','arrived','in_service')")->fetchColumn(),
            'done' => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status='completed'")->fetchColumn(),
            'cancelled' => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status='cancelled'")->fetchColumn(),
            'revenue' => (int)$pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status='completed'")->fetchColumn(),
            'veh_avail' => (int)$pdo->query("SELECT COUNT(*) FROM vehicles WHERE status='available'")->fetchColumn(),
            'veh_trip' => (int)$pdo->query("SELECT COUNT(*) FROM vehicles WHERE status IN ('assigned','on_trip')")->fetchColumn(),
        ];
        $recent = $pdo->query("SELECT o.*, u.name AS user_name FROM orders o LEFT JOIN users u ON u.id=o.user_id ORDER BY o.created_at DESC LIMIT 8")->fetchAll();
        $perDay = $pdo->query("SELECT substr(created_at,1,10) AS d, COUNT(*) c FROM orders GROUP BY d ORDER BY d DESC LIMIT 7")->fetchAll();
        $this->view('admin/index', ['title' => 'Admin — My Seled', 'stats' => $stats, 'recent' => $recent, 'perDay' => $perDay], 'app');
    }

    public function orders(array $p = []): void
    {
        $pdo = Database::connection();
        $status = $_GET['status'] ?? 'all';
        $q = trim($_GET['q'] ?? '');
        $sql = "SELECT o.*, u.name AS user_name, v.code AS vehicle_code FROM orders o LEFT JOIN users u ON u.id=o.user_id LEFT JOIN vehicles v ON v.id=o.vehicle_id WHERE 1=1";
        $params = [];
        if ($status !== 'all' && $status !== '') {
            $sql .= ' AND o.status=?';
            $params[] = $status;
        }
        if ($q !== '') {
            $sql .= ' AND o.order_code LIKE ?';
            $params[] = '%' . $q . '%';
        }
        $sql .= ' ORDER BY o.created_at DESC LIMIT 100';
        $st = $pdo->prepare($sql);
        $st->execute($params);
        $this->view('admin/orders', ['title' => 'Kelola Pesanan', 'orders' => $st->fetchAll(), 'status' => $status, 'q' => $q], 'app');
    }

    public function users(array $p = []): void
    {
        $pdo = Database::connection();
        $q = trim($_GET['q'] ?? '');
        if ($q !== '') {
            $st = $pdo->prepare('SELECT * FROM users WHERE name LIKE ? OR email LIKE ? OR username LIKE ? ORDER BY created_at DESC LIMIT 100');
            $st->execute(['%' . $q . '%', '%' . $q . '%', '%' . $q . '%']);
        } else {
            $st = $pdo->query('SELECT * FROM users ORDER BY created_at DESC LIMIT 100');
        }
        $this->view('admin/users', ['title' => 'Kelola Pengguna', 'users' => $st->fetchAll(), 'q' => $q], 'app');
    }

    public function updateUserRole(array $p = []): void
    {
        Csrf::requireValid();
        $pdo = Database::connection();
        $id = (int)$this->input('id', 0);
        $role = (string)$this->input('role', '');
        if (!in_array($role, ['user', 'driver', 'admin'], true)) {
            $_SESSION['flash'][] = ['type' => 'error', 'message' => 'Role tidak valid.'];
            Response::redirect('/admin/users');
            return;
        }
        $me = \App\Core\Auth::user();
        if ($me && (int)$me['id'] === $id && $role !== 'admin') {
            $_SESSION['flash'][] = ['type' => 'error', 'message' => 'Tidak bisa menurunkan role diri sendiri.'];
            Response::redirect('/admin/users');
            return;
        }
        // Cegah admin terakhir hilang
        if ($role !== 'admin') {
            $target = $pdo->prepare('SELECT role FROM users WHERE id=?');
            $target->execute([$id]);
            $t = $target->fetch();
            if ($t && $t['role'] === 'admin') {
                $count = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='admin' AND is_active=1")->fetchColumn();
                if ($count <= 1) {
                    $_SESSION['flash'][] = ['type' => 'error', 'message' => 'Tidak bisa mengubah admin terakhir.'];
                    Response::redirect('/admin/users');
                    return;
                }
            }
        }
        $pdo->prepare('UPDATE users SET role=?, updated_at=? WHERE id=?')->execute([$role, gmdate('Y-m-d H:i:s'), $id]);
        app_log('ADMIN role change id=' . $id . ' -> ' . $role . ' by ' . ($me['email'] ?? '?'));
        $_SESSION['flash'][] = ['type' => 'success', 'message' => 'Role diperbarui.'];
        Response::redirect('/admin/users');
    }

    public function toggleUser(array $p = []): void
    {
        Csrf::requireValid();
        $pdo = Database::connection();
        $id = (int)$this->input('id', 0);
        $me = \App\Core\Auth::user();
        $st = $pdo->prepare('SELECT * FROM users WHERE id=? LIMIT 1');
        $st->execute([$id]);
        $t = $st->fetch();
        if (!$t) {
            Response::redirect('/admin/users');
            return;
        }
        if ($t['role'] === 'admin' && (int)$t['is_active'] === 1) {
            $count = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='admin' AND is_active=1")->fetchColumn();
            if ($count <= 1) {
                $_SESSION['flash'][] = ['type' => 'error', 'message' => 'Tidak bisa menonaktifkan admin terakhir.'];
                Response::redirect('/admin/users');
                return;
            }
        }
        if ($me && (int)$me['id'] === $id) {
            $_SESSION['flash'][] = ['type' => 'error', 'message' => 'Tidak bisa menonaktifkan diri sendiri.'];
            Response::redirect('/admin/users');
            return;
        }
        $pdo->prepare('UPDATE users SET is_active=?, updated_at=? WHERE id=?')->execute([(int)$t['is_active'] === 1 ? 0 : 1, gmdate('Y-m-d H:i:s'), $id]);
        $_SESSION['flash'][] = ['type' => 'success', 'message' => 'Status akun diperbarui.'];
        Response::redirect('/admin/users');
    }

    public function vehicles(array $p = []): void
    {
        $pdo = Database::connection();
        $vehicles = $pdo->query('SELECT * FROM vehicles ORDER BY code ASC')->fetchAll();
        $this->view('admin/vehicles', ['title' => 'Kelola Kendaraan', 'vehicles' => $vehicles], 'app');
    }

    public function saveVehicle(array $p = []): void
    {
        Csrf::requireValid();
        $pdo = Database::connection();
        $id = (int)$this->input('id', 0);
        $code = strtoupper(trim((string)$this->input('code', '')));
        $name = trim((string)$this->input('name', ''));
        $status = (string)$this->input('status', 'available');
        $lat = (float)$this->input('latitude', -6.2);
        $lng = (float)$this->input('longitude', 106.81);
        if ($code === '' || $name === '' || !in_array($status, ['available', 'assigned', 'on_trip', 'maintenance', 'offline'], true)) {
            $_SESSION['flash'][] = ['type' => 'error', 'message' => 'Data kendaraan tidak valid.'];
            Response::redirect('/admin/vehicles');
            return;
        }
        $now = gmdate('Y-m-d H:i:s');
        if ($id > 0) {
            $cur = $pdo->prepare('SELECT * FROM vehicles WHERE id=?');
            $cur->execute([$id]);
            $c = $cur->fetch();
            if ($c && $c['current_order_id'] && in_array($status, ['maintenance', 'offline'], true)) {
                $_SESSION['flash'][] = ['type' => 'error', 'message' => 'Kendaraan masih bertugas, tidak bisa maintenance/offline.'];
                Response::redirect('/admin/vehicles');
                return;
            }
            $pdo->prepare('UPDATE vehicles SET code=?, name=?, status=?, latitude=?, longitude=?, updated_at=? WHERE id=?')
                ->execute([$code, $name, $status, $lat, $lng, $now, $id]);
        } else {
            $pdo->prepare("INSERT INTO vehicles(code,name,type,capacity,status,latitude,longitude,updated_at,created_at) VALUES(?,?, 'mobile_toilet',1,?,?,?, ?,?)")
                ->execute([$code, $name, $status, $lat, $lng, $now, $now]);
        }
        $_SESSION['flash'][] = ['type' => 'success', 'message' => 'Kendaraan disimpan.'];
        Response::redirect('/admin/vehicles');
    }

    public function packages(array $p = []): void
    {
        $pdo = Database::connection();
        $packages = $pdo->query('SELECT * FROM packages ORDER BY price ASC')->fetchAll();
        $this->view('admin/packages', ['title' => 'Kelola Paket', 'packages' => $packages], 'app');
    }

    public function savePackage(array $p = []): void
    {
        Csrf::requireValid();
        $pdo = Database::connection();
        $id = (int)$this->input('id', 0);
        $name = trim((string)$this->input('name', ''));
        $price = (int)$this->input('price', 0);
        $desc = trim((string)$this->input('description', ''));
        $active = $this->input('is_active', '1') === '1' ? 1 : 0;
        if ($name === '' || $price < 0) {
            $_SESSION['flash'][] = ['type' => 'error', 'message' => 'Nama/harga tidak valid.'];
            Response::redirect('/admin/packages');
            return;
        }
        $now = gmdate('Y-m-d H:i:s');
        if ($id > 0) {
            $pdo->prepare('UPDATE packages SET name=?, price=?, description=?, is_active=?, updated_at=? WHERE id=?')
                ->execute([$name, $price, $desc, $active, $now, $id]);
        } else {
            $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name)) . '-' . substr(bin2hex(random_bytes(2)), 0, 4);
            $pdo->prepare('INSERT INTO packages(name,slug,description,price,features_json,is_active,sort_order,created_at,updated_at) VALUES(?,?,?,?,?, ?,0,?,?)')
                ->execute([$name, $slug, $desc, $price, json_encode(['Fasilitas standar'], JSON_UNESCAPED_UNICODE), $active, $now, $now]);
        }
        $_SESSION['flash'][] = ['type' => 'success', 'message' => 'Paket disimpan. Harga lama pada pesanan tidak ikut berubah (snapshot).'];
        Response::redirect('/admin/packages');
    }

    public function settings(array $p = []): void
    {
        $pdo = Database::connection();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Csrf::requireValid();
            // Whitelist: hanya key non-sensitif
            $allowed = ['app_name', 'contact_demo', 'demo_mode'];
            $now = gmdate('Y-m-d H:i:s');
            foreach ($allowed as $k) {
                if (isset($_POST[$k])) {
                    $v = substr(trim((string)$_POST[$k]), 0, 200);
                    $pdo->prepare('INSERT INTO app_settings(key,value,updated_at) VALUES(?,?,?) ON CONFLICT(key) DO UPDATE SET value=excluded.value, updated_at=excluded.updated_at')
                        ->execute([$k, $v, $now]);
                }
            }
            $_SESSION['flash'][] = ['type' => 'success', 'message' => 'Pengaturan disimpan.'];
            Response::redirect('/admin/settings');
            return;
        }
        $rows = $pdo->query('SELECT * FROM app_settings')->fetchAll();
        $s = [];
        foreach ($rows as $r) $s[$r['key']] = $r['value'];
        $this->view('admin/settings', ['title' => 'Pengaturan', 's' => $s], 'app');
    }

    public function adminOrderDetail(array $p = []): void
    {
        $pdo = Database::connection();
        $code = $p['code'] ?? '';
        $st = $pdo->prepare("SELECT o.*, u.name AS user_name, u.email AS user_email, v.code AS vehicle_code FROM orders o LEFT JOIN users u ON u.id=o.user_id LEFT JOIN vehicles v ON v.id=o.vehicle_id WHERE o.order_code=? LIMIT 1");
        $st->execute([$code]);
        $order = $st->fetch();
        if (!$order) {
            http_response_code(404);
            $this->view('errors/404', ['title' => 'Tidak Ditemukan'], 'app');
            return;
        }
        $logs = Order::logs((int)$order['id']);
        $this->view('admin/order-detail', ['title' => 'Detail ' . $code, 'order' => $order, 'logs' => $logs], 'app');
    }
}
