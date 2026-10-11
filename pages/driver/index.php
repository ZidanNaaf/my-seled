<?php use App\Core\View;
$bootJobs = array_map(fn($j) => ['id' => (int)$j['id'], 'order_code' => $j['order_code'], 'package_name' => $j['package_name'] ?? '-', 'destination_address' => $j['destination_address'] ?? ''], $jobs ?? []);
$bootMine = array_map(fn($m) => ['id' => (int)$m['id'], 'order_code' => $m['order_code'], 'status' => $m['status'], 'vehicle_code' => $m['vehicle_code'] ?? '-', 'destination_address' => $m['destination_address'] ?? ''], $mine ?? []);
?>
<div id="vue-driver">
<div class="flex items-center justify-between flex-wrap gap-2">
  <div><h1 class="text-2xl font-bold"><i class="fa-solid fa-truck-fast"></i> Driver Dashboard</h1><p class="muted text-sm">Halo <?= View::e($user['name']) ?> • Status: <span v-cloak><span v-if="online" class="badge badge-green"><i class="fa-solid fa-circle"></i> ONLINE</span><span v-else class="badge"><i class="fa-regular fa-circle"></i> OFFLINE</span></span><span v-cloak style="display:none"><?= ((int)($user['is_online'] ?? 0) === 1) ? 'ONLINE' : 'OFFLINE' ?></span></p></div>
  <div class="flex gap-2"><button v-cloak @click="toggleOnline" :disabled="busy==='online'" class="btn text-sm" :class="online ? 'btn-ghost' : 'btn-primary'"><i class="fa-solid fa-power-off"></i> {{ busy==='online' ? '…' : (online ? 'Go Offline' : 'Go Online') }}</button>
  <form method="POST" action="/driver/availability"><noscript><?= \App\Core\Csrf::field() ?><input type="hidden" name="online" value="<?= ((int)($user['is_online'] ?? 0) === 1) ? '0' : '1' ?>"><button class="btn text-sm"><i class="fa-solid fa-power-off"></i> Toggle</button></noscript></form></div>
</div>
<div class="grid md:grid-cols-2 gap-4 mt-4" v-cloak>
  <div class="card p-4">
    <h2 class="font-bold">Pekerjaan tersedia ({{ jobs.length }})</h2>
    <p v-if="!jobs.length" class="muted text-sm mt-2">Tidak ada antrean. Santai dulu, mas.</p>
    <div class="space-y-2 mt-2"><div v-for="j in jobs" :key="j.id" class="card-2 p-3 text-sm flex justify-between items-center gap-2">
      <div><div class="font-mono font-bold">{{ j.order_code }}</div><div class="muted text-xs">{{ j.package_name }} • {{ (j.destination_address || '').slice(0, 60) }}</div></div>
      <button @click="accept(j.id)" :disabled="busy==='accept-'+j.id" class="btn btn-primary text-sm !py-2"><i class="fa-solid fa-hand"></i> {{ busy==='accept-'+j.id ? '…' : 'Ambil' }}</button>
    </div></div>
  </div>
  <div class="card p-4">
    <h2 class="font-bold">Tugas aktif & simulasi status</h2>
    <div class="space-y-2 mt-2"><div v-for="m in mine" :key="m.id" class="card-2 p-3 text-sm">
      <div class="flex justify-between"><span class="font-mono font-bold">{{ m.order_code }}</span><span class="badge">{{ m.status }}</span></div>
      <div class="muted text-xs">{{ m.vehicle_code }} • {{ (m.destination_address || '').slice(0, 80) }}</div>
      <div class="flex flex-wrap gap-1 mt-2">
        <button v-for="st in ['driver_arriving','arrived','in_service','completed']" :key="st" @click="setStatus(m.id, st)" :disabled="busy==='st-'+m.id+st" class="btn btn-ghost text-xs !py-1 !px-2">{{ st }}</button>
        <a :href="'/tracking/' + m.order_code" class="btn btn-ghost text-xs !py-1 !px-2"><i class="fa-solid fa-map"></i> Peta</a>
      </div>
    </div><p v-if="!mine.length" class="muted text-sm">Belum ada tugas aktif.</p></div>
    <p class="muted text-xs mt-3">Total selesai (sistem): <?= (int)$done ?> • Semua perubahan status tervalidasi di backend + tercatat di log.</p>
  </div>
</div>
<noscript>
<div class="grid md:grid-cols-2 gap-4 mt-4"><div class="card p-4"><h2 class="font-bold">Pekerjaan tersedia (<?= count($jobs) ?>)</h2><?php if (!$jobs): ?><p class="muted text-sm mt-2">Tidak ada antrean.</p><?php endif; ?><div class="space-y-2 mt-2"><?php foreach ($jobs as $j): ?><div class="card-2 p-3 text-sm flex justify-between items-center gap-2"><div><div class="font-mono font-bold"><?= View::e($j['order_code']) ?></div><div class="muted text-xs"><?= View::e($j['package_name'] ?? '-') ?></div></div><form method="POST" action="/driver/orders/<?= (int)$j['id'] ?>/accept"><?= \App\Core\Csrf::field() ?><button class="btn btn-primary text-sm !py-2">Ambil</button></form></div><?php endforeach; ?></div></div>
<div class="card p-4"><h2 class="font-bold">Tugas aktif</h2><div class="space-y-2 mt-2"><?php foreach ($mine as $m): ?><div class="card-2 p-3 text-sm"><div class="flex justify-between"><span class="font-mono font-bold"><?= View::e($m['order_code']) ?></span><span class="badge"><?= View::e($m['status']) ?></span></div></div><?php endforeach; ?></div></div></div>
</noscript>
<script>window.__BOOT_DRIVER__ = <?= json_encode(['jobs' => $bootJobs, 'mine' => $bootMine, 'online' => ((int)($user['is_online'] ?? 0) === 1)], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;</script>
</div>
