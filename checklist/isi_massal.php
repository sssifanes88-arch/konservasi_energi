<?php
require_once __DIR__ . '/../includes/functions.php';
login_wajib();

$post = $_SERVER['REQUEST_METHOD'] === 'POST';
$src  = $post ? $_POST : $_GET;

$c  = ambil_checklist($pdo, $src['id_checklist'] ?? 0);   // validasi kepemilikan
$id = (int)$c['id_checklist'];

$daftarTahapan = ['SEBELUM', 'SELAMA', 'SESUDAH', '5R'];
$defKondisi    = ['SEBELUM' => 'Siap', 'SELAMA' => 'Digunakan', 'SESUDAH' => 'Mati', '5R' => 'Rapi dan bersih'];
$tahapan = in_array($src['tahapan'] ?? '', $daftarTahapan, true) ? $src['tahapan'] : 'SESUDAH';
$q = trim($src['q'] ?? '');

$st = $pdo->prepare('SELECT * FROM perangkat
    WHERE nama_perangkat LIKE :a OR keterangan LIKE :b OR jenis LIKE :c
    ORDER BY nama_perangkat');
$st->execute([':a' => "%$q%", ':b' => "%$q%", ':c' => "%$q%"]);
$perangkat = $st->fetchAll();
$validId = array_map('intval', array_column($perangkat, 'id_perangkat'));

// Detail yang sudah ada untuk checklist + tahapan ini (akan diperbarui, bukan digandakan)
$ex = $pdo->prepare('SELECT * FROM detail_checklist WHERE id_checklist = ? AND tahapan = ?');
$ex->execute([$id, $tahapan]);
$exist = [];
foreach ($ex->fetchAll() as $r) $exist[(int)$r['id_perangkat']] = $r;

$err = [];
if ($post) {
    csrf_check();
    $cek = $_POST['cek'] ?? [];
    $stt = $_POST['status'] ?? [];
    $kon = $_POST['kondisi'] ?? [];
    $ket = $_POST['ket'] ?? [];

    $simpan = [];
    foreach ($perangkat as $p) {
        $pid = (int)$p['id_perangkat'];
        if (!isset($cek[$pid])) continue;
        $s = $stt[$pid] ?? '';
        $k = trim($kon[$pid] ?? '');
        $t = trim($ket[$pid] ?? '');
        if (!in_array($s, ['Ya', 'Tidak'], true)) { $err[] = $p['nama_perangkat'] . ': status tidak valid.'; continue; }
        if ($k === '' || panjang($k) > 100)     { $err[] = $p['nama_perangkat'] . ': kondisi wajib diisi (maksimal 100 karakter).'; continue; }
        if ($s === 'Tidak' && $t === '')          { $err[] = $p['nama_perangkat'] . ': beri keterangan karena statusnya Tidak.'; continue; }
        $simpan[$pid] = [$s, $k, $t];
    }
    if (!$simpan && !$err) $err[] = 'Pilih minimal satu perangkat untuk disimpan.';

    if (!$err) {
        try {
            $pdo->beginTransaction();
            $upd = $pdo->prepare('UPDATE detail_checklist SET kondisi=?, status=?, keterangan=? WHERE id_detail=? AND id_checklist=?');
            $ins = $pdo->prepare('INSERT INTO detail_checklist (id_checklist, id_perangkat, tahapan, kondisi, status, keterangan) VALUES (?,?,?,?,?,?)');
            foreach ($simpan as $pid => [$s, $k, $t]) {
                if (isset($exist[$pid])) $upd->execute([$k, $s, $t, $exist[$pid]['id_detail'], $id]);
                else                     $ins->execute([$id, $pid, $tahapan, $k, $s, $t]);
            }
            $pdo->commit();
            flash('success', count($simpan) . ' detail tahap ' . $tahapan . ' disimpan.');
            redirect('checklist/detail.php?id=' . $id);
        } catch (PDOException $ex2) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $err[] = 'Data gagal disimpan.';
        }
    }
}

$judul = 'Isi Massal';
require '../includes/header.php';
?>
<a href="detail.php?id=<?= $id ?>" class="text-decoration-none">&larr; Kembali ke detail checklist</a>
<h3 class="mt-3 mb-0">Isi massal: <?= e($c['nama']) ?></h3>
<p class="text-muted"><?= e($c['tanggal']) ?> pukul <?= e(substr($c['jam'], 0, 5)) ?>. Semua unit terisi "Ya" dari awal. Ubah hanya yang bermasalah.</p>

<?php foreach ($err as $m): ?><div class="alert alert-danger py-2"><?= e($m) ?></div><?php endforeach; ?>

