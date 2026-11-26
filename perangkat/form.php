<?php
require_once __DIR__ . '/../includes/functions.php';
login_wajib(['Admin', 'Guru']);

$edit = isset($_GET['id']);
$row = ['nama_perangkat' => '', 'jenis' => '', 'keterangan' => ''];
if ($edit) {
    $st = $pdo->prepare('SELECT * FROM perangkat WHERE id_perangkat = ?');
    $st->execute([(int)$_GET['id']]);
    $row = $st->fetch();
    if (!$row) { flash('danger', 'Perangkat tidak ditemukan.'); redirect('perangkat/'); }
}

$err = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $row['nama_perangkat'] = trim($_POST['nama_perangkat'] ?? '');
    $row['jenis']          = trim($_POST['jenis'] ?? '');
    $row['keterangan']     = trim($_POST['keterangan'] ?? '');

    if ($row['nama_perangkat'] === '' || panjang($row['nama_perangkat']) > 100) $err[] = 'Nama perangkat wajib diisi (maksimal 100 karakter).';
    if ($row['jenis'] === '' || panjang($row['jenis']) > 50) $err[] = 'Jenis wajib diisi (maksimal 50 karakter).';

    if (!$err) {
        try {
            if ($edit) {
                $pdo->prepare('UPDATE perangkat SET nama_perangkat=?, jenis=?, keterangan=? WHERE id_perangkat=?')
                    ->execute([$row['nama_perangkat'], $row['jenis'], $row['keterangan'], (int)$_GET['id']]);
                flash('success', 'Perangkat diperbarui.');
            } else {
                $pdo->prepare('INSERT INTO perangkat (nama_perangkat, jenis, keterangan) VALUES (?,?,?)')
                    ->execute([$row['nama_perangkat'], $row['jenis'], $row['keterangan']]);
                flash('success', 'Perangkat ditambahkan.');
            }
            redirect('perangkat/');
        } catch (PDOException $ex) {
            $err[] = 'Data gagal disimpan.';
        }
    }
}
$judul = $edit ? 'Ubah Perangkat' : 'Tambah Perangkat';
require '../includes/header.php';
?>
<h3 class="mb-3"><?= $judul ?></h3>
<?php foreach ($err as $m): ?><div class="alert alert-danger py-2"><?= e($m) ?></div><?php endforeach; ?>
<div class="card" style="max-width:560px"><div class="card-body">
<form method="post"><?= csrf_field() ?>
  <div class="mb-3"><label class="form-label">Nama perangkat</label>
    <input name="nama_perangkat" class="form-control" value="<?= e($row['nama_perangkat']) ?>" required maxlength="100"></div>
  <div class="mb-3"><label class="form-label">Jenis</label>
    <input name="jenis" class="form-control" value="<?= e($row['jenis']) ?>" required maxlength="50" placeholder="contoh: Penerangan, Pendingin"></div>
  <div class="mb-3"><label class="form-label">Keterangan</label>
    <textarea name="keterangan" class="form-control" rows="3"><?= e($row['keterangan']) ?></textarea></div>
  <button class="btn btn-success">Simpan</button> <a class="btn btn-link" href="index.php">Batal</a>
</form></div></div>
<?php require '../includes/footer.php'; ?>
