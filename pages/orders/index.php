<?php
use App\Core\View;
$fmt = fn($n) => 'Rp' . number_format((int)$n, 0, ',', '.');
$bootOrders = array_map(fn($o) => ['order_code' => $o['order_code'], 'created_at' => $o['created_at'], 'package_name' => $o['package_name'] ?? $o['package_name_snapshot'] ?? '-', 'total' => (int)$o['total'], 'status' => $o['status'], 'vehicle_code' => $o['vehicle_code'] ?? null], $orders ?? []);
?>
<div id="vue-orders">
<div class="flex items-center justify-between flex-wrap gap-2">
  <h1 class="text-2xl font-bold">Riwayat Pesanan</h1>
  <div class="flex gap-2"><button @click="refresh" class="btn btn-ghost text-sm" v-cloak><i class="fa-solid fa-rotate"></i> Refresh</button><a href="/order" class="btn btn-primary text-sm"><i class="fa-solid fa-circle-plus"></i> Pesan lagi</a></div>
</div>
<div class="flex flex-wrap gap-2 mt-3" v-cloak>
  <select v-model="statusF" class="select !w-auto"><option value="all">Semua</option><option value="active">Aktif</option><option value="completed">Selesai</option><option value="cancelled">Dibatalkan</option></select>
  <input v-model="q" class="input !w-auto" placeholder="Cari kode MS-... (live)">
  <span class="muted text-xs self-center">{{ filtered.length }} hasil</span>
</div>
<div v-cloak>
  <div v-if="!filtered.length" class="card p-8 text-center mt-4"><div class="text-4xl"><i class="fa-solid fa-inbox"></i></div><p class="font-bold mt-2">Belum ada pesanan</p><p class="muted text-sm">Perutmu terlalu sopan. <a href="/order" class="underline">Pesan sekarang <i class="fa-solid fa-arrow-right"></i></a></p></div>
  <div v-else class="card mt-4 overflow-x-auto"><table class="table"><thead><tr><th>Kode</th><th>Tanggal</th><th>Paket</th><th>Total</th><th>Status</th><th>Kendaraan</th><th></th></tr></thead><tbody>
  <tr v-for="o in filtered" :key="o.order_code"><td class="font-mono">{{ o.order_code }}</td><td class="text-xs muted">{{ o.created_at }}</td><td>{{ o.package_name }}</td><td>{{ rupiah(o.total) }}</td><td><span class="badge">{{ o.status }}</span></td><td class="text-xs">{{ o.vehicle_code || '—' }}</td><td><a class="underline text-sm" :href="'/orders/' + o.order_code">Detail</a> <span v-if="['pending','searching_driver','assigned','driver_arriving','arrived','in_service'].includes(o.status)"> • <a class="underline text-sm" :href="'/tracking/' + o.order_code">Lacak</a> <button v-if="['pending','searching_driver','assigned'].includes(o.status)" @click="cancel(o.order_code)" :disabled="busy===o.order_code" class="underline text-sm" style="color:#F87171">{{ busy===o.order_code ? '…' : 'Batal' }}</button></span></td></tr>
  </tbody></table></div>
</div>
<noscript>
<form method="GET" action="/orders" class="flex flex-wrap gap-2 mt-3"><select name="status" class="select !w-auto" onchange="this.form.submit()"><?php foreach (['all' => 'Semua', 'active' => 'Aktif', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan'] as $k => $l): ?><option value="<?= $k ?>" <?= ($status === $k) ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select><input name="q" class="input !w-auto" placeholder="Cari kode MS-..." value="<?= View::e($q) ?>"><button class="btn btn-ghost text-sm">Cari</button></form>
<?php if (!$orders): ?><div class="card p-8 text-center mt-4">Belum ada pesanan.</div><?php else: ?><div class="card mt-4 overflow-x-auto"><table class="table"><thead><tr><th>Kode</th><th>Tanggal</th><th>Paket</th><th>Total</th><th>Status</th><th>Kendaraan</th><th></th></tr></thead><tbody><?php foreach ($orders as $o): ?><tr><td class="font-mono"><?= View::e($o['order_code']) ?></td><td class="text-xs muted"><?= View::e($o['created_at']) ?></td><td><?= View::e($o['package_name'] ?? $o['package_name_snapshot']) ?></td><td><?= $fmt($o['total']) ?></td><td><span class="badge"><?= View::e($o['status']) ?></span></td><td class="text-xs"><?= View::e($o['vehicle_code'] ?? '—') ?></td><td><a class="underline text-sm" href="/orders/<?= View::e($o['order_code']) ?>">Detail</a></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
</noscript>
<script>window.__BOOT_ORDERS__ = <?= json_encode(['orders' => $bootOrders, 'status' => $status ?? 'all', 'q' => $q ?? ''], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;</script>
</div>
