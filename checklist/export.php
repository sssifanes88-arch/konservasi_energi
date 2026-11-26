<?php
require_once __DIR__ . '/../includes/export.php';
login_wajib();

$q = trim($_GET['q'] ?? '');
$sql = 'SELECT c.id_checklist, c.tanggal, c.jam, s.nis, s.nama, s.kelas,
               COUNT(d.id_detail) AS jml_item, SUM(d.status = "Ya") AS jml_ya
        FROM checklist c
        JOIN siswa s ON s.nis = c.nis
        LEFT JOIN detail_checklist d ON d.id_checklist = c.id_checklist
        WHERE (s.nama LIKE :a OR s.kelas LIKE :b OR c.tanggal LIKE :c)';
$par = [':a' => "%$q%", ':b' => "%$q%", ':c' => "%$q%"];
if (is_siswa()) { $sql .= ' AND c.nis = :nis'; $par[':nis'] = (int)(user()['nis'] ?? 0); }
$sql .= ' GROUP BY c.id_checklist ORDER BY c.tanggal DESC, c.jam DESC';
$st = $pdo->prepare($sql);
$st->execute($par);

$baris = [];
$no = 1;
foreach ($st->fetchAll() as $r) {
    $baris[] = [$no++, tgl_id($r['tanggal']), substr($r['jam'], 0, 5), $r['nis'], $r['nama'], $r['kelas'],
                (int)$r['jml_ya'], (int)$r['jml_item']];
}

ekspor($_GET['format'] ?? 'pdf', 'checklist', 'Checklist Konservasi Energi',
    ekspor_info($q !== '' ? ['Pencarian: ' . $q] : []),
    [['No', 1], ['Tanggal', 2.2], ['Jam', 1.3], ['NIS', 1.8], ['Nama', 4], ['Kelas', 1.5], ['Item Ya', 1.5], ['Total item', 1.6]],
    $baris, true);
