<?php use App\Core\View; ?>
<div class="max-w-md mx-auto card p-6 mt-8">
  <h1 class="text-2xl font-bold">Masuk ke My Seled</h1>
  <p class="muted text-sm">Singgasana menantimu.</p>
  <?php if (!empty($message)): ?><div class="card-2 p-3 mt-3 text-sm" style="border-color:#5c2424"><?= View::e($message) ?></div><?php endif; ?>
  <form method="POST" action="/login" class="mt-4 space-y-3">
    <?= \App\Core\Csrf::field() ?>
    <div><label class="label" for="login">Email atau username</label><input id="login" name="login" class="input" required value="<?= View::e($old['login'] ?? '') ?>"></div>
    <div><label class="label" for="password">Password</label><input id="password" name="password" type="password" class="input" required></div>
    <?php if (!empty($errors)): ?><div class="text-sm" style="color:#F87171"><?php foreach ($errors as $e): ?><div><?= View::e($e) ?></div><?php endforeach; ?></div><?php endif; ?>
    <button class="btn btn-primary w-full" type="submit">Masuk</button>
  </form>
  <p class="text-sm muted mt-4">Belum punya akun? <a href="/register" class="underline text-[#F5F5F5]">Daftar</a></p>
</div>
