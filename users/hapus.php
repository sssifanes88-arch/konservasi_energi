<?php
require_once __DIR__ . '/../includes/functions.php';
login_wajib(['Admin']);
csrf_check();
$id = (int)($_POST['id'] ?? 0);
if ($id === (int)user()['id']) {
    flash('danger', 'Anda tidak bisa menghapus akun yang sedang dipakai.');
} else {
    $pdo->prepare('DELETE FROM users WHERE id_user = ?')->execute([$id]);
    flash('success', 'Pengguna dihapus.');
}
redirect('users/');
