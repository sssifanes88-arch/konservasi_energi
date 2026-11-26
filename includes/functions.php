<?php
require_once __DIR__ . '/../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function redirect($path) { header('Location: ' . BASE_URL . '/' . $path); exit; }

function flash($type = null, $msg = null) {
    if ($type !== null) { $_SESSION['flash'] = [$type, $msg]; return null; }
    if (isset($_SESSION['flash'])) { $f = $_SESSION['flash']; unset($_SESSION['flash']); return $f; }
    return null;
}

function csrf() {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field() { return '<input type="hidden" name="csrf" value="' . csrf() . '">'; }
function csrf_check() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(400);
        exit('Permintaan tidak valid.');
    }
}

function user() { return $_SESSION['user'] ?? null; }
function is_siswa() { return (user()['level'] ?? '') === 'Siswa'; }

function login_wajib(array $level = []) {
    if (empty($_SESSION['user'])) redirect('login.php');
    if ($level && !in_array($_SESSION['user']['level'], $level, true)) {
        http_response_code(403);
        exit('Anda tidak punya akses ke halaman ini.');
    }
}

// Ambil checklist; siswa hanya boleh membuka miliknya sendiri (username = NIS)
function ambil_checklist(PDO $pdo, $id) {
    $st = $pdo->prepare('SELECT c.*, s.nama, s.kelas FROM checklist c JOIN siswa s ON s.nis = c.nis WHERE c.id_checklist = ?');
    $st->execute([(int)$id]);
    $c = $st->fetch();
    if (!$c) { flash('danger', 'Checklist tidak ditemukan.'); redirect('checklist/'); }
    if (is_siswa() && (string)$c['nis'] !== (string)(user()['nis'] ?? '')) {
        http_response_code(403);
        exit('Ini bukan checklist Anda.');
    }
    return $c;
}

// ---------- Tampilan ----------

// Ikon SVG sebaris (stroke mengikuti warna teks). Dipakai di sidebar dan kartu.
function ikon($nama) {
    $p = [
        'menu'   => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'user'   => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/>',
        'home'   => '<path d="M3 11l9-8 9 8M5 10v10h14V10"/>',
        'siswa'  => '<circle cx="9" cy="8" r="3.5"/><path d="M2 20c0-3.5 3-5.5 7-5.5s7 2 7 5.5M17 5a3.5 3.5 0 010 7M22 20c0-2.5-1.5-4-4-5"/>',
        'bolt'   => '<path d="M13 2L4 14h7l-1 8 9-12h-7z"/>',
        'list'   => '<rect x="5" y="3" width="14" height="18" rx="2"/><path d="M9 8h6M9 12h6M9 16h3"/>',
        'shield' => '<path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/>',
        'key'    => '<circle cx="8" cy="15" r="4"/><path d="M11 12l9-9M16 7l3 3"/>',
        'moon'   => '<path d="M20 14A8 8 0 019.5 3.5 8 8 0 1020 14z"/>',
        'info'   => '<circle cx="12" cy="12" r="9"/><path d="M12 11v6M12 7.5v.5"/>',
        'out'    => '<path d="M9 4H5v16h4M16 8l4 4-4 4M20 12H9"/>',
        'chev'   => '<path d="M6 9l6 6 6-6"/>',
        'set'    => '<path d="M4 7h10M18 7h2M4 17h2M10 17h10"/><circle cx="16" cy="7" r="2"/><circle cx="8" cy="17" r="2"/>',
    ][$nama] ?? '';
    return '<svg class="ikon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p . '</svg>';
}

// Mencari file logo di assets/img/ dengan nama konservasi.(png|jpg|jpeg|webp|svg); urutan = prioritas.
function logo_file() {
    foreach (['png', 'jpg', 'jpeg', 'webp', 'svg'] as $x) {
        if (is_file(__DIR__ . '/../assets/img/konservasi.' . $x)) return 'konservasi.' . $x;
    }
    return null;
}

// Logo aplikasi: pakai file logo jika ada, kalau tidak pakai ikon petir.
function logo($ukuran = 36) {
    $f = logo_file();
    if ($f) {
        return '<img src="' . BASE_URL . '/assets/img/' . $f . '" alt="Logo Konservasi Energi" width="' . $ukuran . '" height="' . $ukuran . '" class="logo-img">';
    }
    return '<span class="logo-ph" style="width:' . $ukuran . 'px;height:' . $ukuran . 'px">' . ikon('bolt') . '</span>';
}

// Halaman aktif ditentukan dari alamat file yang sedang dibuka (untuk menyorot menu).
function halaman_aktif() {
    $s = $_SERVER['SCRIPT_NAME'] ?? '';
    foreach (['siswa', 'perangkat', 'checklist', 'users', 'akun'] as $k) {
        if (strpos($s, "/$k/") !== false) return $k;
    }
    return strpos($s, 'tentang') !== false ? 'tentang' : 'dashboard';
}

function menu_item($kunci, $url, $ikon, $label, $aktif) {
    $on = $kunci === $aktif ? ' on' : '';
    return '<a class="menu' . $on . '" href="' . BASE_URL . '/' . $url . '">' . ikon($ikon) . '<span>' . e($label) . '</span></a>';
}

// Tanggal berbahasa Indonesia, contoh: 15 Okt 2026
function tgl_id($tanggal) {
    $bln = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $t = strtotime($tanggal);
    return date('j', $t) . ' ' . $bln[(int)date('n', $t)] . ' ' . date('Y', $t);
}

// Panjang teks; memakai mbstring bila tersedia (tidak semua server mengaktifkannya).
function panjang($s) { return function_exists('mb_strlen') ? mb_strlen($s) : strlen($s); }

// Membuat akun Siswa (tabel users) dari data di tabel siswa. Dipakai saat siswa pertama kali masuk
// dengan username = NIS dan password = NIS. Mengembalikan baris users, atau null jika NIS tidak terdaftar.
function buat_akun_siswa(PDO $pdo, $nis) {
    $st = $pdo->prepare('SELECT nis, nama FROM siswa WHERE nis = ?');
    $st->execute([(int)$nis]);
    $sw = $st->fetch();
    if (!$sw || (string)$sw['nis'] !== (string)$nis) return null;   // tolak mis. "012" (angka nol di depan)
    try {
        $pdo->prepare('INSERT INTO users (username, password, nama, level) VALUES (?,?,?,?)')
            ->execute([(string)$nis, password_hash((string)$nis, PASSWORD_DEFAULT), $sw['nama'], 'Siswa']);
    } catch (PDOException $e) {
        return null;                                                  // mis. username sudah dipakai
    }
    $st = $pdo->prepare('SELECT * FROM users WHERE username = ?');
    $st->execute([(string)$nis]);
    return $st->fetch() ?: null;
}

// Tombol dropdown "Ekspor" (PDF / Excel). $url = halaman ekspor, $param = parameter tambahan (mis. kata pencarian).
function tombol_ekspor($url = 'export.php', array $param = []) {
    $h = '<div class="dropdown"><button class="btn btn-outline-success dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">Ekspor</button>'
       . '<ul class="dropdown-menu dropdown-menu-end">';
    foreach (['pdf' => 'PDF (.pdf)', 'excel' => 'Excel (.xlsx)'] as $f => $label) {
        $h .= '<li><a class="dropdown-item" href="' . e($url . '?' . http_build_query($param + ['format' => $f])) . '">' . e($label) . '</a></li>';
    }
    return $h . '</ul></div>';
}
