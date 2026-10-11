<?php use App\Core\View; ?>
<div class="flex items-center justify-between flex-wrap gap-2">
  <div><h1 class="text-2xl font-bold"><i class="fa-solid fa-truck-fast"></i> Driver Dashboard</h1><p class="muted text-sm">Halo <?= View::e($user['name']) ?> • Status: <?= ((int)($user['is_online'] ?? 0) === 1) ? '<span class="badge badge-green"><i class="fa-solid fa-circle"></i> ONLINE</span>' : '<span class="badge"><i class="fa-regular fa-circle"></i> OFFLINE</span>' ?></p></div>
  <form method="POST" action="/driver/availability"><?= \App\Core\Csrf::field() ?><input type="hidden" name="online" value="<?= ((int)($user['is_online'] ?? 0) === 1) ? '0' : '1' ?>"><button class="btn <?= ((int)($user['is_online'] ?? 0) === 1) ? 'btn-ghost' : 'btn-primary' ?> text-sm"><i class="fa-solid fa-power-off"></i> <?= ((int)($user['is_online'] ?? 0) === 1) ? 'Go Offline' : 'Go Online' ?></button></form>
</div>
<div class="grid md:grid-cols-2 gap-4 mt-4">
  <div class="card p-4">
    <h2 class="font-bold">Pekerjaan tersedia (<?= count($jobs) ?>)</h2>
    <?php if (!$jobs): ?><p class="muted text-sm mt-2">Tidak ada antrean. Santai dulu, mas.</p><?php endif; ?>
    <div class="space-y-2 mt-2"><?php foreach ($jobs as $j): ?>
      <div class="card-2 p-3 text-sm flex justify-between items-center gap-2">
        <div><div class="font-mono font-bold"><?= View::e($j['order_code']) ?></div><div class="muted text-xs"><?= View::e($j['package_name'] ?? '-') ?> • <?= View::e(substr($j['destination_address'], 0, 60)) ?></div></div>
        <form method="POST" action="/driver/orders/<?= (int)$j['id'] ?>/accept"><?= \App\Core\Csrf::field() ?><button class="btn btn-primary text-sm !py-2"><i class="fa-solid fa-hand"></i> Ambil</button></form>
      </div>
    <?php endforeach; ?></div>
  </div>
  <div class="card p-4">
    <h2 class="font-bold">Tugas aktif & simulasi status</h2>
    <div class="space-y-2 mt-2"><?php foreach ($mine as $m): ?>
      <div class="card-2 p-3 text-sm">
        <div class="flex justify-between"><span class="font-mono font-bold"><?= View::e($m['order_code']) ?></span><span class="badge"><?= View::e($m['status']) ?></span></div>
        <div class="muted text-xs"><?= View::e($m['vehicle_code'] ?? '-') ?> • <?= View::e(substr($m['destination_address'], 0, 80)) ?></div>
        <div class="flex flex-wrap gap-1 mt-2">
          <?php foreach (['driver_arriving' => '<i class="fa-solid fa-truck-fast"></i> Meluncur', 'arrived' => '<i class="fa-solid fa-location-dot"></i> Tiba', 'in_service' => '<i class="fa-solid fa-play"></i> Mulai layanan', 'completed' => '<i class="fa-solid fa-flag-checkered"></i> Selesai'] as $st => $lbl): ?>
          <form method="POST" action="/driver/orders/<?= (int)$m['id'] ?>/status"><?= \App\Core\Csrf::field() ?><input type="hidden" name="status" value="<?= $st ?>"><button class="btn btn-ghost text-xs !py-1 !px-2"><?= $lbl ?></button></form>
          <?php endforeach; ?>
          <a href="/tracking/<?= View::e($m['order_code']) ?>" class="btn btn-ghost text-xs !py-1 !px-2"><i class="fa-solid fa-map"></i> Peta</a>
        </div>
      </div>
    <?php endforeach; ?>
    <?php if (!$mine): ?><p class="muted text-sm">Belum ada tugas aktif.</p><?php endif; ?></div>
    <p class="muted text-xs mt-3">Total selesai (sistem): <?= (int)$done ?> • Semua perubahan status tervalidasi di backend + tercatat di log.</p>
  </div>
</div>
