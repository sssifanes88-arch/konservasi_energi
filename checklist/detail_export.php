<?php
require_once __DIR__ . '/../includes/export.php';
login_wajib();
$c = ambil_checklist($pdo, $_GET['id'] ?? 0);   // siswa hanya bisa mengekspor miliknya sendiri

$st = $pdo->prepare('SELECT d.*, p.nama_perangkat, p.jenis
    FROM detail_checklist d JOIN perangkat p ON p.id_perangkat = d.id_perangkat
    WHERE d.id_checklist = ?
    ORDER BY FIELD(d.tahapan, "SEBELUM","SELAMA","SESUDAH","5R"), p.nama_perangkat');
$st->execute([$c['id_checklist']]);

$baris = [];
$no = 1;
foreach ($st->fetchAll() as $r) {
    $baris[] = [$no++, $r['tahapan'], $r['nama_perangkat'], $r['jenis'], $r['kondisi'], $r['status'], $r['keterangan']];
}

ekspor($_GET['format'] ?? 'pdf', 'detail_checklist_' . $c['nis'], 'Detail Checklist Konservasi Energi',
    ekspor_info([
        'Siswa: ' . $c['nama'] . ' (NIS ' . $c['nis'] . ') - Kelas ' . $c['kelas'],
        'Waktu: ' . tgl_id($c['tanggal']) . ' pukul ' . substr($c['jam'], 0, 5),
    ]),
    [['No', 1], ['Tahapan', 2], ['Perangkat', 3.5], ['Jenis', 2.5], ['Kondisi', 3], ['Status', 1.5], ['Keterangan', 4]],
    $baris, true);
