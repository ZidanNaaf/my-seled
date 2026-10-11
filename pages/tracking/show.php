<?php
use App\Core\View;
$fmt = fn($n) => 'Rp' . number_format((int)$n, 0, ',', '.');
$cancellable = in_array($order['status'], ['pending', 'searching_driver', 'assigned'], true);
?>
<div class="flex items-center justify-between flex-wrap gap-2">
  <div><h1 class="text-2xl font-bold font-mono"><?= View::e($order['order_code']) ?></h1><p class="muted text-sm">Status: <strong id="t-status" class="text-[#F5F5F5]"><?= View::e($order['status']) ?></strong> • <?= View::e($order['package_name_snapshot']) ?> • <?= $fmt($order['total']) ?></p></div>
  <div class="flex gap-2"><a href="/orders" class="btn btn-ghost text-sm"><i class="fa-solid fa-arrow-left"></i> Semua pesanan</a>
  <?php if ($cancellable): ?>
  <form method="POST" action="/orders/<?= View::e($order['order_code']) ?>/cancel" onsubmit="return confirm('Batalkan pesanan ini?')">
    <?= \App\Core\Csrf::field() ?><button class="btn text-sm" style="border-color:#5c2424;color:#F87171"><i class="fa-solid fa-xmark"></i> Batalkan</button>
  </form>
  <?php endif; ?></div>
</div>

<div class="grid md:grid-cols-3 gap-4 mt-4">
  <div class="md:col-span-2">
    <div id="track-map" data-code="<?= View::e($order['order_code']) ?>" data-dlat="<?= View::e($order['destination_latitude']) ?>" data-dlng="<?= View::e($order['destination_longitude']) ?>" style="height:380px"></div>
    <div class="flex justify-between text-xs muted mt-1"><span id="route-note">Memuat rute...</span><span id="poll-note">Live update tiap 5 dtk (simulasi).</span></div>
    <div class="card p-3 mt-2"><div class="text-sm" id="t-progress-label">Memuat...</div><div class="h-2 bg-[#292929] rounded mt-2"><div id="t-progress" class="h-2 rounded bg-[#D4D4D4]" style="width:0%"></div></div></div>
  </div>
  <div class="space-y-3">
    <div class="card p-4 text-sm">
      <div class="muted text-xs">ARMADA (SIMULASI)</div>
      <div class="font-bold"><?= View::e($vehicle['name'] ?? 'Mencari armada...') ?></div>
      <div class="muted"><?= View::e($vehicle['code'] ?? '—') ?> • <?= View::e($vehicle['status'] ?? 'searching') ?></div>
      <div class="muted text-xs mt-2"><i class="fa-solid fa-location-dot"></i> <?= View::e($order['destination_address']) ?></div>
      <div class="muted text-xs font-mono"><?= View::e($order['destination_latitude']) ?>, <?= View::e($order['destination_longitude']) ?></div>
    </div>
    <div class="card p-4"><div class="muted text-xs mb-2">TIMELINE</div><ul id="timeline" class="space-y-1"><?php foreach ($logs as $l): ?><li class="flex gap-2 text-sm py-1"><span class="badge"><?= View::e($l['created_at']) ?></span><span><?= View::e($l['old_status'] ?? '—') ?> → <strong><?= View::e($l['new_status']) ?></strong></span></li><?php endforeach; ?></ul></div>
  </div>
</div>
