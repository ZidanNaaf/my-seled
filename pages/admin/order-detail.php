<?php use App\Core\View; ?>
<a href="/admin/orders" class="text-sm muted underline"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
<h1 class="text-2xl font-bold font-mono"><?= View::e($order['order_code']) ?> <span class="badge"><?= View::e($order['status']) ?></span></h1>
<div class="card p-4 mt-3 text-sm space-y-1">
  <div>User: <strong><?= View::e($order['user_name'] ?? '-') ?></strong> (<?= View::e($order['user_email'] ?? '-') ?>)</div>
  <div>Total: <strong>Rp<?= number_format($order['total']) ?></strong> (snapshot <?= View::e($order['package_name_snapshot']) ?>)</div>
  <div>Alamat: <?= View::e($order['destination_address']) ?> <span class="font-mono text-xs muted"><?= View::e($order['destination_latitude']) ?>, <?= View::e($order['destination_longitude']) ?></span></div>
  <div>Kendaraan: <?= View::e($order['vehicle_code'] ?? '—') ?></div>
</div>
<div class="card p-4 mt-3"><h2 class="font-bold text-sm">RIWAYAT STATUS (AUDIT)</h2><ul class="mt-2 space-y-1"><?php foreach ($logs as $l): ?><li class="text-xs"><span class="badge"><?= View::e($l['created_at']) ?></span> <?= View::e($l['old_status'] ?? '—') ?> → <strong><?= View::e($l['new_status']) ?></strong> <?= $l['note'] ? ' • ' . View::e($l['note']) : '' ?> (actor <?= View::e($l['actor_user_id'] ?? 'system') ?>)</li><?php endforeach; ?></ul></div>
