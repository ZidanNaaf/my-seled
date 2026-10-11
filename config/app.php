<?php
// Konfigurasi aplikasi My Seled
return [
    'name' => getenv('APP_NAME') ?: 'My Seled',
    'tagline' => 'When Nature Calls, We Roll.',
    'tagline_id' => 'Lagi kebelet? Tenang. My Seled siap mengirimkan singgasana berjalan ke lokasi kamu.',
    'env' => getenv('APP_ENV') ?: 'local',
    'debug' => (getenv('APP_DEBUG') ?: 'true') === 'true',
    'url' => getenv('APP_URL') ?: 'http://localhost:8000',
    'session_name' => 'MYSELEDSESSID',
    // Kecepatan simulasi: fraksi progress per detik (demo cepat agar bisa dicoba)
    'sim_speed_per_sec' => 0.02, // ~50 detik sampai tiba
    'tracking_poll_ms' => 5000,
    'demo_mode' => true,
    'contact_demo' => 'halo@myseled.local | 0800-SELED-00 (demo)',
];
