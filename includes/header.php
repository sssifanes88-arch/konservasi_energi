<?php
// Kerangka halaman: sidebar (kiri) + navbar (atas) + area isi. Dipakai semua halaman setelah login.
require_once __DIR__ . '/functions.php';
$u     = user();
$f     = flash();
$aktif = halaman_aktif();
$level = $u['level'] ?? '';
?>
<!doctype html>
<html lang="id" data-bs-theme="light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($judul ?? 'Konservasi Energi') ?> | Konservasi Energi</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;700&display=swap" rel="stylesheet">
  <link href="<?= BASE_URL ?>/assets/css/app.css" rel="stylesheet">
  <script>
    // Dijalankan sebelum halaman tampil agar tidak "berkedip": ambil tema & status sidebar yang tersimpan.
    try {
      var t = localStorage.getItem('tema'); if (t) document.documentElement.setAttribute('data-bs-theme', t);
      if (localStorage.getItem('sidebar') === 'tutup') document.documentElement.classList.add('side-closed');
    } catch (e) {}
  </script>
</head>
<body>
<div class="app-shell">
  <aside class="sidebar" id="sidebar">
    <a class="brand" href="<?= BASE_URL ?>/index.php"><?= logo(38) ?><span>Konservasi Energi</span></a>
    <?= menu_item('dashboard', 'index.php', 'home', 'Dashboard', $aktif) ?>

    <div class="grp">PENGELOLAAN</div>
    <?php if (in_array($level, ['Admin', 'Guru'], true)): ?>
      <?= menu_item('siswa', 'siswa/', 'siswa', 'Siswa', $aktif) ?>
      <?= menu_item('perangkat', 'perangkat/', 'bolt', 'Perangkat', $aktif) ?>
      <?= menu_item('checklist', 'checklist/', 'list', 'Checklist', $aktif) ?>
    <?php else: ?>
      <?= menu_item('checklist', 'checklist/', 'list', 'Checklist saya', $aktif) ?>
    <?php endif; ?>
    <?php if ($level === 'Admin'): ?>
      <?= menu_item('users', 'users/', 'shield', 'Pengguna', $aktif) ?>
    <?php endif; ?>

    <div class="grp">SETTING</div>
    <!-- Menu Setting bisa dibuka/tutup (Bootstrap collapse); berisi Ganti password dan Mode gelap -->
    <button class="menu menu-btn" type="button" data-bs-toggle="collapse" data-bs-target="#subSetting" aria-expanded="<?= $aktif === 'akun' ? 'true' : 'false' ?>">
      <?= ikon('set') ?><span>Setting</span><span class="chv"><?= ikon('chev') ?></span>
    </button>
    <div class="collapse sub2 <?= $aktif === 'akun' ? 'show' : '' ?>" id="subSetting">
      <?= menu_item('akun', 'akun/password.php', 'key', 'Ganti password', $aktif) ?>
      <button class="menu menu-btn" type="button" id="btnTema" role="switch" aria-checked="false">
        <?= ikon('moon') ?><span>Mode gelap</span><span class="sw"></span>
      </button>
    </div>

    <div class="grp">LAINNYA</div>
    <?= menu_item('tentang', 'tentang.php', 'info', 'Tentang', $aktif) ?>
  </aside>
  <div class="backdrop" id="backdrop"></div>

  <div class="app-main">
    <header class="topbar">
      <div class="d-flex align-items-center gap-3">
        <button class="burger" id="btnSidebar" type="button" aria-label="Buka atau tutup sidebar"><?= ikon('menu') ?></button>
        <strong class="judul-halaman"><?= e($judul ?? 'Dashboard') ?></strong>
      </div>
      <div class="dropdown">
        <button class="user-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
          <span class="user-nama d-none d-md-block"><b><?= e($u['nama'] ?? '') ?></b><small><?= e($level) ?></small></span>
          <span class="avatar"><?= ikon('user') ?></span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow">
          <li class="dropdown-header d-md-none"><?= e($u['nama'] ?? '') ?> (<?= e($level) ?>)</li>
          <li><a class="dropdown-item" href="<?= BASE_URL ?>/logout.php"><?= ikon('out') ?> Keluar</a></li>
        </ul>
      </div>
    </header>
    <main class="page">
<?php if ($f): ?>
      <div class="alert alert-<?= e($f[0]) ?> alert-dismissible fade show"><?= e($f[1]) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
