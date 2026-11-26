<?php
// Sesuaikan dengan lingkungan Anda
date_default_timezone_set('Asia/Jakarta');   // WIB; ganti Asia/Makassar (WITA) atau Asia/Jayapura (WIT) jika perlu

// BASE_URL dideteksi otomatis dari letak folder proyek di dalam htdocs,
// jadi folder boleh diberi nama apa saja (mis. /konservasi_energi_anes/konservasi_energi).
$__root = rtrim(str_replace('\\', '/', (string)realpath($_SERVER['DOCUMENT_ROOT'] ?? '')), '/');
$__dir  = str_replace('\\', '/', (string)realpath(__DIR__ . '/..'));
define('BASE_URL', ($__root !== '' && stripos($__dir, $__root) === 0) ? substr($__dir, strlen($__root)) : '/konservasi_energi');

// true  = siswa yang ada di tabel siswa boleh masuk dengan username = NIS dan password awal = NIS
//         (akun di tabel users dibuat otomatis saat login pertama).
// false = hanya akun yang sudah dibuat manual di menu Pengguna yang bisa masuk.
define('LOGIN_NIS_OTOMATIS', true);

$host = 'localhost';
$db   = 'konservasi_energi';
$user = 'root';
$pass = '';

$opsi = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, $opsi);
    } catch (PDOException $e) {
        // 1049 = database belum ada -> buat otomatis
        if ((int)$e->getCode() !== 1049 && strpos($e->getMessage(), '1049') === false) throw $e;
        $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, $opsi);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
        $pdo->exec("USE `$db`");
    }

    // Database ada tetapi tabel belum ada -> impor skema + data contoh dari konservasi_energi.sql
    if (!$pdo->query("SHOW TABLES LIKE 'siswa'")->fetchColumn()) {
        $sql = file_get_contents(__DIR__ . '/../konservasi_energi.sql');
        $sql = preg_replace('/^\s*--.*$/m', '', $sql);            // buang baris komentar
        foreach (explode(';', $sql) as $q) {
            $q = trim($q);
            if ($q !== '') $pdo->exec($q);
        }
    }
} catch (PDOException $e) {
    exit('Koneksi database gagal. Pastikan MySQL di XAMPP sudah menyala, lalu periksa config/database.php');
}
