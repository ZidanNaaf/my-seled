<?php
// Konfigurasi database
$dbFile = getenv('DB_PATH');
if (!$dbFile) {
    $dbFile = dirname(__DIR__) . '/storage/database/myseled.sqlite';
}
return [
    'dsn' => 'sqlite:' . $dbFile,
    'path' => $dbFile,
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ],
];