<form method="get" class="row g-2 mb-3">
  <input type="hidden" name="id_checklist" value="<?= $id ?>">
  <div class="col-12 col-sm-4"><label class="form-label mb-1">Tahapan</label>
    <select name="tahapan" class="form-select" onchange="this.form.submit()">
      <?php foreach ($daftarTahapan as $t): ?><option <?= $tahapan === $t ? 'selected' : '' ?>><?= $t ?></option><?php endforeach; ?>
    </select></div>
  <div class="col-12 col-sm-5"><label class="form-label mb-1">Filter perangkat</label>
    <input name="q" value="<?= e($q) ?>" class="form-control" placeholder="contoh: Lab 1"></div>
  <div class="col-12 col-sm-3 d-flex align-items-end"><button class="btn btn-outline-success w-100">Terapkan</button></div>
</form>

<form method="post">
  <?= csrf_field() ?>
  <input type="hidden" name="id_checklist" value="<?= $id ?>">
  <input type="hidden" name="tahapan" value="<?= e($tahapan) ?>">
  <input type="hidden" name="q" value="<?= e($q) ?>">
  <div class="card"><div class="table-responsive"><table class="table align-middle mb-0">
    <thead><tr>
      <th style="width:40px"><input class="form-check-input" type="checkbox" id="semua" checked title="Pilih semua"></th>
      <th>Perangkat</th><th style="width:150px">Status</th><th>Kondisi</th><th>Keterangan</th></tr></thead>
    <tbody>
    <?php foreach ($perangkat as $p):
        $pid = (int)$p['id_perangkat'];
        if ($post) {
            $ck = isset($cek[$pid]); $s = $stt[$pid] ?? 'Ya'; $k = $kon[$pid] ?? ''; $t = $ket[$pid] ?? '';
        } elseif (isset($exist[$pid])) {
            $ck = true; $s = $exist[$pid]['status']; $k = $exist[$pid]['kondisi']; $t = $exist[$pid]['keterangan'];
        } else {
            $ck = true; $s = 'Ya'; $k = $defKondisi[$tahapan]; $t = '';
        } ?>
      <tr data-row>
        <td><input class="form-check-input cek" type="checkbox" name="cek[<?= $pid ?>]" value="1" <?= $ck ? 'checked' : '' ?>></td>
        <td><?= e($p['nama_perangkat']) ?><div class="text-muted small"><?= e($p['jenis']) ?><?= $p['keterangan'] ? ' &middot; ' . e($p['keterangan']) : '' ?></div></td>
        <td>
          <div class="form-check form-check-inline me-2"><input class="form-check-input st" type="radio" name="status[<?= $pid ?>]" id="y<?= $pid ?>" value="Ya" <?= $s === 'Ya' ? 'checked' : '' ?>><label class="form-check-label" for="y<?= $pid ?>">Ya</label></div>
          <div class="form-check form-check-inline me-0"><input class="form-check-input st" type="radio" name="status[<?= $pid ?>]" id="t<?= $pid ?>" value="Tidak" <?= $s === 'Tidak' ? 'checked' : '' ?>><label class="form-check-label" for="t<?= $pid ?>">Tidak</label></div>
        </td>
        <td><input name="kondisi[<?= $pid ?>]" class="form-control form-control-sm" maxlength="100" value="<?= e($k) ?>"></td>
        <td><input name="ket[<?= $pid ?>]" class="form-control form-control-sm ket" value="<?= e($t) ?>" placeholder="wajib jika Tidak"></td>
      </tr>
    <?php endforeach; if (!$perangkat): ?>
      <tr><td colspan="5" class="text-center text-muted py-4">Tidak ada perangkat<?= $q !== '' ? ' untuk filter ini' : '. Tambahkan dulu di menu Perangkat' ?>.</td></tr>
    <?php endif; ?>
    </tbody></table></div></div>
  <?php if ($perangkat): ?>
  <div class="mt-3"><button class="btn btn-success">Simpan semua</button>
    <a class="btn btn-link" href="detail.php?id=<?= $id ?>">Batal</a></div>
  <?php endif; ?>
</form>
<script>
  document.getElementById('semua')?.addEventListener('change', e =>
    document.querySelectorAll('.cek').forEach(c => c.checked = e.target.checked));
  document.querySelectorAll('[data-row]').forEach(tr => {
    const ket = tr.querySelector('.ket');
    const sync = () => {
      const tidak = tr.querySelector('.st[value="Tidak"]').checked;
      tr.classList.toggle('table-warning', tidak);
      ket.required = tidak;
    };
    tr.querySelectorAll('.st').forEach(r => r.addEventListener('change', () => { sync(); if (r.value === 'Tidak') ket.focus(); }));
    sync();
  });
</script>
<?php require '../includes/footer.php'; ?>
