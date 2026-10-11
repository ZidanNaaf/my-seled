<?php use App\Core\View; ?>
<h1 class="text-2xl font-bold">Pengaturan</h1>
<p class="muted text-sm">Hanya key non-sensitif. Rahasia tetap di server (.env).</p>
<form method="POST" action="/admin/settings" class="card p-4 mt-3 space-y-3 max-w-lg"><?= \App\Core\Csrf::field() ?>
  <div><label class="label">Nama aplikasi</label><input name="app_name" class="input" value="<?= View::e($s['app_name'] ?? 'My Seled') ?>"></div>
  <div><label class="label">Kontak demo</label><input name="contact_demo" class="input" value="<?= View::e($s['contact_demo'] ?? '') ?>"></div>
  <div><label class="label">Mode demo (1/0)</label><input name="demo_mode" class="input" value="<?= View::e($s['demo_mode'] ?? '1') ?>"></div>
  <button class="btn btn-primary">Simpan</button>
</form>
