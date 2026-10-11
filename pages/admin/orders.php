<?php use App\Core\View; ?>
<h1 class="text-2xl font-bold">Kelola Pesanan</h1>
<div class="flex flex-wrap gap-2 mt-3 text-sm"><a href="/admin" class="btn btn-ghost !py-2"><i class="fa-solid fa-arrow-left"></i> Dashboard</a></div>
<form method="GET" class="flex flex-wrap gap-2 mt-3">
  <select name="status" class="select !w-auto" onchange="this.form.submit()"><?php foreach (['all' => 'Semua', 'pending' => 'pending', 'searching_driver' => 'searching_driver', 'assigned' => 'assigned', 'driver_arriving' => 'driver_arriving', 'arrived' => 'arrived', 'in_service' => 'in_service', 'completed' => 'completed', 'cancelled' => 'cancelled'] as $k => $l): ?><option value="<?= $k ?>" <?= ($status === $k) ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select>
  <input name="q" class="input !w-auto" placeholder="Cari MS-..." value="<?= View::e($q) ?>"><button class="btn btn-ghost text-sm">Cari</button>
</form>
<div class="card mt-3 overflow-x-auto"><table class="table"><thead><tr><th>Kode</th><th>User</th><th>Total</th><th>Status</th><th>Kendaraan</th><th></th></tr></thead><tbody>
<?php foreach ($orders as $o): ?><tr><td class="font-mono"><?= View::e($o['order_code']) ?></td><td><?= View::e($o['user_name'] ?? '-') ?></td><td>Rp<?= number_format($o['total']) ?></td><td><span class="badge"><?= View::e($o['status']) ?></span></td><td><?= View::e($o['vehicle_code'] ?? '—') ?></td><td><a class="underline text-sm" href="/admin/orders/<?= View::e($o['order_code']) ?>">Detail</a></td></tr><?php endforeach; ?>
<?php if (!$orders): ?><tr><td colspan="6" class="muted text-center">Kosong.</td></tr><?php endif; ?>
</tbody></table></div>
