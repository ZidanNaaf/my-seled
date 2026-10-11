<?php
use App\Core\View;
$fmt = fn($n) => 'Rp' . number_format((int)$n, 0, ',', '.');
$cancellable = in_array($order['status'], ['pending', 'searching_driver', 'assigned'], true);
?>
<a href="/orders" class="text-sm muted underline"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
<h1 class="text-2xl font-bold font-mono mt-1"><?= View::e($order['order_code']) ?> <span class="badge"><?= View::e($order['status']) ?></span></h1>
<div class="grid md:grid-cols-2 gap-4 mt-4">
  <div class="card p-4 text-sm space-y-1">
    <div class="muted text-xs">DETAIL PESANAN</div>
    <div>Paket: <strong><?= View::e($order['package_name_snapshot']) ?></strong> (snapshot <?= $fmt($order['package_price_snapshot']) ?>)</div>
    <div>Total: <strong><?= $fmt($order['total']) ?></strong> • Bayar: <?= View::e($payment['status'] ?? $order['payment_status']) ?> (<?= View::e($payment['method'] ?? '-') ?>, simulasi)</div>
    <div>Alamat: <?= View::e($order['destination_address']) ?></div>
    <div class="font-mono text-xs muted"><?= View::e($order['destination_latitude']) ?>, <?= View::e($order['destination_longitude']) ?></div>
    <?php if ($order['note']): ?><div>Catatan: <?= View::e($order['note']) ?></div><?php endif; ?>
    <div class="muted text-xs">Dibuat: <?= View::e($order['created_at']) ?> UTC</div>
    <div class="flex gap-2 mt-2">
      <?php if (in_array($order['status'], ['pending','searching_driver','assigned','driver_arriving','arrived','in_service'])): ?><a href="/tracking/<?= View::e($order['order_code']) ?>" class="btn btn-primary text-sm"><i class="fa-solid fa-location-crosshairs"></i> Lacak</a><?php endif; ?>
      <?php if ($cancellable): ?><form method="POST" action="/orders/<?= View::e($order['order_code']) ?>/cancel" onsubmit="return confirm('Batalkan?')"><?= \App\Core\Csrf::field() ?><button class="btn text-sm" style="color:#F87171;border-color:#5c2424"><i class="fa-solid fa-xmark"></i> Batalkan</button></form><?php endif; ?>
    </div>
  </div>
  <div class="card p-4 text-sm">
    <div class="muted text-xs">KENDARAAN</div>
    <?php if ($vehicle): ?><div class="font-bold"><?= View::e($vehicle['name']) ?> (<?= View::e($vehicle['code']) ?>)</div><div class="muted"><?= View::e($vehicle['status']) ?></div>
    <?php else: ?><div class="muted">Menunggu dispatch...</div><?php endif; ?>
    <div class="muted text-xs mt-3">TIMELINE</div>
    <ul class="mt-1 space-y-1"><?php foreach ($logs as $l): ?><li class="text-xs"><span class="badge"><?= View::e($l['created_at']) ?></span> <?= View::e($l['old_status'] ?? '—') ?> → <strong><?= View::e($l['new_status']) ?></strong><?= $l['note'] ? ' • ' . View::e($l['note']) : '' ?></li><?php endforeach; ?></ul>
  </div>
</div>
