<?php use App\Core\View; ?>
<h1 class="text-2xl font-bold">Kelola Kendaraan</h1>
<p class="muted text-sm">Kendaraan bertugas tidak bisa di-maintenance/offline.</p>
<div class="card p-4 mt-3">
  <h2 class="font-bold text-sm">TAMBAH / EDIT</h2>
  <form method="POST" action="/admin/vehicles/save" class="grid md:grid-cols-6 gap-2 mt-2"><?= \App\Core\Csrf::field() ?>
    <input type="hidden" name="id" id="v-id"><input name="code" id="v-code" class="input" placeholder="SL-099" required><input name="name" id="v-name" class="input" placeholder="Nama" required>
    <select name="status" id="v-status" class="select"><?php foreach (['available', 'assigned', 'on_trip', 'maintenance', 'offline'] as $s): ?><option><?= $s ?></option><?php endforeach; ?></select>
    <input name="latitude" id="v-lat" class="input" placeholder="-6.2"><input name="longitude" id="v-lng" class="input" placeholder="106.81">
    <button class="btn btn-primary text-sm">Simpan</button>
  </form>
</div>
<div class="card mt-3 overflow-x-auto"><table class="table"><thead><tr><th>Kode</th><th>Nama</th><th>Status</th><th>Lat,Lng</th><th>Order</th><th></th></tr></thead><tbody>
<?php foreach ($vehicles as $v): ?><tr><td class="font-mono"><?= View::e($v['code']) ?></td><td><?= View::e($v['name']) ?></td><td><span class="badge"><?= View::e($v['status']) ?></span></td><td class="text-xs font-mono"><?= $v['latitude'] ?>, <?= $v['longitude'] ?></td><td><?= View::e($v['current_order_id'] ?? '—') ?></td>
<td><button class="btn btn-ghost text-xs !py-1" onclick="document.getElementById('v-id').value='<?= (int)$v['id'] ?>';document.getElementById('v-code').value='<?= View::e($v['code']) ?>';document.getElementById('v-name').value='<?= View::e($v['name']) ?>';document.getElementById('v-status').value='<?= View::e($v['status']) ?>';document.getElementById('v-lat').value='<?= $v['latitude'] ?>';document.getElementById('v-lng').value='<?= $v['longitude'] ?>';window.scrollTo({top:0,behavior:'smooth'})">Edit</button></td></tr><?php endforeach; ?>
</tbody></table></div>
