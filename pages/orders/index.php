<?php
use App\Core\View;
$fmt = fn($n) => 'Rp' . number_format((int)$n, 0, ',', '.');
?>
<div class="flex items-center justify-between flex-wrap gap-2">
  <h1 class="text-2xl font-bold">Riwayat Pesanan</h1>
  <a href="/order" class="btn btn-primary text-sm"><i class="fa-solid fa-circle-plus"></i> Pesan lagi</a>
</div>
<form method="GET" action="/orders" class="flex flex-wrap gap-2 mt-3">
  <select name="status" class="select !w-auto" onchange="this.form.submit()">
    <?php foreach (['all' => 'Semua', 'active' => 'Aktif', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan'] as $k => $l): ?>
    <option value="<?= $k ?>" <?= ($status === $k) ? 'selected' : '' ?>><?= $l ?></option>
    <?php endforeach; ?>
  </select>
  <input name="q" class="input !w-auto" placeholder="Cari kode MS-..." value="<?= View::e($q) ?>">
  <button class="btn btn-ghost text-sm"><i class="fa-solid fa-magnifying-glass"></i> Cari</button>
</form>
<?php if (!$orders): ?>
<div class="card p-8 text-center mt-4"><div class="text-4xl"><i class="fa-solid fa-inbox"></i></div><p class="font-bold mt-2">Belum ada pesanan</p><p class="muted text-sm">Perutmu terlalu sopan. <a href="/order" class="underline">Pesan sekarang <i class="fa-solid fa-arrow-right"></i></a></p></div>
<?php else: ?>
<div class="card mt-4 overflow-x-auto"><table class="table"><thead><tr><th>Kode</th><th>Tanggal</th><th>Paket</th><th>Total</th><th>Status</th><th>Kendaraan</th><th></th></tr></thead><tbody>
<?php foreach ($orders as $o): ?><tr>
<td class="font-mono"><?= View::e($o['order_code']) ?></td>
<td class="text-xs muted"><?= View::e($o['created_at']) ?></td>
<td><?= View::e($o['package_name'] ?? $o['package_name_snapshot']) ?></td>
<td><?= $fmt($o['total']) ?></td>
<td><span class="badge"><?= View::e($o['status']) ?></span></td>
<td class="text-xs"><?= View::e($o['vehicle_code'] ?? '—') ?></td>
<td><a class="underline text-sm" href="/orders/<?= View::e($o['order_code']) ?>">Detail</a><?php if (in_array($o['status'], ['pending','searching_driver','assigned','driver_arriving','arrived','in_service'])): ?> • <a class="underline text-sm" href="/tracking/<?= View::e($o['order_code']) ?>">Lacak</a><?php endif; ?></td>
</tr><?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>
