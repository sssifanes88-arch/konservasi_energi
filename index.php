<?php
require_once __DIR__ . '/includes/functions.php';
login_wajib();
$judul = 'Dashboard';

$level  = user()['level'];
$siswa  = is_siswa();
$nis    = $siswa ? (int)(user()['nis'] ?? 0) : 0;
$awalBulan = date('Y-m-01');
$mulai7    = date('Y-m-d', strtotime('-6 days'));
$mulai30   = date('Y-m-d', strtotime('-29 days'));

// Siswa hanya melihat data miliknya: tambahkan syarat nis ke setiap query.
$fc = $siswa ? ' AND c.nis = :nis' : '';
$ambil = function ($sql, array $p = []) use ($pdo, $siswa, $nis) {
    if ($siswa && strpos($sql, ':nis') !== false) $p[':nis'] = $nis;
    $st = $pdo->prepare($sql);
    $st->execute($p);
    return $st;
};

$jmlChecklist = (int)$ambil("SELECT COUNT(*) FROM checklist c WHERE c.tanggal >= :m $fc", [':m' => $awalBulan])->fetchColumn();
$r = $ambil("SELECT COUNT(*) total, SUM(d.status = 'Ya') ya FROM detail_checklist d
             JOIN checklist c ON c.id_checklist = d.id_checklist WHERE c.tanggal >= :m $fc", [':m' => $awalBulan])->fetch();
$persen = $r['total'] ? round($r['ya'] / $r['total'] * 100) : 0;

$terbaru = $ambil("SELECT c.id_checklist, c.tanggal, c.jam, s.nama, s.kelas,
        COUNT(d.id_detail) jml, SUM(d.status = 'Ya') ya
    FROM checklist c JOIN siswa s ON s.nis = c.nis
    LEFT JOIN detail_checklist d ON d.id_checklist = c.id_checklist
    WHERE 1 = 1 $fc GROUP BY c.id_checklist ORDER BY c.tanggal DESC, c.jam DESC LIMIT 5")->fetchAll();

if (!$siswa) {
    $jmlSiswa     = $pdo->query('SELECT COUNT(*) FROM siswa')->fetchColumn();
    $jmlPerangkat = $pdo->query('SELECT COUNT(*) FROM perangkat')->fetchColumn();

    // Grafik: persentase "Ya" per hari selama 7 hari terakhir
    $per = [];
    foreach ($ambil("SELECT c.tanggal, ROUND(100 * SUM(d.status = 'Ya') / COUNT(*)) p
        FROM checklist c JOIN detail_checklist d ON d.id_checklist = c.id_checklist
        WHERE c.tanggal >= :m GROUP BY c.tanggal", [':m' => $mulai7])->fetchAll() as $x) $per[$x['tanggal']] = (int)$x['p'];
    $hari = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
    $grafik = [];
    for ($i = 6; $i >= 0; $i--) {
        $t = date('Y-m-d', strtotime("-$i days"));
        $grafik[] = [$hari[(int)date('w', strtotime($t))], $per[$t] ?? 0];
    }

    // Unit yang paling sering berstatus "Tidak" dalam 30 hari terakhir
    $perhatian = $ambil("SELECT p.nama_perangkat, COUNT(*) n FROM detail_checklist d
        JOIN perangkat p ON p.id_perangkat = d.id_perangkat
        JOIN checklist c ON c.id_checklist = d.id_checklist
        WHERE d.status = 'Tidak' AND c.tanggal >= :m GROUP BY p.id_perangkat ORDER BY n DESC LIMIT 5", [':m' => $mulai30])->fetchAll();

    if ($level === 'Guru') {
        $kelas = $ambil("SELECT s.kelas, ROUND(100 * SUM(d.status = 'Ya') / COUNT(*)) p FROM detail_checklist d
            JOIN checklist c ON c.id_checklist = d.id_checklist JOIN siswa s ON s.nis = c.nis
            WHERE c.tanggal >= :m GROUP BY s.kelas ORDER BY s.kelas", [':m' => $mulai30])->fetchAll();
    } else {
        $akun = $pdo->query('SELECT level, COUNT(*) n FROM users GROUP BY level')->fetchAll(PDO::FETCH_KEY_PAIR);
    }
}

function kartu($ikon, $label, $nilai, $amber = false) {
    return '<div class="col-6 col-xl-3"><div class="card h-100"><div class="card-body"><div class="stat-ic ' . ($amber ? 'a' : '') . '">' . ikon($ikon) . '</div>'
         . '<div class="stat-k">' . e($label) . '</div><div class="stat-v">' . e($nilai) . '</div></div></div></div>';
}

require __DIR__ . '/includes/header.php';
?>
<?php if ($siswa): ?>
  <div><h3 class="mb-0">Halo, <?= e(user()['nama']) ?></h3></div>
  <div class="card hero"><div class="card-body p-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div><div class="fs-5 fw-bold">Checklist hari ini</div>
      <div class="muted mt-1">Cek perangkat sebelum, selama, dan sesudah dipakai, lalu catat kondisinya.</div></div>
    <a class="btn btn-amber" href="<?= BASE_URL ?>/checklist/form.php">Buat checklist</a></div></div>
  <div class="row g-3" style="max-width:560px">
    <?= kartu('list', 'Checklist bulan ini', $jmlChecklist) ?>
    <?= kartu('bolt', 'Item dijalankan (Ya)', $persen . '%', true) ?>
  </div>
<?php else: ?>
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
    <div><h3 class="mb-0">Dashboard <?= e($level) ?></h3>
      <div class="muted"><?= $level === 'Admin' ? 'Akses penuh ke semua data dan pengguna' : 'Akses terbatas: tanpa kelola pengguna dan tanpa hapus data' ?></div></div>
    <a class="btn btn-success" href="<?= BASE_URL ?>/checklist/form.php">Buat checklist</a>
  </div>
  <div class="row g-3">
    <?= kartu('list', 'Checklist bulan ini', $jmlChecklist) ?>
    <?= kartu('bolt', 'Item dijalankan (Ya)', $persen . '%', true) ?>
    <?= kartu('siswa', 'Siswa', $jmlSiswa) ?>
    <?= kartu('bolt', 'Perangkat', $jmlPerangkat, true) ?>
  </div>
  <div class="row g-3">
    <div class="col-lg-8"><div class="card h-100"><div class="card-body">
      <h6 class="mb-3">Kepatuhan 7 hari terakhir</h6>
      <div class="grafik"><?php foreach ($grafik as [$h, $p]): ?>
        <div title="<?= $p ?>%"><i style="height:<?= $p ?>%"></i><?= $h ?></div><?php endforeach; ?></div>
    </div></div></div>
    <div class="col-lg-4 d-flex flex-column gap-3">
      <div class="card"><div class="card-body"><h6 class="mb-3">Unit perlu perhatian</h6>
        <?php foreach ($perhatian as $x): ?>
          <div class="py-2 border-top"><?= e($x['nama_perangkat']) ?><div class="muted small">Status Tidak <?= (int)$x['n'] ?> kali dalam 30 hari</div></div>
        <?php endforeach; if (!$perhatian): ?><div class="muted">Belum ada unit bermasalah.</div><?php endif; ?>
      </div></div>
      <?php if ($level === 'Admin'): ?>
      <div class="card"><div class="card-body"><h6 class="mb-2">Pengguna</h6>
        <div class="muted mb-3">Admin <?= (int)($akun['Admin'] ?? 0) ?> &middot; Guru <?= (int)($akun['Guru'] ?? 0) ?> &middot; Siswa <?= (int)($akun['Siswa'] ?? 0) ?></div>
        <a class="btn btn-outline-success btn-sm" href="<?= BASE_URL ?>/users/">Kelola pengguna</a></div></div>
      <?php else: ?>
      <div class="card"><div class="card-body"><h6 class="mb-2">Kepatuhan per kelas (30 hari)</h6>
        <?php foreach ($kelas as $k): ?>
          <div class="py-2"><?= e($k['kelas']) ?> <span class="muted">&middot; <?= (int)$k['p'] ?>%</span><div class="bar-bg"><i style="width:<?= (int)$k['p'] ?>%"></i></div></div>
        <?php endforeach; if (!$kelas): ?><div class="muted">Belum ada data.</div><?php endif; ?>
      </div></div>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>

<div class="card"><div class="card-body pb-2"><h6 class="mb-3"><?= $siswa ? 'Riwayat checklist saya' : 'Checklist terbaru' ?></h6></div>
  <div class="table-responsive"><table class="table align-middle">
    <thead><tr><th>Tanggal</th><th>Jam</th><?php if (!$siswa): ?><th>Siswa</th><th>Kelas</th><?php endif; ?><th>Ya/Total</th><th></th></tr></thead><tbody>
    <?php foreach ($terbaru as $x): ?>
      <tr><td><?= e(tgl_id($x['tanggal'])) ?></td><td><?= e(substr($x['jam'], 0, 5)) ?></td>
        <?php if (!$siswa): ?><td><?= e($x['nama']) ?></td><td><?= e($x['kelas']) ?></td><?php endif; ?>
        <td><?= (int)$x['ya'] ?>/<?= (int)$x['jml'] ?></td>
        <td class="text-end"><a class="btn btn-sm btn-outline-success" href="<?= BASE_URL ?>/checklist/detail.php?id=<?= $x['id_checklist'] ?>">Buka</a></td></tr>
    <?php endforeach; if (!$terbaru): ?>
      <tr><td colspan="6" class="text-center muted py-4">Belum ada checklist. Klik "Buat checklist" untuk memulai.</td></tr>
    <?php endif; ?>
    </tbody></table></div></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
