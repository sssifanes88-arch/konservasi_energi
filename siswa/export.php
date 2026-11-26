<?php
require_once __DIR__ . '/../includes/export.php';
login_wajib(['Admin', 'Guru']);

$q = trim($_GET['q'] ?? '');
$st = $pdo->prepare('SELECT * FROM siswa WHERE nama LIKE :a OR kelas LIKE :b OR CAST(nis AS CHAR) LIKE :c ORDER BY kelas, nama');
$st->execute([':a' => "%$q%", ':b' => "%$q%", ':c' => "%$q%"]);

$baris = [];
$no = 1;
foreach ($st->fetchAll() as $r) $baris[] = [$no++, $r['nis'], $r['nama'], $r['kelas']];

ekspor($_GET['format'] ?? 'pdf', 'data_siswa', 'Data Siswa',
    ekspor_info($q !== '' ? ['Pencarian: ' . $q] : []),
    [['No', 1], ['NIS', 2], ['Nama', 6], ['Kelas', 2]], $baris);
