<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Models\Package;

class HomeController extends Controller
{
    public function landing(array $p = []): void
    {
        $packages = [];
        try {
            $packages = Package::active();
        } catch (\Throwable $e) {
            $packages = [];
        }
        $this->view('landing/index', ['title' => 'My Seled — When Nature Calls, We Roll.', 'packages' => $packages], 'guest');
    }

    public function home(array $p = []): void
    {
        $user = Auth::user();
        // Defense-in-depth: middleware sudah memvalidasi, tapi jangan pernah
        // akses $user['...'] di atas null (stale session -> warning).
        if (!$user) {
            Auth::logout();
            \App\Core\Response::redirect('/login');
        }
        $pdo = Database::connection();
        $st = $pdo->prepare("SELECT o.*, p.name AS package_name, v.code AS vehicle_code FROM orders o LEFT JOIN packages p ON p.id=o.package_id LEFT JOIN vehicles v ON v.id=o.vehicle_id WHERE o.user_id=? ORDER BY o.created_at DESC LIMIT 5");
        $st->execute([$user['id']]);
        $recent = $st->fetchAll();
        $st = $pdo->prepare("SELECT * FROM orders WHERE user_id=? AND status IN ('pending','searching_driver','assigned','driver_arriving','arrived','in_service') ORDER BY created_at DESC LIMIT 1");
        $st->execute([$user['id']]);
        $active = $st->fetch() ?: null;
        $total = (int)$pdo->query('SELECT COUNT(*) FROM orders WHERE user_id=' . (int)$user['id'])->fetchColumn();
        $packages = Package::active();
        $this->view('home/index', ['title' => 'Dashboard — My Seled', 'user' => $user, 'recent' => $recent, 'active' => $active, 'total' => $total, 'packages' => array_slice($packages, 0, 3)], 'app');
    }
}
