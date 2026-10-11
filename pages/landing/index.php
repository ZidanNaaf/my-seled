<?php
use App\Core\View;
$fmt = fn($n) => 'Rp' . number_format((int)$n, 0, ',', '.');
?>
<!-- HERO -->
<section class="grid md:grid-cols-2 gap-8 items-center py-10">
  <div>
    <div class="badge mb-3"><i class="fa-solid fa-truck-fast"></i> Startup Toilet Mobile • SIMULASI</div>
    <h1 class="text-4xl md:text-5xl font-extrabold leading-tight">When Nature Calls,<br>We Roll.</h1>
    <p class="muted mt-4 text-lg">Lagi kebelet? Tenang. My Seled siap mengirimkan singgasana berjalan ke lokasi kamu.</p>
    <div class="flex flex-wrap gap-3 mt-6">
      <a href="/register" class="btn btn-primary"><i class="fa-solid fa-toilet"></i> Pesan Toilet</a>
      <a href="#cara-kerja" class="btn btn-ghost"><i class="fa-solid fa-circle-info"></i> Lihat Cara Kerja</a>
    </div>
    <div class="muted text-xs mt-4">Tanpa antre. Tanpa drama. Tanpa tahan-tahan. (Iklan fiktif, jangan ditahan beneran.)</div>
  </div>
  <div class="card p-6">
    <!-- Ilustrasi SVG kendaraan toilet futuristik -->
    <svg viewBox="0 0 400 220" class="w-full h-auto" role="img" aria-label="Ilustrasi toilet mobile futuristik">
      <rect x="20" y="60" width="260" height="110" rx="18" fill="#292929" stroke="#383838"/>
      <rect x="40" y="80" width="90" height="70" rx="10" fill="#171717" stroke="#383838"/>
      <circle cx="85" cy="130" r="18" fill="none" stroke="#D4D4D4" stroke-width="5"/>
      <rect x="150" y="85" width="110" height="12" rx="6" fill="#383838"/>
      <rect x="150" y="105" width="80" height="10" rx="5" fill="#383838"/>
      <rect x="150" y="122" width="100" height="10" rx="5" fill="#383838"/>
      <circle cx="90" cy="180" r="22" fill="#101010" stroke="#D4D4D4" stroke-width="3"/>
      <circle cx="230" cy="180" r="22" fill="#101010" stroke="#D4D4D4" stroke-width="3"/>
      <foreignObject x="290" y="80" width="70" height="70"><div xmlns="http://www.w3.org/1999/xhtml" style="font-size:52px;color:#D4D4D4;text-align:center;"><i class="fa-solid fa-toilet"></i></div></foreignObject>
      <text x="40" y="40" fill="#A3A3A3" font-size="14">SL-007 • Throne Runner • SIMULASI</text>
    </svg>
    <div class="grid grid-cols-3 gap-2 mt-4 text-center text-sm">
      <div class="card-2 p-3"><div class="font-bold">~5 mnt</div><div class="muted text-xs">ETA simulasi</div></div>
      <div class="card-2 p-3"><div class="font-bold">4 armada</div><div class="muted text-xs">Fiktif</div></div>
      <div class="card-2 p-3"><div class="font-bold">24/7</div><div class="muted text-xs">Kebelet ready</div></div>
    </div>
  </div>
</section>

<!-- CARA KERJA -->
<section id="cara-kerja" class="py-10">
  <h2 class="text-2xl font-bold">Cara kerja</h2>
  <p class="muted">Tiga langkah menuju kelegaan.</p>
  <div class="grid md:grid-cols-3 gap-4 mt-4">
    <div class="card p-5"><div class="text-2xl"><i class="fa-solid fa-toilet"></i></div><h3 class="font-bold mt-2">1. Pilih toilet impianmu</h3><p class="muted text-sm">Basic buat darurat, Comfort buat santai, Sultan buat pengalaman tak terlupakan.</p></div>
    <div class="card p-5"><div class="text-2xl"><i class="fa-solid fa-location-dot"></i></div><h3 class="font-bold mt-2">2. Tentukan lokasi di peta</h3><p class="muted text-sm">Geser pin, cari alamat, atau pakai GPS. Kami yang meluncur, kamu yang duduk manis.</p></div>
    <div class="card p-5"><div class="text-2xl"><i class="fa-solid fa-truck-fast"></i></div><h3 class="font-bold mt-2">3. Toilet meluncur</h3><p class="muted text-sm">Pantau armada secara live. Estimasi & rute adalah simulasi yang jujur.</p></div>
  </div>
