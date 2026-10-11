<?php use App\Core\View; ?>
<div data-vue-table-filter>
<h1 class="text-2xl font-bold">Kelola Paket</h1>
<div class="flex gap-2 mt-3"><input data-filter-input class="input !w-auto" placeholder="Filter nama paket... (live)"></div>
<p class="muted text-sm">Harga lama pada pesanan tidak ikut berubah (snapshot).</p>
<div class="card p-4 mt-3">
  <form method="POST" action="/admin/packages/save" class="grid md:grid-cols-5 gap-2"><?= \App\Core\Csrf::field() ?>
    <input type="hidden" name="id" id="p-id"><input name="name" id="p-name" class="input" placeholder="Nama paket" required><input name="price" id="p-price" type="number" min="0" class="input" placeholder="19900" required><input name="description" id="p-desc" class="input" placeholder="Deskripsi"><select name="is_active" class="select"><option value="1">Aktif</option><option value="0">Nonaktif</option></select>
    <button class="btn btn-primary text-sm md:col-span-5">Simpan Paket</button>
  </form>
</div>
<div class="grid md:grid-cols-3 gap-3 mt-3" data-filter-list><?php foreach ($packages as $pkg): ?>
  <div class="card p-4" data-filter-text="<?= View::e($pkg['name'] . ' ' . $pkg['description']) ?>"><div class="flex justify-between"><strong><?= View::e($pkg['name']) ?></strong><span class="badge <?= (int)$pkg['is_active'] === 1 ? 'badge-green' : 'badge-red' ?>"><?= (int)$pkg['is_active'] === 1 ? 'aktif' : 'mati' ?></span></div><div class="text-xl font-extrabold">Rp<?= number_format($pkg['price']) ?></div><div class="muted text-xs"><?= View::e($pkg['description']) ?> • <?= View::e($pkg['slug']) ?></div>
  <button class="btn btn-ghost text-xs mt-2" onclick="document.getElementById('p-id').value='<?= (int)$pkg['id'] ?>';document.getElementById('p-name').value='<?= View::e($pkg['name']) ?>';document.getElementById('p-price').value='<?= (int)$pkg['price'] ?>';document.getElementById('p-desc').value='<?= View::e($pkg['description']) ?>';window.scrollTo({top:0,behavior:'smooth'})">Edit</button></div>
<?php endforeach; ?></div>
</div>
