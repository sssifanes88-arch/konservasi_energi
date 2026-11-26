<?php
require_once __DIR__ . '/../includes/functions.php';
login_wajib();
csrf_check();
$st = $pdo->prepare('SELECT id_checklist FROM detail_checklist WHERE id_detail = ?');
$st->execute([(int)($_POST['id'] ?? 0)]);
$idChecklist = $st->fetchColumn();
if (!$idChecklist) { flash('danger', 'Detail tidak ditemukan.'); redirect('checklist/'); }
ambil_checklist($pdo, $idChecklist);   // validasi kepemilikan
$pdo->prepare('DELETE FROM detail_checklist WHERE id_detail = ?')->execute([(int)$_POST['id']]);
flash('success', 'Detail dihapus.');
redirect('checklist/detail.php?id=' . $idChecklist);
