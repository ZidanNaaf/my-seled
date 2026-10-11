<?php
declare(strict_types=1);
// Test runner native: php tests/run.php (tanpa PHPUnit)
require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Database;

$pass = 0;
$fail = 0;
function ok(bool $cond, string $name, string $detail = ''): void
{
    global $pass, $fail;
    if ($cond) {
        $pass++;
        echo "PASS $name\n";
    } else {
        $fail++;
        echo "FAIL $name $detail\n";
    }
}

// Pastikan DB termigrasi + seed untuk test
require BASE_PATH . '/database/migrate.php';
require BASE_PATH . '/database/seed.php';

$pdo = Database::connection();

// 1. Skema ada
foreach (['users', 'packages', 'vehicles', 'orders', 'order_status_logs', 'payments', 'vehicle_location_logs', 'app_settings'] as $t) {
    $r = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='$t'")->fetch();
    ok(!!$r, "table:$t");
}

// 2. Seed paket & kendaraan
ok((int)$pdo->query('SELECT COUNT(*) FROM packages WHERE is_active=1')->fetchColumn() >= 3, 'seed:packages>=3');
ok((int)$pdo->query("SELECT COUNT(*) FROM vehicles WHERE status='available'")->fetchColumn() >= 1, 'seed:vehicles-available');

// 3. State machine
ok(\App\Models\Order::canTransition('pending', 'searching_driver'), 'fsm:pending->searching');
ok(!\App\Models\Order::canTransition('pending', 'completed'), 'fsm:pending!->completed');
ok(!\App\Models\Order::canTransition('completed', 'cancelled'), 'fsm:completed-terminal');

// 4. Register + login password
$email = 'test_' . time() . '@local';
$id = \App\Models\User::create(['name' => 'Test User', 'username' => 'tu_' . time(), 'email' => $email, 'phone' => '0812', 'password_hash' => password_hash('secret123', PASSWORD_DEFAULT), 'role' => 'user']);
$u = \App\Models\User::findById($id);
ok($u && password_verify('secret123', $u['password_hash']), 'auth:hash-verify');
ok(!password_verify('salah', $u['password_hash']), 'auth:wrong-reject');

// 5. OrderService create + dispatch + snapshot
$res = \App\Services\OrderService::create($id, [
    'package_id' => 1, 'latitude' => -6.21, 'longitude' => 106.82,
    'address' => 'Jl. Test No. 1', 'note' => '', 'payment_method' => 'cash_on_arrival',
    'idempotency_key' => 'test-' . time(),
]);
ok(!empty($res['order']['order_code']), 'order:create-code');
ok(str_starts_with($res['order']['order_code'], 'MS-'), 'order:code-prefix');
ok(in_array($res['order']['status'], ['assigned', 'searching_driver']), 'order:dispatched-or-queued');
$code = $res['order']['order_code'];

// 6. Idempotency: buat lagi dengan key sama -> order sama
$res2 = \App\Services\OrderService::create($id, [
    'package_id' => 1, 'latitude' => -6.21, 'longitude' => 106.82,
    'address' => 'Jl. Test No. 1', 'note' => '', 'payment_method' => 'cash_on_arrival',
    'idempotency_key' => $res['order']['idempotency_key'],
]);
ok($res2['duplicate'] && $res2['order']['order_code'] === $code, 'order:idempotency');

// 7. Harga manipulasi diabaikan (snapshot dari DB)
$pdo->prepare('UPDATE packages SET price=999999 WHERE id=1')->execute();
$res3 = \App\Services\OrderService::create($id, [
    'package_id' => 2, 'latitude' => -6.21, 'longitude' => 106.82,
    'address' => 'Jl. Test 2', 'note' => '', 'payment_method' => 'ewallet',
    'idempotency_key' => 'k-' . time() . rand(1, 99999),
]);
ok((int)$res3['order']['total'] < 100000, 'order:price-from-db');
$pdo->prepare('UPDATE packages SET price=9900 WHERE id=1')->execute();

// 8. Tracking advance monotonic
$o = \App\Models\Order::findByCode($code);
$s1 = \App\Services\TrackingService::advance($o);
$o2 = \App\Models\Order::findByCode($code);
$s2 = \App\Services\TrackingService::advance($o2);
ok($s2['progress'] >= $s1['progress'], 'tracking:monotonic');
ok($s2['is_simulated'] === true, 'tracking:simulated-flag');

// 9. Cancel hanya status diizinkan
try {
    // Paksa ke completed lalu coba cancel -> harus 409
    $pdo->prepare("UPDATE orders SET status='completed' WHERE order_code=?")->execute([$res3['order']['order_code']]);
    $oc = \App\Models\Order::findByCode($res3['order']['order_code']);
    \App\Services\OrderService::cancel($oc, $id);
    ok(false, 'order:cancel-blocked');
} catch (\RuntimeException $e) {
    ok($e->getCode() === 409 || str_contains($e->getMessage(), 'tidak dapat dibatalkan'), 'order:cancel-blocked');
}

// 10. FK: order user invalid ditolak
try {
    $pdo->prepare('INSERT INTO orders(order_code,user_id,package_id,status,destination_address,destination_latitude,destination_longitude,package_name_snapshot,package_price_snapshot,subtotal,total,payment_status,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?)')
        ->execute(['MS-FAKE99', 999999, 1, 'pending', 'x', -6.2, 106.8, 'Basic', 9900, 9900, 9900, 'unpaid', gmdate('Y-m-d H:i:s'), gmdate('Y-m-d H:i:s')]);
    ok(false, 'db:fk-enforced');
} catch (\Throwable $e) {
    ok(true, 'db:fk-enforced');
}

// 11. Validator koordinat
ok(!\App\Core\Validator::lat(999), 'valid:lat-reject');
ok(!\App\Core\Validator::lng(999), 'valid:lng-reject');
ok(\App\Core\Validator::lat(-6.2) && \App\Core\Validator::lng(106.8), 'valid:latlng-ok');

echo "\n== Hasil: $pass PASS, $fail FAIL ==\n";
exit($fail > 0 ? 1 : 0);
