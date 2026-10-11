<?php
use App\Core\View;
$fmt = fn($n) => 'Rp' . number_format((int)$n, 0, ',', '.');
$old = $old ?? [];
$bootPackages = array_map(fn($p) => ['id' => $p['id'], 'name' => $p['name'], 'price' => (int)$p['price'], 'description' => $p['description'] ?? ''], $packages ?? []);
?>
<div id="vue-order">
<h1 class="text-2xl font-bold"><i class="fa-solid fa-truck-fast"></i> Pesan Toilet</h1>
<p class="muted text-sm">Geser pin → pilih paket → konfirmasi. Kurang dari semenit.</p>

<?php if (!empty($errors)): ?><div class="card p-3 mt-3 text-sm" style="border-color:#5c2424"><?php foreach ($errors as $k => $e): ?><div style="color:#F87171"><?= View::e($k) ?>: <?= View::e($e) ?></div><?php endforeach; ?></div><?php endif; ?>

<form id="order-form" method="POST" action="/orders" class="grid md:grid-cols-2 gap-4 mt-4" @submit="onSubmit">
  <?= \App\Core\Csrf::field() ?>
  <input type="hidden" name="idempotency_key" value="<?= View::e($idem ?? '') ?>">
  <input type="hidden" name="package_id" id="package_id" value="<?= View::e($old['package_id'] ?? ($packages[0]['id'] ?? '')) ?>">
  <input type="hidden" name="latitude" id="latitude" value="<?= View::e($old['latitude'] ?? '-6.2') ?>">
  <input type="hidden" name="longitude" id="longitude" value="<?= View::e($old['longitude'] ?? '106.816666') ?>">

  <div class="card p-4">
    <h2 class="font-bold">1. Lokasi kamu</h2>
    <div class="flex gap-2 mt-2">
      <input id="map-search" class="input" placeholder="Cari alamat... (mis. Monas)">
      <button type="button" id="map-search-btn" class="btn btn-ghost"><i class="fa-solid fa-magnifying-glass"></i> Cari</button>
    </div>
    <div id="search-results" class="mt-1"></div>
    <div id="order-map" class="mt-2" style="height:320px"></div>
    <div class="flex items-center justify-between mt-2 text-sm">
      <span class="muted">Pin: <span id="latlng-label" class="text-[#F5F5F5]">—</span></span>
      <button type="button" id="btn-locate" class="btn btn-ghost text-sm !py-2"><i class="fa-solid fa-location-dot"></i> Lokasiku</button>
    </div>
    <div class="mt-3"><label class="label" for="address">Alamat / patokan</label><textarea id="address" name="address" class="textarea" rows="2" required placeholder="Contoh: Depan Indomaret, gang buntu, pagar hijau..."><?= View::e($old['address'] ?? '') ?></textarea></div>
    <div class="mt-2"><label class="label" for="note">Catatan (opsional)</label><input id="note" name="note" class="input" placeholder="Mis. buru-buru level 10" value="<?= View::e($old['note'] ?? '') ?>"></div>
  </div>

  <div class="space-y-4">
    <div class="card p-4">
      <h2 class="font-bold">2. Pilih paket</h2>
      <div class="grid gap-2 mt-2">
        <?php foreach ($packages as $pkg): $f = json_decode($pkg['features_json'] ?? '[]', true) ?: []; ?>
        <button type="button" data-package="<?= $pkg['id'] ?>" data-name="<?= View::e($pkg['name']) ?>" data-price="<?= $pkg['price'] ?>" class="card-2 p-3 text-left hover:border-[#D4D4D4]" aria-pressed="false">
          <div class="flex justify-between items-center"><strong><?= View::e($pkg['name']) ?></strong><span><?= $fmt($pkg['price']) ?></span></div>
          <div class="muted text-xs"><?= View::e($pkg['description']) ?> • <?= View::e(implode(', ', array_slice($f, 0, 2))) ?></div>
        </button>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="card p-4">
      <h2 class="font-bold">3. Pembayaran (simulasi)</h2>
      <div class="space-y-2 mt-2 text-sm">
        <?php foreach (\App\Models\Payment::METHODS as $k => $label): ?>
        <label class="flex gap-2 items-center card-2 p-2 cursor-pointer"><input type="radio" name="payment_method" value="<?= $k ?>" <?= (($old['payment_method'] ?? 'cash_on_arrival') === $k) ? 'checked' : '' ?>> <?= View::e($label) ?></label>
        <?php endforeach; ?>
      </div>
      <div id="order-summary" class="card-2 p-3 mt-3">
        <div v-if="selected" class="flex justify-between text-sm"><span>{{ selected.name }}</span><strong>{{ rupiah(selected.price) }}</strong></div>
        <div v-else class="muted text-xs">Pilih paket…</div>
        <div class="muted text-xs mt-1">SIMULASI — harga fiktif, dihitung ulang di server. <button type="button" @click="refreshPackages" class="underline" :disabled="loadingPkg">{{ loadingPkg ? 'Memuat…' : 'Refresh harga' }}</button><span v-if="lat"> • Pin: {{ lat }}, {{ lng }}</span></div>
      </div>
      <button type="submit" class="btn btn-primary w-full mt-3" :disabled="submitting"><i class="fa-solid fa-toilet"></i> {{ submitting ? 'Mengirim…' : 'Pesan Sekarang' }}</button>
      <p class="muted text-xs mt-2">Dengan menekan tombol, kamu menyetujui bahwa ini SIMULASI hiburan. Tidak ada toilet beneran yang datang.</p>
    </div>
  </div>
</form>
<script>window.__BOOT_ORDER__ = <?= json_encode(['packages' => $bootPackages, 'packageId' => (string)($old['package_id'] ?? ($packages[0]['id'] ?? '')), 'paymentMethod' => $old['payment_method'] ?? 'cash_on_arrival'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;</script>
</div>
