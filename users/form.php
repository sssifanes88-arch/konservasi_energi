<?php
require_once __DIR__ . '/../includes/functions.php';
login_wajib(['Admin']);

$levels = ['Admin', 'Guru', 'Siswa'];
$edit = isset($_GET['id']);
$row = ['username' => '', 'nama' => '', 'level' => 'Siswa'];
if ($edit) {
    $st = $pdo->prepare('SELECT id_user, username, nama, level FROM users WHERE id_user = ?');
    $st->execute([(int)$_GET['id']]);
    $row = $st->fetch();
    if (!$row) { flash('danger', 'Pengguna tidak ditemukan.'); redirect('users/'); }
}
// Akun level Siswa: username = NIS siswa
$nis = ($row['level'] === 'Siswa' && ctype_digit((string)$row['username'])) ? (string)$row['username'] : '';
$daftarSiswa = $pdo->query('SELECT nis, nama, kelas FROM siswa ORDER BY kelas, nama')->fetchAll();

$err = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $row['username'] = trim($_POST['username'] ?? '');
    $row['nama']     = trim($_POST['nama'] ?? '');
    $row['level']    = $_POST['level'] ?? '';
    $nis             = trim($_POST['nis'] ?? '');
    $password        = $_POST['password'] ?? '';

    if (!in_array($row['level'], $levels, true)) $err[] = 'Level tidak valid.';
    if ($row['level'] === 'Siswa') {
        $cek = $pdo->prepare('SELECT 1 FROM siswa WHERE nis = ?');
        $cek->execute([(int)$nis]);
        if (!ctype_digit($nis) || !$cek->fetchColumn()) $err[] = 'Akun level Siswa harus dipilih dari data siswa (username = NIS).';
        else $row['username'] = $nis;
    }
    if ($row['username'] === '' || panjang($row['username']) > 50) $err[] = 'Username wajib diisi (maksimal 50 karakter).';
    if ($row['nama'] === '' || panjang($row['nama']) > 100) $err[] = 'Nama wajib diisi (maksimal 100 karakter).';
    if (!$edit && strlen($password) < 6) $err[] = 'Password minimal 6 karakter.';
    if ($edit && $password !== '' && strlen($password) < 6) $err[] = 'Password baru minimal 6 karakter.';

    if (!$err) {
        try {
            if ($edit) {
                if ($password !== '') {
                    $pdo->prepare('UPDATE users SET username=?, nama=?, level=?, password=? WHERE id_user=?')
                        ->execute([$row['username'], $row['nama'], $row['level'], password_hash($password, PASSWORD_DEFAULT), (int)$_GET['id']]);
                } else {
                    $pdo->prepare('UPDATE users SET username=?, nama=?, level=? WHERE id_user=?')
                        ->execute([$row['username'], $row['nama'], $row['level'], (int)$_GET['id']]);
                }
                flash('success', 'Pengguna diperbarui.');
            } else {
                $pdo->prepare('INSERT INTO users (username, password, nama, level) VALUES (?,?,?,?)')
                    ->execute([$row['username'], password_hash($password, PASSWORD_DEFAULT), $row['nama'], $row['level']]);
                flash('success', 'Pengguna ditambahkan.');
            }
            redirect('users/');
        } catch (PDOException $ex) {
            $err[] = $ex->getCode() === '23000' ? 'Username sudah dipakai.' : 'Data gagal disimpan.';
        }
    }
}
$judul = $edit ? 'Ubah Pengguna' : 'Tambah Pengguna';
require '../includes/header.php';
?>
<h3 class="mb-3"><?= $judul ?></h3>
<?php foreach ($err as $m): ?><div class="alert alert-danger py-2"><?= e($m) ?></div><?php endforeach; ?>
<div class="card" style="max-width:560px"><div class="card-body">
<form method="post"><?= csrf_field() ?>
  <div class="mb-3"><label class="form-label">Level</label>
    <select name="level" id="level" class="form-select">
      <?php foreach ($levels as $l): ?><option <?= $row['level'] === $l ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?>
    </select></div>
  <div class="mb-3" id="boxNis"><label class="form-label">Siswa (NIS menjadi username)</label>
    <select name="nis" id="nis" class="form-select">
      <option value="">Pilih siswa</option>
      <?php foreach ($daftarSiswa as $s): ?>
        <option value="<?= $s['nis'] ?>" data-nama="<?= e($s['nama']) ?>" <?= $nis === (string)$s['nis'] ? 'selected' : '' ?>><?= e($s['kelas'] . ' - ' . $s['nama'] . ' (' . $s['nis'] . ')') ?></option>
      <?php endforeach; ?>
    </select></div>
  <div class="mb-3" id="boxUser"><label class="form-label">Username</label>
    <input name="username" id="username" class="form-control" value="<?= e($row['username']) ?>" maxlength="50"></div>
  <div class="mb-3"><label class="form-label">Nama</label>
    <input name="nama" id="nama" class="form-control" value="<?= e($row['nama']) ?>" required maxlength="100"></div>
  <div class="mb-3"><label class="form-label">Password</label>
    <input name="password" type="password" class="form-control" <?= $edit ? '' : 'required' ?> autocomplete="new-password">
    <?php if ($edit): ?><div class="form-text">Kosongkan jika tidak ingin mengganti password.</div><?php endif; ?></div>
  <button class="btn btn-success">Simpan</button> <a class="btn btn-link" href="index.php">Batal</a>
</form></div></div>
<script>
  const level = document.getElementById('level'), boxNis = document.getElementById('boxNis'),
        boxUser = document.getElementById('boxUser'), user = document.getElementById('username'),
        nis = document.getElementById('nis'), nama = document.getElementById('nama');
  function tampil() {
    const siswa = level.value === 'Siswa';
    boxNis.style.display = siswa ? '' : 'none';
    boxUser.style.display = siswa ? 'none' : '';
    user.required = !siswa;
  }
  nis.addEventListener('change', () => {          // isi nama otomatis dari data siswa bila masih kosong
    const o = nis.options[nis.selectedIndex];
    if (o && o.dataset.nama && nama.value.trim() === '') nama.value = o.dataset.nama;
  });
  level.addEventListener('change', tampil); tampil();
</script>
<?php require '../includes/footer.php'; ?>
