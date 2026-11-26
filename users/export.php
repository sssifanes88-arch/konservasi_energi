<?php
require_once __DIR__ . '/../includes/export.php';
login_wajib(['Admin']);

// Kata sandi sengaja tidak ikut diekspor.
$data = $pdo->query('SELECT username, nama, level FROM users ORDER BY level, nama')->fetchAll();

$baris = [];
$no = 1;
foreach ($data as $r) $baris[] = [$no++, $r['username'], $r['nama'], $r['level']];

ekspor($_GET['format'] ?? 'pdf', 'data_pengguna', 'Data Pengguna', ekspor_info(),
    [['No', 1], ['Username', 3], ['Nama', 5], ['Level', 2]], $baris);
