<?php use App\Core\View; ?>
<div class="max-w-md mx-auto card p-6 mt-8">
  <h1 class="text-2xl font-bold">Daftar My Seled</h1>
  <p class="muted text-sm">Gratis. Fiktif. Cepat.</p>
  <form method="POST" action="/register" class="mt-4 space-y-3">
    <?= \App\Core\Csrf::field() ?>
    <div><label class="label" for="name">Nama lengkap</label><input id="name" name="name" class="input" required value="<?= View::e($old['name'] ?? '') ?>"><?php if (!empty($errors['name'])): ?><div class="text-xs mt-1" style="color:#F87171"><?= View::e($errors['name']) ?></div><?php endif; ?></div>
    <div><label class="label" for="username">Username</label><input id="username" name="username" class="input" required value="<?= View::e($old['username'] ?? '') ?>"><?php if (!empty($errors['username'])): ?><div class="text-xs mt-1" style="color:#F87171"><?= View::e($errors['username']) ?></div><?php endif; ?></div>
    <div><label class="label" for="email">Email</label><input id="email" name="email" type="email" class="input" required value="<?= View::e($old['email'] ?? '') ?>"><?php if (!empty($errors['email'])): ?><div class="text-xs mt-1" style="color:#F87171"><?= View::e($errors['email']) ?></div><?php endif; ?></div>
    <div><label class="label" for="phone">No. HP (opsional)</label><input id="phone" name="phone" class="input" value="<?= View::e($old['phone'] ?? '') ?>"></div>
    <div class="grid grid-cols-2 gap-2">
      <div><label class="label" for="password">Password</label><input id="password" name="password" type="password" class="input" required></div>
      <div><label class="label" for="password_confirm">Konfirmasi</label><input id="password_confirm" name="password_confirm" type="password" class="input" required></div>
    </div>
    <?php if (!empty($errors['password'])): ?><div class="text-xs" style="color:#F87171"><?= View::e($errors['password']) ?></div><?php endif; ?>
    <?php if (!empty($errors['password_confirm'])): ?><div class="text-xs" style="color:#F87171"><?= View::e($errors['password_confirm']) ?></div><?php endif; ?>
    <button class="btn btn-primary w-full" type="submit">Daftar & Masuk</button>
  </form>
  <p class="text-sm muted mt-4">Sudah punya akun? <a href="/login" class="underline text-[#F5F5F5]">Masuk</a></p>
</div>
