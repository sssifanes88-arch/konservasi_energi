<?php
require_once __DIR__ . '/../includes/functions.php';
login_wajib();

$tahapan = ['SEBELUM', 'SELAMA', 'SESUDAH', '5R'];
$status  = ['Ya', 'Tidak'];

$edit = isset($_GET['id']);
if ($edit) {
    $st = $pdo->prepare('SELECT * FROM detail_checklist WHERE id_detail = ?');
    $st->execute([(int)$_GET['id']]);
    $row = $st->fetch();
    if (!$row) { flash('danger', 'Detail tidak ditemukan.'); redirect('checklist/'); }
    $idChecklist = (int)$row['id_checklist'];
} else {
    $idChecklist = (int)($_GET['id_checklist'] ?? 0);
    $row = ['id_perangkat' => '', 'tahapan' => 'SEBELUM', 'kondisi' => '', 'status' => 'Ya', 'keterangan' => ''];
}
$c = ambil_checklist($pdo, $idChecklist);   // validasi kepemilikan

$perangkat = $pdo->query('SELECT id_perangkat, nama_perangkat, jenis FROM perangkat ORDER BY jenis, nama_perangkat')->fetchAll();

$err = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $row['id_perangkat'] = (int)($_POST['id_perangkat'] ?? 0);
    $row['tahapan']      = $_POST['tahapan'] ?? '';
    $row['kondisi']      = trim($_POST['kondisi'] ?? '');
    $row['status']       = $_POST['status'] ?? '';
    $row['keterangan']   = trim($_POST['keterangan'] ?? '');

    $cek = $pdo->prepare('SELECT 1 FROM perangkat WHERE id_perangkat = ?');
    $cek->execute([$row['id_perangkat']]);
    if (!$cek->fetchColumn()) $err[] = 'Pilih perangkat.';
    if (!in_array($row['tahapan'], $tahapan, true)) $err[] = 'Tahapan tidak valid.';
    if ($row['kondisi'] === '' || panjang($row['kondisi']) > 100) $err[] = 'Kondisi wajib diisi (maksimal 100 karakter).';
    if (!in_array($row['status'], $status, true)) $err[] = 'Status tidak valid.';

    if (!$err) {
        if ($edit) {
            $pdo->prepare('UPDATE detail_checklist SET id_perangkat=?, tahapan=?, kondisi=?, status=?, keterangan=? WHERE id_detail=?')
                ->execute([$row['id_perangkat'], $row['tahapan'], $row['kondisi'], $row['status'], $row['keterangan'], (int)$_GET['id']]);
            flash('success', 'Detail diperbarui.');
        } else {
            $pdo->prepare('INSERT INTO detail_checklist (id_checklist, id_perangkat, tahapan, kondisi, status, keterangan) VALUES (?,?,?,?,?,?)')
                ->execute([$idChecklist, $row['id_perangkat'], $row['tahapan'], $row['kondisi'], $row['status'], $row['keterangan']]);
            flash('success', 'Detail ditambahkan.');
        }
        redirect('checklist/detail.php?id=' . $idChecklist);
    }
}
$judul = $edit ? 'Ubah Detail' : 'Tambah Detail';
require '../includes/header.php';
?>
<h3 class="mb-1"><?= $judul ?></h3>
<p class="text-muted"><?= e($c['nama']) ?> &middot; <?= e($c['tanggal']) ?></p>
<?php foreach ($err as $m): ?><div class="alert alert-danger py-2"><?= e($m) ?></div><?php endforeach; ?>
<div class="card" style="max-width:560px"><div class="card-body">
<form method="post"><?= csrf_field() ?>
  <div class="mb-3"><label class="form-label">Perangkat</label>
    <select name="id_perangkat" class="form-select" required>
      <option value="">Pilih perangkat</option>
      <?php foreach ($perangkat as $p): ?>
        <option value="<?= $p['id_perangkat'] ?>" <?= (int)$row['id_perangkat'] === (int)$p['id_perangkat'] ? 'selected' : '' ?>><?= e($p['nama_perangkat'] . ' (' . $p['jenis'] . ')') ?></option>
      <?php endforeach; ?>
    </select></div>
  <div class="mb-3"><label class="form-label">Tahapan</label>
    <select name="tahapan" class="form-select">
      <?php foreach ($tahapan as $t): ?><option <?= $row['tahapan'] === $t ? 'selected' : '' ?>><?= $t ?></option><?php endforeach; ?>
    </select></div>
  <div class="mb-3"><label class="form-label">Kondisi</label>
    <input name="kondisi" class="form-control" value="<?= e($row['kondisi']) ?>" required maxlength="100" placeholder="contoh: Menyala, Dimatikan, Standby"></div>
  <div class="mb-3"><label class="form-label d-block">Sesuai (dijalankan)?</label>
    <?php foreach ($status as $s): ?>
      <div class="form-check form-check-inline">
        <input class="form-check-input" type="radio" name="status" id="st<?= $s ?>" value="<?= $s ?>" <?= $row['status'] === $s ? 'checked' : '' ?>>
        <label class="form-check-label" for="st<?= $s ?>"><?= $s ?></label></div>
    <?php endforeach; ?></div>
  <div class="mb-3"><label class="form-label">Keterangan</label>
    <textarea name="keterangan" class="form-control" rows="3"><?= e($row['keterangan']) ?></textarea></div>
  <button class="btn btn-success">Simpan</button>
  <a class="btn btn-link" href="detail.php?id=<?= $idChecklist ?>">Batal</a>
</form></div></div>
<?php require '../includes/footer.php'; ?>
