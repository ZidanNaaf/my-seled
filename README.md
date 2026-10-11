# My Seled — When Nature Calls, We Roll.

Aplikasi pemesanan toilet mobile futuristik (SIMULASI hiburan). Full-stack PHP native + SQLite + Leaflet/OSM. Tanpa framework, tanpa npm/composer.

> Semua armada, harga, tracking, dan pembayaran adalah **SIMULASI fiktif**. Jangan dipakai saat kebelet beneran.

## Teknologi

- Backend: PHP 8.2+ native, PDO SQLite, session auth, router manual, API JSON.
- Frontend: HTML5 + Tailwind CDN + vanilla JS + Leaflet.js + OSM + Lucide + SweetAlert2.
- DB: SQLite (`storage/database/myseled.sqlite`), migrasi idempotent, seed paket + kendaraan.
- Infra: `public/` sebagai document root. Support Apache `.htaccess` + PHP built-in server.

## Struktur

```
my-seled/
  public/ (index.php, .htaccess, router-stub.php, assets/)
  app/ (Core, Controllers, Models, Services, Middleware, bootstrap.php)
  config/ routes/ pages/ components/layouts/
  database/ (schema.sql, migrate.php, seed.php)
  storage/ (database, logs, cache)
  tools/install.php  tests/run.php
```

## Syarat

- PHP 8.2+ dengan ekstensi `pdo_sqlite` aktif.
- Tidak butuh npm/composer. Koneksi internet hanya untuk CDN + tile OSM (opsional, ada fallback).

## Instalasi (Windows / XAMPP / Linux)

```powershell
cd my-seled
php tools/install.php --fresh --seed --admin="Admin,admin@myseled.local,PasswordKuat123"
php tools/install.php --demo-users   # opsional, dev only: user@demo.local & driver@demo.local / password123
php -S localhost:8000 -t public public/router-stub.php
# buka http://localhost:8000
```

Linux/shared hosting (Apache):

1. Upload semua file. Arahkan DocumentRoot ke `public/`. Jika tidak bisa, tambahkan proteksi (storage/ sudah ada `.htaccess` deny).
2. Pastikan `storage/` writable oleh PHP (755 file, 775 dir secukupnya — jangan 777).
3. Jalankan sekali via SSH/CLI: `php tools/install.php --fresh --seed --admin="..."`. Hapus akun demo sebelum production.
4. Geolocation butuh HTTPS atau localhost.

## Akun

- User: daftar di `/register`.
- Driver: daftar lalu minta admin ubah role ke `driver` di `/admin/users` (atau edit DB).
- Admin: hanya via CLI `--admin` (tidak ada password default).

## Peta & Routing

- Tile: OpenStreetMap. Search: Nominatim (debounce 600ms, limit 5, `countrycodes=id`).
- Rute: coba OSRM demo sekali (`router.project-osrm.org`), gagal → fallback garis lurus + label "simulasi". Jangan anggap sebagai SLA production.
- Simulasi: **progress-on-poll** — tiap `GET /api/tracking/{code}` (poll 5s) memajukan `progress` + posisi kendaraan + status (`assigned → driver_arriving → arrived → in_service → completed`). Tanpa cron/daemon. Kecepatan: `sim_speed_per_sec=0.02` (~50 dtk sampai tiba).

## API

Sukses: `{success:true, message, data}`. Error: `{success:false, message, errors?}` + status 200/201/400/401/403/404/409/422/429/500.

## Testing

```powershell
php tests/run.php
```

Mencakup: skema, seed, FSM, hash, create order + dispatch + idempotency, harga dari DB, tracking monotonic, cancel terlarang → 409, FK, validator koordinat.

## Troubleshooting

- `could not find driver`: aktifkan `extension=pdo_sqlite` di `php.ini`.
- `database is locked`: WAL + busy_timeout sudah aktif; retry. Jangan buka SQLite via dua writer berat bersamaan.
- Warning `php_mbstring.dll blocked` di Windows terkunci: pakai PHP dari XAMPP atau copy `php.ini` yang sesuai; pastikan `pdo_sqlite` ter-load (`php -m`).
- Peta blank: cek internet/CDN Leaflet. Order manual tetap bisa (isi lat/lng + alamat).
- 404 semua route di Apache: aktifkan `mod_rewrite` + `AllowOverride All`.

## Checklist keamanan sebelum production

- [ ] `APP_DEBUG=false`, hapus demo users, ganti admin password kuat.
- [ ] DocumentRoot ke `public/`, `storage/` tidak bisa diakses browser.
- [ ] HTTPS aktif + `SESSION_SECURE=true`.
- [ ] `storage/logs` dipantau, error tidak bocor ke user.
- [ ] Rate-limit login aktif (default 8x/10 mnt).
- [ ] Backup SQLite berkala.
