<?php use App\Core\View; ?>
<h1 class="text-2xl font-bold">Profil</h1>
<div class="grid md:grid-cols-2 gap-4 mt-4">
  <form method="POST" action="/profile" class="card p-4 space-y-3">
    <?= \App\Core\Csrf::field() ?>
    <h2 class="font-bold">Data diri</h2>
    <div><label class="label">Nama</label><input name="name" class="input" value="<?= View::e($user['name']) ?>"></div>
    <div><label class="label">Username (tidak bisa diubah)</label><input class="input" value="<?= View::e($user['username']) ?>" disabled></div>
    <div><label class="label">Email</label><input name="email" type="email" class="input" value="<?= View::e($user['email']) ?>"></div>
    <div><label class="label">Telepon</label><input name="phone" class="input" value="<?= View::e($user['phone'] ?? '') ?>"></div>
    <div class="muted text-xs">Role: <?= View::e($user['role']) ?> • Total pesanan: <?= (int)($total ?? 0) ?></div>
    <?php if (!empty($errors)): foreach ($errors as $e): ?><div class="text-xs" style="color:#F87171"><?= View::e($e) ?></div><?php endforeach; endif; ?>
    <button class="btn btn-primary w-full">Simpan</button>
  </form>
  <form method="POST" action="/profile/password" class="card p-4 space-y-3">
    <?= \App\Core\Csrf::field() ?>
    <h2 class="font-bold">Ganti password</h2>
    <div><label class="label">Password lama</label><input name="old_password" type="password" class="input" required></div>
    <div><label class="label">Password baru (min 8)</label><input name="new_password" type="password" class="input" required></div>
    <div><label class="label">Konfirmasi baru</label><input name="new_password_confirm" type="password" class="input" required></div>
    <button class="btn btn-ghost w-full">Ganti Password</button>
  </form>
</div>