</section>

<!-- PAKET -->
<section id="paket" class="py-10">
  <h2 class="text-2xl font-bold">Pilihan paket</h2>
  <p class="muted">Harga fiktif untuk hiburan. Diambil live dari database.</p>
  <div class="grid md:grid-cols-3 gap-4 mt-4">
    <?php foreach (($packages ?? []) as $pkg): $f = json_decode($pkg['features_json'] ?? '[]', true) ?: []; ?>
    <div class="card p-5 flex flex-col">
      <div class="badge mb-2"><?= View::e($pkg['slug']) ?></div>
      <h3 class="font-bold text-xl"><?= View::e($pkg['name']) ?></h3>
      <div class="text-2xl font-extrabold mt-1"><?= $fmt($pkg['price']) ?></div>
      <p class="muted text-sm mt-2"><?= View::e($pkg['description']) ?></p>
      <ul class="text-sm mt-3 space-y-1"><?php foreach ($f as $ft): ?><li><i class="fa-solid fa-check text-[#4ADE80]"></i> <?= View::e($ft) ?></li><?php endforeach; ?></ul>
      <a href="/register" class="btn btn-primary w-full mt-4"><i class="fa-solid fa-arrow-right"></i> Pilih <?= View::e($pkg['name']) ?></a>
    </div>
    <?php endforeach; ?>
    <?php if (empty($packages)): ?><div class="card p-5 muted">Paket belum tersedia. Jalankan seed dulu.</div><?php endif; ?>
  </div>
</section>

<!-- KEUNGGULAN -->
<section class="py-10 grid md:grid-cols-2 gap-4">
  <div class="card p-5"><h3 class="font-bold"><i class="fa-solid fa-star"></i> Kenapa My Seled?</h3><ul class="text-sm mt-2 space-y-1 muted"><li>• Dispatch otomatis + tracking live (simulasi konsisten dengan DB).</li><li>• Harga snapshot — paket diubah admin, struk lama tetap utuh.</li><li>• Privasi: lokasimu hanya dipakai untuk orderanmu.</li><li>• Bisa dicoba lokal tanpa layanan berbayar.</li></ul></div>
  <div class="card p-5"><h3 class="font-bold"><i class="fa-solid fa-route"></i> Simulasi cara pemesanan</h3><ol class="text-sm mt-2 space-y-1 muted"><li>1. Daftar / masuk → 2. Buka <a class="underline" href="/order">/order</a> → 3. Geser pin → 4. Pilih paket → 5. Bayar (simulasi) → 6. Pantau di tracking.</li></ol><a href="/register" class="btn btn-ghost mt-3"><i class="fa-solid fa-play"></i> Coba sekarang</a></div>
</section>

<!-- FAQ -->
<section class="py-10">
  <h2 class="text-2xl font-bold">FAQ</h2>
  <div class="space-y-2 mt-4">
    <details class="card p-4"><summary class="font-semibold cursor-pointer">Apakah toiletnya beneran datang?</summary><p class="muted text-sm mt-2">Tidak. Ini proyek hiburan. Armadanya fiktif (SL-001, SL-007, dst), pergerakannya simulasi progress-on-poll.</p></details>
    <details class="card p-4"><summary class="font-semibold cursor-pointer">Apakah saya harus bayar?</summary><p class="muted text-sm mt-2">Tidak. Pembayaran 100% simulasi, tanpa gateway, tanpa QR asli.</p></details>
    <details class="card p-4"><summary class="font-semibold cursor-pointer">Apakah lokasi saya dikirim ke pihak ketiga?</summary><p class="muted text-sm mt-2">Tile OSM & pencarian Nominatim di-load browser. Koordinat order hanya tersimpan di SQLite lokal kamu.</p></details>
    <details class="card p-4"><summary class="font-semibold cursor-pointer">Butuh apa untuk menjalankan?</summary><p class="muted text-sm mt-2">PHP 8.2+ dengan pdo_sqlite. Tanpa npm/composer. Lihat README.</p></details>
  </div>
</section>
