<?php
require_once __DIR__ . '/../includes/functions.php';
login_wajib(['Admin']);   // hanya Admin yang boleh menghapus
csrf_check();
$nis = (int)($_POST['nis'] ?? 0);
try {
    $cek = $pdo->prepare('SELECT COUNT(*) FROM checklist WHERE nis = ?');
    $cek->execute([$nis]);
    if ($cek->fetchColumn() > 0) {
        flash('danger', 'Siswa tidak bisa dihapus karena masih memiliki data checklist. Hapus checklist-nya dulu.');
    } else {
        $pdo->beginTransaction();
        // akun login siswa (username = NIS) ikut dihapus
        $pdo->prepare("DELETE FROM users WHERE level = 'Siswa' AND username = ?")->execute([(string)$nis]);
        $pdo->prepare('DELETE FROM siswa WHERE nis = ?')->execute([$nis]);
        $pdo->commit();
        flash('success', 'Siswa dihapus.');
    }
} catch (PDOException $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    flash('danger', 'Siswa tidak bisa dihapus.');
}
redirect('siswa/');
