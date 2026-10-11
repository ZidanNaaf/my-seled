<?php use App\Core\View; ?>
<div data-vue-table-filter>
<h1 class="text-2xl font-bold">Kelola Pengguna</h1>
<form method="GET" class="flex gap-2 mt-3"><input name="q" data-filter-input class="input !w-auto" placeholder="Cari nama/email... (live + server)" value="<?= View::e($q) ?>"><button class="btn btn-ghost text-sm">Cari</button></form>
<div class="card mt-3 overflow-x-auto"><table class="table"><thead><tr><th>Nama</th><th>Login</th><th>Role</th><th>Aktif</th><th>Aksi</th></tr></thead><tbody>
<?php foreach ($users as $u): ?><tr data-filter-text="<?= View::e($u['name'] . ' ' . $u['username'] . ' ' . $u['email']) ?>">
<td><?= View::e($u['name']) ?></td><td class="text-xs"><?= View::e($u['username']) ?><br><span class="muted"><?= View::e($u['email']) ?></span></td>
<td><form method="POST" action="/admin/users/role" class="flex gap-1"><?= \App\Core\Csrf::field() ?><input type="hidden" name="id" value="<?= (int)$u['id'] ?>"><select name="role" class="select !w-auto !py-1 text-xs"><?php foreach (['user', 'driver', 'admin'] as $r): ?><option <?= $u['role'] === $r ? 'selected' : '' ?>><?= $r ?></option><?php endforeach; ?></select><button class="btn btn-ghost text-xs !py-1">Simpan</button></form></td>
<td><?= (int)$u['is_active'] === 1 ? '<i class="fa-solid fa-circle-check" style="color:#4ADE80"></i>' : '<i class="fa-solid fa-circle-xmark" style="color:#F87171"></i>' ?></td>
<td><form method="POST" action="/admin/users/toggle"><?= \App\Core\Csrf::field() ?><input type="hidden" name="id" value="<?= (int)$u['id'] ?>"><button class="btn btn-ghost text-xs !py-1"><?= (int)$u['is_active'] === 1 ? 'Nonaktifkan' : 'Aktifkan' ?></button></form></td>
</tr><?php endforeach; ?>
</tbody></table></div>
</div>
