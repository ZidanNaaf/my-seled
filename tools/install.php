<?php
declare(strict_types=1);
// CLI installer: php tools/install.php --fresh --seed --admin="Nama,email,password" [--demo-users]
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Hanya via CLI.');
}
require_once __DIR__ . '/../app/bootstrap.php';

$opts = getopt('', ['fresh', 'seed', 'admin:', 'demo-users']);
$pdo = \App\Core\Database::connection();

if (isset($opts['fresh'])) {
    $dbCfg = require BASE_PATH . '/config/database.php';
    // Tutup koneksi lalu hapus file agar fresh
    $pdo = null;
    // Reset singleton via refleksi sederhana
    $ref = new ReflectionClass(\App\Core\Database::class);
    $prop = $ref->getProperty('pdo');
    $prop->setAccessible(true);
    $prop->setValue(null, null);
    if (is_file($dbCfg['path'])) {
        unlink($dbCfg['path']);
        echo "DB lama dihapus.\n";
    }
    $pdo = \App\Core\Database::connection();
}

require BASE_PATH . '/database/migrate.php';
if (isset($opts['seed']) || isset($opts['fresh'])) {
    require BASE_PATH . '/database/seed.php';
}

if (!empty($opts['admin'])) {
    // Format: "Nama,email,password"
    $parts = explode(',', $opts['admin'], 3);
    if (count($parts) !== 3) {
        fwrite(STDERR, "Format --admin=\"Nama,email,password\"\n");
        exit(1);
    }
    [$name, $email, $pass] = array_map('trim', $parts);
    if (strlen($pass) < 8) {
        fwrite(STDERR, "Password admin minimal 8 karakter.\n");
        exit(1);
    }
    $pdo = \App\Core\Database::connection();
    $st = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $st->execute([$email]);
    $now = gmdate('Y-m-d H:i:s');
    $username = strtolower(preg_replace('/[^a-z0-9]+/i', '_', explode('@', $email)[0])) . '_admin';
    if ($row = $st->fetch()) {
        $pdo->prepare("UPDATE users SET name=?, username=?, password_hash=?, role='admin', is_active=1, updated_at=? WHERE id=?")
            ->execute([$name, $username, password_hash($pass, PASSWORD_DEFAULT), $now, $row['id']]);
        echo "Admin diperbarui: $email\n";
    } else {
        $pdo->prepare("INSERT INTO users(name,username,email,password_hash,role,is_active,created_at,updated_at) VALUES(?,?,?,?, 'admin',1,?,?)")
            ->execute([$name, $username, $email, password_hash($pass, PASSWORD_DEFAULT), $now, $now]);
        echo "Admin dibuat: $email (username: $username)\n";
    }
}

if (isset($opts['demo-users'])) {
    $now = gmdate('Y-m-d H:i:s');
    $demos = [
        ['Demo User', 'demouser', 'user@demo.local', 'password123', 'user'],
        ['Demo Driver', 'demodriver', 'driver@demo.local', 'password123', 'driver'],
    ];
    foreach ($demos as $d) {
        $st = $pdo->prepare('SELECT id FROM users WHERE email=? LIMIT 1');
        $st->execute([$d[2]]);
        if (!$st->fetch()) {
            $pdo->prepare('INSERT INTO users(name,username,email,password_hash,role,is_active,created_at,updated_at) VALUES(?,?,?,?,?,1,?,?)')
                ->execute([$d[0], $d[1], $d[2], password_hash($d[3], PASSWORD_DEFAULT), $d[4], $now, $now]);
            echo "Demo dibuat: {$d[2]} / password123\n";
        }
    }
}

echo "Install selesai.\n";
echo "Jalankan: php -S localhost:8000 -t public public/router-stub.php\n";
