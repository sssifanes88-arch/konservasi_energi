<?php
require_once __DIR__ . '/../includes/functions.php';
login_wajib();                       // semua level boleh mengganti password sendiri
$judul = 'Ganti Password';

$err = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $lama = $_POST['lama'] ?? '';
    $baru = $_POST['baru'] ?? '';
    $ulang = $_POST['ulang'] ?? '';

    $st = $pdo->prepare('SELECT password FROM users WHERE id_user = ?');
    $st->execute([(int)user()['id']]);
    $hash = $st->fetchColumn();

    if (!$hash || !password_verify($lama, $hash)) $err[] = 'Password lama salah.';
    if (strlen($baru) < 6)                          $err[] = 'Password baru minimal 6 karakter.';
    if ($baru !== $ulang)                           $err[] = 'Konfirmasi password tidak sama.';
    if (!$err && $lama === $baru)                   $err[] = 'Password baru harus berbeda dari yang lama.';

    if (!$err) {
        // Simpan HASH-nya, bukan password asli
        $pdo->prepare('UPDATE users SET password = ? WHERE id_user = ?')
            ->execute([password_hash($baru, PASSWORD_DEFAULT), (int)user()['id']]);
        flash('success', 'Password berhasil diganti.');
        redirect('akun/password.php');
    }
}
require '../includes/header.php';
?>
<div><h3 class="mb-0">Ganti Password</h3><div class="muted">Gunakan password yang sulit ditebak.</div></div>
<?php foreach ($err as $m): ?><div class="alert alert-danger py-2 mb-0"><?= e($m) ?></div><?php endforeach; ?>
<div class="card" style="max-width:480px"><div class="card-body">
<form method="post" autocomplete="off"><?= csrf_field() ?>
  <div class="mb-3"><label class="form-label">Password lama</label><input type="password" name="lama" class="form-control" required></div>
  <div class="mb-3"><label class="form-label">Password baru</label><input type="password" name="baru" class="form-control" required minlength="6"></div>
  <div class="mb-3"><label class="form-label">Ulangi password baru</label><input type="password" name="ulang" class="form-control" required minlength="6"></div>
  <button class="btn btn-success">Simpan</button>
</form></div></div>
<?php require '../includes/footer.php'; ?>
