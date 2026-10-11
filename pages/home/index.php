<?php
use App\Core\View;
$fmt = fn($n) => 'Rp' . number_format((int)$n, 0, ',', '.');
$statusBadge = function ($s) {
  $map = ['pending'=>'', 'searching_driver'=>'badge-yellow', 'assigned'=>'', 'driver_arriving'=>'badge-yellow', 'arrived'=>'badge-green', 'in_service'=>'badge-green', 'completed'=>'badge-green', 'cancelled'=>'badge-red'];
  return $map[$s] ?? '';
};
?>
<div class="flex items-center justify-between flex-wrap gap-2">
  <div><h1 class="text-2xl font-bold">Halo, <?= View::e(explode(' ', (string)($user['name'] ?? 'Kawan'))[0]) ?> <i class="fa-solid fa-hand-sparkles text-[#A3A3A3]"></i></h1><p class="muted text-sm">Kebelet? Jangan ditahan. Kita meluncur.</p></div>
  <a href="/order" class="btn btn-primary"><i class="fa-solid fa-circle-plus"></i> Pesan Toilet Sekarang</a>
</div>

<?php if ($active): ?>
<div id="vue-home-active" data-code="<?= View::e($active['order_code']) ?>" data-status="<?= View::e($active['status']) ?>">
<div class="card p-5 mt-4" style="border-color:#5a4413">
  <div class="flex justify-between items-center flex-wrap gap-2">
    <div><div class="muted text-xs">PESANAN AKTIF • <span v-cloak class="text-[#F5F5F5]">live: {{ status }}</span></div><div class="font-bold text-lg"><?= View::e($active['order_code']) ?> <span class="badge <?= $statusBadge($active['status']) ?>"><?= View::e($active['status']) ?></span></div></div>
    <a href="/tracking/<?= View::e($active['order_code']) ?>" class="btn btn-primary text-sm"><i class="fa-solid fa-location-crosshairs"></i> Lacak Armada</a>
  </div>
</div>
</div>
<?php else: ?>
<div class="card p-6 mt-4 text-center">
  <div class="text-4xl"><i class="fa-solid fa-toilet"></i> <i class="fa-solid fa-wand-magic-sparkles text-[#A3A3A3]"></i></div>
  <h2 class="font-bold mt-2">Belum ada misi penyelamatan</h2>
  <p class="muted text-sm">Perut aman? Bagus. Kalau darurat, satu klik toilet datang.</p>
  <a href="/order" class="btn btn-primary mt-3">Pesan Toilet Pertama</a>
</div>
<?php endif; ?>

<div class="grid md:grid-cols-3 gap-3 mt-4">
  <div class="card p-4"><div class="muted text-xs">TOTAL PESANAN</div><div class="text-2xl font-extrabold"><?= (int)$total ?></div></div>
  <div class="card p-4"><div class="muted text-xs">STATUS TERAKHIR</div><div class="font-bold"><?= $recent ? View::e($recent[0]['status']) : '—' ?></div><div class="muted text-xs"><?= $recent ? View::e($recent[0]['order_code']) : 'Belum pernah memesan' ?></div></div>
  <div class="card p-4"><div class="muted text-xs">PAKET POPULER</div><?php foreach (array_slice($packages, 0, 2) as $pkg): ?><div class="text-sm"><i class="fa-solid fa-toilet-paper"></i> <?= View::e($pkg['name']) ?> • <?= $fmt($pkg['price']) ?></div><?php endforeach; ?></div>
</div>

<h2 class="font-bold mt-6 mb-2">Riwayat singkat</h2>
<?php if (!$recent): ?><div class="card p-4 muted text-sm">Belum ada riwayat. <a href="/order" class="underline">Buat pesanan <i class="fa-solid fa-arrow-right"></i></a></div>
<?php else: ?>
<div class="card overflow-x-auto"><table class="table"><thead><tr><th>Kode</th><th>Paket</th><th>Total</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
<?php foreach ($recent as $r): ?><tr><td class="font-mono"><?= View::e($r['order_code']) ?></td><td><?= View::e($r['package_name'] ?? '-') ?></td><td><?= $fmt($r['total']) ?></td><td><span class="badge <?= $statusBadge($r['status']) ?>"><?= View::e($r['status']) ?></span></td><td><a class="underline text-sm" href="/orders/<?= View::e($r['order_code']) ?>">Detail</a></td></tr><?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>
