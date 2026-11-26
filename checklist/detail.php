<?php
require_once __DIR__ . '/../includes/functions.php';
login_wajib();
$c = ambil_checklist($pdo, $_GET['id'] ?? 0);
$judul = 'Detail Checklist';

$st = $pdo->prepare('SELECT d.*, p.nama_perangkat, p.jenis
    FROM detail_checklist d JOIN perangkat p ON p.id_perangkat = d.id_perangkat
    WHERE d.id_checklist = ?
    ORDER BY FIELD(d.tahapan, "SEBELUM","SELAMA","SESUDAH","5R"), p.nama_perangkat');
$st->execute([$c['id_checklist']]);
$data = $st->fetchAll();

require '../includes/header.php';
?>
<a href="index.php" class="text-decoration-none">&larr; Kembali ke daftar</a>
<div class="d-flex justify-content-between align-items-start my-3">
  <div>
    <h3 class="mb-0"><?= e($c['nama']) ?> <small class="text-muted fs-6">Kelas <?= e($c['kelas']) ?></small></h3>
    <div class="text-muted">NIS <?= e($c['nis']) ?> &middot; <?= e($c['tanggal']) ?> pukul <?= e(substr($c['jam'], 0, 5)) ?></div>
  </div>
  <div class="text-end d-flex gap-2 flex-wrap justify-content-end">
    <?= tombol_ekspor('detail_export.php', ['id' => $c['id_checklist']]) ?>
    <a class="btn btn-success" href="isi_massal.php?id_checklist=<?= $c['id_checklist'] ?>">Isi massal</a>
    <a class="btn btn-outline-success" href="detail_form.php?id_checklist=<?= $c['id_checklist'] ?>">Tambah satu</a>
  </div>
</div>
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0 align-middle">
  <thead><tr><th>Tahapan</th><th>Perangkat</th><th>Kondisi</th><th>Status</th><th>Keterangan</th><th style="width:150px"></th></tr></thead><tbody>
  <?php foreach ($data as $r): ?>
    <tr><td><?= e($r['tahapan']) ?></td>
      <td><?= e($r['nama_perangkat']) ?> <span class="text-muted small"><?= e($r['jenis']) ?></span></td>
      <td><?= e($r['kondisi']) ?></td>
      <td><span class="badge text-bg-<?= $r['status'] === 'Ya' ? 'success' : 'secondary' ?>"><?= e($r['status']) ?></span></td>
      <td><?= e($r['keterangan']) ?></td>
      <td class="text-end">
        <a class="btn btn-sm btn-outline-secondary" href="detail_form.php?id=<?= $r['id_detail'] ?>">Ubah</a>
        <form method="post" action="detail_hapus.php" class="d-inline" onsubmit="return confirm('Hapus detail ini?')">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id_detail'] ?>">
          <button class="btn btn-sm btn-outline-danger">Hapus</button></form>
      </td></tr>
  <?php endforeach; if (!$data): ?>
    <tr><td colspan="6" class="text-center text-muted py-4">Belum ada detail. Klik "Tambah detail" untuk mencatat perangkat yang dicek.</td></tr>
  <?php endif; ?>
  </tbody></table></div></div>
<?php require '../includes/footer.php'; ?>
