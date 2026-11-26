<?php
require_once __DIR__ . '/../includes/functions.php';
login_wajib();

$edit = isset($_GET['id']);
$row = ['nis' => '', 'tanggal' => date('Y-m-d'), 'jam' => date('H:i')];
if ($edit) {
    $c = ambil_checklist($pdo, $_GET['id']);
    $row = ['nis' => $c['nis'], 'tanggal' => $c['tanggal'], 'jam' => substr($c['jam'], 0, 5)];
}
if (is_siswa()) $row['nis'] = (int)(user()['nis'] ?? 0);

$daftarSiswa = $pdo->query('SELECT nis, nama, kelas FROM siswa ORDER BY kelas, nama')->fetchAll();

$err = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $row['nis']     = is_siswa() ? (int)(user()['nis'] ?? 0) : (int)($_POST['nis'] ?? 0);
    $row['tanggal'] = $_POST['tanggal'] ?? '';
    $row['jam']     = $_POST['jam'] ?? '';

    $cek = $pdo->prepare('SELECT 1 FROM siswa WHERE nis = ?');
    $cek->execute([$row['nis']]);
    if (!$cek->fetchColumn()) $err[] = is_siswa() ? 'Akun Anda belum terhubung ke data siswa. Hubungi admin.' : 'Pilih siswa.';
    $d = DateTime::createFromFormat('Y-m-d', $row['tanggal']);
    if (!$d || $d->format('Y-m-d') !== $row['tanggal']) $err[] = 'Tanggal tidak valid.';
    if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $row['jam'])) $err[] = 'Jam tidak valid.';

    if (!$err) {
        if ($edit) {
            $pdo->prepare('UPDATE checklist SET nis=?, tanggal=?, jam=? WHERE id_checklist=?')
                ->execute([$row['nis'], $row['tanggal'], $row['jam'], (int)$_GET['id']]);
            flash('success', 'Checklist diperbarui.');
            redirect('checklist/detail.php?id=' . (int)$_GET['id']);
        }
        $pdo->prepare('INSERT INTO checklist (nis, tanggal, jam) VALUES (?,?,?)')
            ->execute([$row['nis'], $row['tanggal'], $row['jam']]);
        flash('success', 'Checklist dibuat. Silakan tambahkan detail perangkat.');
        redirect('checklist/detail.php?id=' . $pdo->lastInsertId());
    }
}
$judul = $edit ? 'Ubah Checklist' : 'Buat Checklist';
require '../includes/header.php';
?>
<h3 class="mb-3"><?= $judul ?></h3>
<?php foreach ($err as $m): ?><div class="alert alert-danger py-2"><?= e($m) ?></div><?php endforeach; ?>
<div class="card" style="max-width:560px"><div class="card-body">
<form method="post"><?= csrf_field() ?>
  <div class="mb-3"><label class="form-label">Siswa</label>
    <?php if (is_siswa()): ?>
      <input class="form-control" value="NIS <?= e($row['nis']) ?> (akun Anda)" readonly>
    <?php else: ?>
      <select name="nis" class="form-select" required>
        <option value="">Pilih siswa</option>
        <?php foreach ($daftarSiswa as $s): ?>
          <option value="<?= $s['nis'] ?>" <?= (int)$row['nis'] === (int)$s['nis'] ? 'selected' : '' ?>><?= e($s['kelas'] . ' - ' . $s['nama'] . ' (' . $s['nis'] . ')') ?></option>
        <?php endforeach; ?>
      </select>
    <?php endif; ?></div>
  <div class="row">
    <div class="col-7 mb-3"><label class="form-label">Tanggal</label>
      <input type="date" name="tanggal" class="form-control" value="<?= e($row['tanggal']) ?>" required></div>
    <div class="col-5 mb-3"><label class="form-label">Jam</label>
      <input type="time" name="jam" class="form-control" value="<?= e($row['jam']) ?>" required></div>
  </div>
  <button class="btn btn-success">Simpan</button> <a class="btn btn-link" href="index.php">Batal</a>
</form></div></div>
<?php require '../includes/footer.php'; ?>
