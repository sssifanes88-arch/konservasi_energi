<?php
require_once __DIR__ . '/../includes/functions.php';
login_wajib(['Admin', 'Guru']);
$judul = 'Data Siswa';

$q = trim($_GET['q'] ?? '');
$st = $pdo->prepare('SELECT * FROM siswa WHERE nama LIKE :a OR kelas LIKE :b OR CAST(nis AS CHAR) LIKE :c ORDER BY kelas, nama');
$st->execute([':a' => "%$q%", ':b' => "%$q%", ':c' => "%$q%"]);
$data = $st->fetchAll();

require '../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Data Siswa</h3>
  <div class="d-flex gap-2"><?= tombol_ekspor('export.php', ['q' => $q]) ?><a class="btn btn-success" href="form.php">Tambah siswa</a></div>
</div>
<form class="mb-3" method="get"><div class="input-group">
  <input name="q" value="<?= e($q) ?>" class="form-control" placeholder="Cari NIS, nama, atau kelas">
  <button class="btn btn-outline-success">Cari</button></div></form>
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0 align-middle">
  <thead><tr><th>NIS</th><th>Nama</th><th>Kelas</th><th style="width:170px"></th></tr></thead><tbody>
  <?php foreach ($data as $r): ?>
    <tr><td><?= e($r['nis']) ?></td><td><?= e($r['nama']) ?></td><td><?= e($r['kelas']) ?></td>
      <td class="text-end">
        <a class="btn btn-sm btn-outline-secondary" href="form.php?nis=<?= $r['nis'] ?>">Ubah</a>
        <?php if (user()['level'] === 'Admin'): ?>
        <form method="post" action="hapus.php" class="d-inline" onsubmit="return confirm('Hapus siswa ini?')">
          <?= csrf_field() ?><input type="hidden" name="nis" value="<?= $r['nis'] ?>">
          <button class="btn btn-sm btn-outline-danger">Hapus</button></form>
        <?php endif; ?>
      </td></tr>
  <?php endforeach; if (!$data): ?>
    <tr><td colspan="4" class="text-center text-muted py-4">Belum ada data siswa.</td></tr>
  <?php endif; ?>
  </tbody></table></div></div>
<?php require '../includes/footer.php'; ?>
