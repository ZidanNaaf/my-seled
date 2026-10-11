<?php
use App\Core\View;
use App\Core\Csrf;
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
<link rel="stylesheet" href="/assets/css/custom.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="icon" href="/favicon.ico">
</head>
<body class="min-h-screen">
<header class="border-b border-[#383838] bg-[#202020] sticky top-0 z-20">
  <div class="max-w-6xl mx-auto flex items-center justify-between p-4">
    <a href="/" class="font-extrabold text-lg"><i class="fa-solid fa-toilet"></i> My Seled</a>
    <nav class="flex gap-2 text-sm">
      <a href="/#cara-kerja" class="btn btn-ghost !py-2 hidden sm:inline-flex">Cara kerja</a>
      <a href="/#paket" class="btn btn-ghost !py-2 hidden sm:inline-flex">Paket</a>
      <a href="/login" class="btn btn-ghost !py-2"><i class="fa-solid fa-right-to-bracket"></i> Masuk</a>
      <a href="/register" class="btn btn-primary !py-2"><i class="fa-solid fa-toilet"></i> Pesan Toilet</a>
    </nav>
  </div>
</header>
<main class="max-w-6xl mx-auto p-4 md:p-8">
  <?php foreach ($flashes as $f): ?><div data-flash="<?= View::e($f['message']) ?>" data-type="<?= View::e($f['type']) ?>"></div><?php endforeach; ?>
  <?= $content ?? '' ?>
</main>
<footer class="border-t border-[#383838] mt-12 p-6 text-center muted text-sm">
  <div class="font-bold text-[#F5F5F5]">My Seled</div>
  <div>When Nature Calls, We Roll. • Your Throne. Our Mission.</div>
  <div class="mt-2 text-xs">Proyek hiburan (shitposting berkualitas). Semua armada, harga & tracking adalah SIMULASI.</div>
</footer>
<script src="/assets/js/app.js"></script>
<script src="/assets/js/map.js"></script>
<script src="/assets/js/order.js"></script>
<script src="/assets/js/tracking.js"></script>
</body>
</html>
