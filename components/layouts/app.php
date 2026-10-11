<?php
use App\Core\View;
use App\Core\Csrf;
$user = \App\Core\Auth::user();
$flashes = $_SESSION['flash'] ?? [];
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= View::e($title ?? 'My Seled') ?></title>
<meta name="csrf" content="<?= View::e(Csrf::token()) ?>">
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config = { theme: { extend: { colors: { base:'#171717', surface:'#202020', surface2:'#292929', line:'#383838' } } } }</script>
<link rel="stylesheet" href="/assets/css/custom.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="icon" href="/favicon.ico">
</head>
<body class="min-h-screen">
<div class="flex min-h-screen">
  <!-- Sidebar desktop -->
  <aside class="sidebar-desktop w-60 shrink-0 border-r border-[#383838] bg-[#202020] p-4 hidden md:flex flex-col gap-2">
    <a href="<?= $user ? '/home' : '/' ?>" class="flex items-center gap-2 font-extrabold text-lg mb-4"><span class="w-8 h-8 inline-flex items-center justify-center rounded-lg bg-[#292929] border border-[#383838]"><i class="fa-solid fa-toilet"></i></span> My Seled</a>
    <?php if ($user): ?>
      <a href="/home" class="btn btn-ghost justify-start"><i class="fa-solid fa-house w-5"></i> Dashboard</a>
      <a href="/order" class="btn btn-ghost justify-start"><i class="fa-solid fa-circle-plus w-5"></i> Pesan Toilet</a>
      <a href="/orders" class="btn btn-ghost justify-start"><i class="fa-solid fa-box-archive w-5"></i> Riwayat</a>
      <a href="/profile" class="btn btn-ghost justify-start"><i class="fa-solid fa-user w-5"></i> Profil</a>
      <?php if (in_array($user['role'], ['driver','admin'])): ?><a href="/driver" class="btn btn-ghost justify-start"><i class="fa-solid fa-truck-fast w-5"></i> Driver</a><?php endif; ?>
      <?php if ($user['role']==='admin'): ?><a href="/admin" class="btn btn-ghost justify-start"><i class="fa-solid fa-screwdriver-wrench w-5"></i> Admin</a><?php endif; ?>
      <div class="mt-auto card p-3 text-sm"><div class="font-semibold"><?= View::e($user['name']) ?></div><div class="muted text-xs"><?= View::e($user['role']) ?> • <?= View::e($user['email']) ?></div><a href="/logout" class="btn btn-ghost w-full mt-2 text-sm"><i class="fa-solid fa-right-from-bracket"></i> Keluar</a></div>
    <?php else: ?>
      <a href="/" class="btn btn-ghost justify-start">Beranda</a>
      <a href="/login" class="btn btn-ghost justify-start">Masuk</a>
      <a href="/register" class="btn btn-primary justify-start">Daftar</a>
    <?php endif; ?>
  </aside>
  <div class="flex-1 min-w-0">
    <!-- Topbar mobile -->
    <header class="md:hidden flex items-center justify-between p-4 border-b border-[#383838] bg-[#202020] sticky top-0 z-20">
      <a href="<?= $user ? '/home' : '/' ?>" class="font-extrabold"><i class="fa-solid fa-toilet"></i> My Seled</a>
      <div class="flex gap-2">
        <?php if ($user): ?><a href="/order" class="btn btn-primary text-sm !py-2"><i class="fa-solid fa-circle-plus"></i> Pesan</a><a href="/logout" class="btn btn-ghost text-sm !py-2"><i class="fa-solid fa-right-from-bracket"></i> Keluar</a>
        <?php else: ?><a href="/login" class="btn btn-ghost text-sm !py-2"><i class="fa-solid fa-right-to-bracket"></i> Masuk</a><?php endif; ?>
      </div>
    </header>
    <main class="p-4 md:p-8 max-w-5xl mx-auto pb-24 md:pb-12">
      <?php foreach ($flashes as $f): ?><div data-flash="<?= View::e($f['message']) ?>" data-type="<?= View::e($f['type']) ?>"></div><?php endforeach; ?>
      <?= $content ?? '' ?>
    </main>
    <footer class="border-t border-[#383838] p-4 text-center muted text-xs">My Seled — When Nature Calls, We Roll. • SIMULASI hiburan, bukan layanan nyata.</footer>
    <!-- Bottom nav mobile -->
    <?php if ($user): ?>
    <nav class="bottomnav fixed bottom-0 inset-x-0 bg-[#202020] border-t border-[#383838] flex justify-around p-2 z-20">
      <a href="/home" class="p-2 text-center text-xs"><i class="fa-solid fa-house text-base"></i><br>Home</a>
      <a href="/order" class="p-2 text-center text-xs"><i class="fa-solid fa-circle-plus text-base"></i><br>Pesan</a>
      <a href="/orders" class="p-2 text-center text-xs"><i class="fa-solid fa-box-archive text-base"></i><br>Order</a>
      <a href="/profile" class="p-2 text-center text-xs"><i class="fa-solid fa-user text-base"></i><br>Profil</a>
    </nav>
    <?php endif; ?>
  </div>
</div>
<script src="/assets/js/app.js"></script>
<script src="/assets/js/map.js"></script>
<script src="/assets/js/order.js"></script>
<script src="/assets/js/tracking.js"></script>
</body>
</html>
