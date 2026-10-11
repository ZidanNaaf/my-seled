<?php
declare(strict_types=1);
// Seed paket + kendaraan fiktif
require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Database;

$pdo = Database::connection();
$now = gmdate('Y-m-d H:i:s');

$packages = [
    ['Basic', 'basic', 'Toilet mobile standar. Bersih, cepat, tanpa drama.', 9900, ['Toilet standar', 'Estimasi layanan normal', 'Fasilitas dasar']],
    ['Comfort', 'comfort', 'Ruang lebih lega + fasilitas tambahan + prioritas dispatch.', 19900, ['Ruang lebih nyaman', 'Tisu premium + wastafel', 'Prioritas dispatch simulasi']],
    ['Sultan', 'sultan', 'Singgasana premium dengan kendaraan eksklusif.', 49900, ['Toilet premium', 'AC + pewangi + musik', 'Kendaraan eksklusif (simulasi)']],
];
foreach ($packages as $i => $p) {
    $st = $pdo->prepare('SELECT id FROM packages WHERE slug = ?');
    $st->execute([$p[1]]);
    if ($st->fetch()) {
        $pdo->prepare('UPDATE packages SET name=?, description=?, price=?, features_json=?, is_active=1, updated_at=? WHERE slug=?')
            ->execute([$p[0], $p[2], $p[3], json_encode($p[4], JSON_UNESCAPED_UNICODE), $now, $p[1]]);
    } else {
        $pdo->prepare('INSERT INTO packages(name,slug,description,price,features_json,is_active,sort_order,created_at,updated_at) VALUES(?,?,?,?,?,?,?, ?,?)')
            ->execute([$p[0], $p[1], $p[2], $p[3], json_encode($p[4], JSON_UNESCAPED_UNICODE), 1, $i, $now, $now]);
    }
}

$vehicles = [
    ['SL-001', 'The Poop Express', -6.2000, 106.8000],
    ['SL-007', 'Throne Runner', -6.2300, 106.8300],
    ['SL-013', 'Sultan Mobile', -6.1800, 106.8500],
    ['SL-021', 'Emergency Throne', -6.2500, 106.7900],
];
foreach ($vehicles as $v) {
    $st = $pdo->prepare('SELECT id FROM vehicles WHERE code = ?');
    $st->execute([$v[0]]);
    if (!$st->fetch()) {
        $pdo->prepare("INSERT INTO vehicles(code,name,type,capacity,status,latitude,longitude,updated_at,created_at) VALUES(?,?,?,?, 'available',?,?,?,?)")
            ->execute([$v[0], $v[1], 'mobile_toilet', 1, $v[2], $v[3], $now, $now]);
    }
}

$settings = [
    'app_name' => 'My Seled',
    'tagline' => 'When Nature Calls, We Roll.',
    'contact_demo' => 'halo@myseled.local | 0800-SELED-00 (demo)',
    'demo_mode' => '1',
    'sim_speed' => '0.02',
];
foreach ($settings as $k => $val) {
    $pdo->prepare('INSERT INTO app_settings(key,value,updated_at) VALUES(?,?,?) ON CONFLICT(key) DO UPDATE SET value=excluded.value, updated_at=excluded.updated_at')
        ->execute([$k, $val, $now]);
}

echo "Seed OK\n";
