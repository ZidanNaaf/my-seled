<?php
use App\Core\View;
$fmt = fn($n) => 'Rp' . number_format((int)$n, 0, ',', '.');
?>
<h1 class="text-2xl font-bold"><i class="fa-solid fa-screwdriver-wrench"></i> Admin</h1>
<p class="muted text-sm">Semua angka live dari SQLite.</p>
<div class="flex flex-wrap gap-2 mt-3 text-sm">
  <a href="/admin" class="btn btn-primary !py-2"><i class="fa-solid fa-gauge"></i> Ringkasan</a><a href="/admin/orders" class="btn btn-ghost !py-2"><i class="fa-solid fa-box-archive"></i> Pesanan</a><a href="/admin/users" class="btn btn-ghost !py-2"><i class="fa-solid fa-users"></i> Pengguna</a><a href="/admin/vehicles" class="btn btn-ghost !py-2"><i class="fa-solid fa-truck-fast"></i> Kendaraan</a><a href="/admin/packages" class="btn btn-ghost !py-2"><i class="fa-solid fa-toilet-paper"></i> Paket</a><a href="/admin/settings" class="btn btn-ghost !py-2"><i class="fa-solid fa-gear"></i> Pengaturan</a>
</div>
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-4">
  <?php foreach ([['Pengguna', $stats['users']], ['Total order', $stats['orders']], ['Aktif', $stats['active']], ['Selesai', $stats['done']], ['Dibatalkan', $stats['cancelled']], ['Revenue selesai', $fmt($stats['revenue'])], ['Armada ready', $stats['veh_avail']], ['Armada jalan', $stats['veh_trip']]] as [$l, $v]): ?>
  <div class="card p-4"><div class="muted text-xs"><?= $l ?></div><div class="text-xl font-extrabold"><?= $v ?></div></div>
  <?php endforeach; ?>
</div>
<div class="grid md:grid-cols-2 gap-4 mt-4">
  <div class="card p-4"><h2 class="font-bold">Order 7 hari terakhir</h2><?php if (!$perDay): ?><p class="muted text-sm">Belum ada data.</p><?php else: ?><ul class="text-sm mt-2"><?php foreach ($perDay as $d): ?><li class="flex justify-between border-b border-[#383838] py-1"><span><?= View::e($d['d']) ?></span><strong><?= (int)$d['c'] ?></strong></li><?php endforeach; ?></ul><?php endif; ?></div>
  <div class="card p-4"><h2 class="font-bold">Terbaru</h2><div class="overflow-x-auto"><table class="table"><thead><tr><th>Kode</th><th>User</th><th>Status</th><th></th></tr></thead><tbody><?php foreach ($recent as $r): ?><tr><td class="font-mono"><?= View::e($r['order_code']) ?></td><td><?= View::e($r['user_name'] ?? '-') ?></td><td><span class="badge"><?= View::e($r['status']) ?></span></td><td><a class="underline" href="/admin/orders/<?= View::e($r['order_code']) ?>">Buka</a></td></tr><?php endforeach; ?></tbody></table></div></div>
</div>
