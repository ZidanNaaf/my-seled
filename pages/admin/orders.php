<?php use App\Core\View;
$bootAdminOrders = array_map(fn($o) => ['order_code' => $o['order_code'], 'user_name' => $o['user_name'] ?? '-', 'total' => (int)$o['total'], 'status' => $o['status'], 'vehicle_code' => $o['vehicle_code'] ?? null], $orders ?? []);
?>
<div id="vue-admin-orders">
<h1 class="text-2xl font-bold">Kelola Pesanan</h1>
<div class="flex flex-wrap gap-2 mt-3 text-sm"><a href="/admin" class="btn btn-ghost !py-2"><i class="fa-solid fa-arrow-left"></i> Dashboard</a><button @click="refresh" class="btn btn-ghost !py-2" :disabled="loading" v-cloak><i class="fa-solid fa-rotate"></i> {{ loading ? 'Memuat…' : 'Refresh' }}</button></div>
<div class="flex flex-wrap gap-2 mt-3" v-cloak>
  <select v-model="statusF" class="select !w-auto"><option value="all">Semua</option><option>pending</option><option>searching_driver</option><option>assigned</option><option>driver_arriving</option><option>arrived</option><option>in_service</option><option>completed</option><option>cancelled</option></select>
  <input v-model="q" class="input !w-auto" placeholder="Cari MS-... (live)">
  <span class="muted text-xs self-center">{{ filtered.length }} hasil</span>
</div>
<div class="card mt-3 overflow-x-auto" v-cloak><table class="table"><thead><tr><th>Kode</th><th>User</th><th>Total</th><th>Status</th><th>Kendaraan</th><th></th></tr></thead><tbody>
<tr v-if="!filtered.length"><td colspan="6" class="muted text-center">Kosong.</td></tr>
<tr v-for="o in filtered" :key="o.order_code"><td class="font-mono">{{ o.order_code }}</td><td>{{ o.user_name }}</td><td>Rp{{ Number(o.total).toLocaleString('id-ID') }}</td><td><span class="badge">{{ o.status }}</span></td><td>{{ o.vehicle_code || '—' }}</td><td><a class="underline text-sm" :href="'/admin/orders/' + o.order_code">Detail</a></td></tr>
</tbody></table></div>
<noscript>
<form method="GET" class="flex flex-wrap gap-2 mt-3"><select name="status" class="select !w-auto" onchange="this.form.submit()"><?php foreach (['all' => 'Semua', 'pending' => 'pending', 'searching_driver' => 'searching_driver', 'assigned' => 'assigned', 'driver_arriving' => 'driver_arriving', 'arrived' => 'arrived', 'in_service' => 'in_service', 'completed' => 'completed', 'cancelled' => 'cancelled'] as $k => $l): ?><option value="<?= $k ?>" <?= ($status === $k) ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select><input name="q" class="input !w-auto" placeholder="Cari MS-..." value="<?= View::e($q) ?>"><button class="btn btn-ghost text-sm">Cari</button></form>
<div class="card mt-3 overflow-x-auto"><table class="table"><thead><tr><th>Kode</th><th>User</th><th>Total</th><th>Status</th><th>Kendaraan</th><th></th></tr></thead><tbody><?php foreach ($orders as $o): ?><tr><td class="font-mono"><?= View::e($o['order_code']) ?></td><td><?= View::e($o['user_name'] ?? '-') ?></td><td>Rp<?= number_format($o['total']) ?></td><td><span class="badge"><?= View::e($o['status']) ?></span></td><td><?= View::e($o['vehicle_code'] ?? '—') ?></td><td><a class="underline text-sm" href="/admin/orders/<?= View::e($o['order_code']) ?>">Detail</a></td></tr><?php endforeach; ?><?php if (!$orders): ?><tr><td colspan="6" class="muted text-center">Kosong.</td></tr><?php endif; ?></tbody></table></div>
</noscript>
<script>window.__BOOT_ADMIN_ORDERS__ = <?= json_encode(['orders' => $bootAdminOrders, 'status' => $status ?? 'all', 'q' => $q ?? ''], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;</script>
</div>
