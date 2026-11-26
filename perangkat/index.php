<?php
require_once __DIR__ . '/../includes/functions.php';
login_wajib(['Admin', 'Guru']);
$judul = 'Data Perangkat';

$q = trim($_GET['q'] ?? '');
$st = $pdo->prepare('SELECT * FROM perangkat WHERE nama_perangkat LIKE :a OR jenis LIKE :b ORDER BY jenis, nama_perangkat');
$st->execute([':a' => "%$q%", ':b' => "%$q%"]);
$data = $st->fetchAll();

require '../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Data Perangkat</h3>
  <a class="btn btn-success" href="form.php">Tambah perangkat</a>
</div>
<form class="mb-3" method="get"><div class="input-group">
  <input name="q" value="<?= e($q) ?>" class="form-control" placeholder="Cari nama atau jenis perangkat">
  <button class="btn btn-outline-success">Cari</button></div></form>
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0 align-middle">
  <thead><tr><th>Nama perangkat</th><th>Jenis</th><th>Keterangan</th><th style="width:170px"></th></tr></thead><tbody>
  <?php foreach ($data as $r): ?>
    <tr><td><?= e($r['nama_perangkat']) ?></td><td><?= e($r['jenis']) ?></td><td><?= e($r['keterangan']) ?></td>
      <td class="text-end">
        <a class="btn btn-sm btn-outline-secondary" href="form.php?id=<?= $r['id_perangkat'] ?>">Ubah</a>
        <?php if (user()['level'] === 'Admin'): ?>
        <form method="post" action="hapus.php" class="d-inline" onsubmit="return confirm('Hapus perangkat ini?')">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id_perangkat'] ?>">
          <button class="btn btn-sm btn-outline-danger">Hapus</button></form>
        <?php endif; ?>
      </td></tr>
  <?php endforeach; if (!$data): ?>
    <tr><td colspan="4" class="text-center text-muted py-4">Belum ada perangkat.</td></tr>
  <?php endif; ?>
  </tbody></table></div></div>
<?php require '../includes/footer.php'; ?>
