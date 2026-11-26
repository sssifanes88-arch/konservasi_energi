<?php
require_once __DIR__ . '/../includes/functions.php';
login_wajib(['Admin', 'Guru']);

$edit = isset($_GET['nis']);
$row = ['nis' => '', 'nama' => '', 'kelas' => ''];
if ($edit) {
    $st = $pdo->prepare('SELECT * FROM siswa WHERE nis = ?');
    $st->execute([(int)$_GET['nis']]);
    $row = $st->fetch();
    if (!$row) { flash('danger', 'Siswa tidak ditemukan.'); redirect('siswa/'); }
}

$err = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $row['nis']   = $edit ? $row['nis'] : trim($_POST['nis'] ?? '');
    $row['nama']  = trim($_POST['nama'] ?? '');
    $row['kelas'] = trim($_POST['kelas'] ?? '');

    if (!ctype_digit((string)$row['nis']) || strlen((string)$row['nis']) > 10) $err[] = 'NIS harus berupa angka, maksimal 10 digit.';
    if ($row['nama'] === '' || panjang($row['nama']) > 100) $err[] = 'Nama wajib diisi (maksimal 100 karakter).';
    if ($row['kelas'] === '' || panjang($row['kelas']) > 10) $err[] = 'Kelas wajib diisi (maksimal 10 karakter).';

    if (!$err) {
        try {
            if ($edit) {
                $pdo->prepare('UPDATE siswa SET nama=?, kelas=? WHERE nis=?')->execute([$row['nama'], $row['kelas'], $row['nis']]);
                flash('success', 'Data siswa diperbarui.');
            } else {
                $pdo->prepare('INSERT INTO siswa (nis, nama, kelas) VALUES (?,?,?)')->execute([$row['nis'], $row['nama'], $row['kelas']]);
                flash('success', 'Siswa ditambahkan.');
            }
            redirect('siswa/');
        } catch (PDOException $ex) {
            $err[] = $ex->getCode() === '23000' ? 'NIS sudah terdaftar atau nilainya tidak valid.' : 'Data gagal disimpan.';
        }
    }
}
$judul = $edit ? 'Ubah Siswa' : 'Tambah Siswa';
require '../includes/header.php';
?>
<h3 class="mb-3"><?= $judul ?></h3>
<?php foreach ($err as $m): ?><div class="alert alert-danger py-2"><?= e($m) ?></div><?php endforeach; ?>
<div class="card" style="max-width:560px"><div class="card-body">
<form method="post"><?= csrf_field() ?>
  <div class="mb-3"><label class="form-label">NIS</label>
    <input name="nis" class="form-control" value="<?= e($row['nis']) ?>" <?= $edit ? 'readonly' : 'required' ?> inputmode="numeric" maxlength="10"></div>
  <div class="mb-3"><label class="form-label">Nama</label>
    <input name="nama" class="form-control" value="<?= e($row['nama']) ?>" required maxlength="100"></div>
  <div class="mb-3"><label class="form-label">Kelas</label>
    <input name="kelas" class="form-control" value="<?= e($row['kelas']) ?>" required maxlength="10" placeholder="contoh: XI RPL2"></div>
  <button class="btn btn-success">Simpan</button> <a class="btn btn-link" href="index.php">Batal</a>
</form></div></div>
<?php require '../includes/footer.php'; ?>
