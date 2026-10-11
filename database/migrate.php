<?php
declare(strict_types=1);
// Migrasi idempotent: jalankan schema.sql
require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Database;

$sql = file_get_contents(__DIR__ . '/schema.sql');
$pdo = Database::connection();
// Eksekusi per-statement (SQLite tidak selalu support multi-statement)
$statements = array_filter(array_map('trim', explode(';', $sql)));
foreach ($statements as $st) {
    if ($st === '' || str_starts_with($st, 'PRAGMA')) {
        $pdo->exec($st);
        continue;
    }
    $pdo->exec($st . ';');
}
echo "Migrasi OK\n";
