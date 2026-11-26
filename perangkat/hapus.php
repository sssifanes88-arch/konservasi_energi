<?php
require_once __DIR__ . '/../includes/functions.php';
login_wajib(['Admin']);   // hanya Admin yang boleh menghapus
csrf_check();
try {
    $pdo->prepare('DELETE FROM perangkat WHERE id_perangkat = ?')->execute([(int)($_POST['id'] ?? 0)]);
    flash('success', 'Perangkat dihapus.');
} catch (PDOException $ex) {
    flash('danger', 'Perangkat tidak bisa dihapus karena masih dipakai di detail checklist.');
}
redirect('perangkat/');
