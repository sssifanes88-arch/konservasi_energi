<?php
require_once __DIR__ . '/../includes/functions.php';
login_wajib(['Admin']);   // hanya Admin yang boleh menghapus checklist
csrf_check();
$c = ambil_checklist($pdo, $_POST['id'] ?? 0);
// detail dihapus dulu supaya aman walaupun foreign key tidak memakai ON DELETE CASCADE
$pdo->beginTransaction();
$pdo->prepare('DELETE FROM detail_checklist WHERE id_checklist = ?')->execute([$c['id_checklist']]);
$pdo->prepare('DELETE FROM checklist WHERE id_checklist = ?')->execute([$c['id_checklist']]);
$pdo->commit();
flash('success', 'Checklist dihapus.');
redirect('checklist/');
