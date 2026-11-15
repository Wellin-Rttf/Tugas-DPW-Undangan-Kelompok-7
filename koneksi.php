<?php
require_once __DIR__ . '/config.php';
date_default_timezone_set('Asia/Jakarta');
$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
      || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $koneksi = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $koneksi->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    error_log('Koneksi database gagal: ' . $e->getMessage());
    $koneksi = null;
}
require_once __DIR__ . '/skema.php';
require_once __DIR__ . '/konten.php';
if ($koneksi) {
    pastikan_skema($koneksi);
}
const PILIHAN_JUMLAH = ['1 Orang' => 1, '2 Orang' => 2, '3+ Orang' => 3];
const PILIHAN_RSVP   = ['Hadir', 'Tidak Hadir', 'Masih Ragu'];
function ambil_post_string($nama, $default = '') {
    $nilai = $_POST[$nama] ?? $default;
    return is_string($nilai) ? $nilai : $default;
}
function h($teks) {
    return htmlspecialchars((string) $teks, ENT_QUOTES, 'UTF-8');
}
function label_jumlah($angka) {
    return (int) $angka >= 3 ? '3+ Orang' : (int) $angka . ' Orang';
}
function tanggal_indo($waktu) {
    $bulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $t = strtotime($waktu);
    return date('j', $t) . ' ' . $bulan[(int) date('n', $t) - 1] . ' ' . date('Y', $t);
}
function token_csrf() {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}
function cek_csrf($token) {
    return !empty($_SESSION['csrf']) && is_string($token) && hash_equals($_SESSION['csrf'], $token);
}
function sudah_login() {
    return !empty($_SESSION['admin_id']);
}
function jalankan(mysqli $db, string $sql, string $tipe = '', array $param = []): mysqli_stmt {
    $stmt = $db->prepare($sql);
    if ($param) {
        $stmt->bind_param($tipe, ...$param);
    }
    $stmt->execute();
    return $stmt;
}
function catat_log(?mysqli $db, string $jenis, string $teks): void {
    if (!$db) {
        return;
    }
    try {
        jalankan($db, 'INSERT INTO tb_log (jenis, teks) VALUES (?, ?)', 'ss', [$jenis, mb_substr($teks, 0, 250)]);
        $db->query('DELETE FROM tb_log WHERE id <= ' . ((int) $db->insert_id - 200));
    } catch (mysqli_sql_exception $e) {
        error_log('Gagal mencatat log: ' . $e->getMessage());
    }
}
function bersihkan_nama_tamu($nilai, int $maks = 100): string {
    $nilai = is_string($nilai) ? strip_tags($nilai) : '';
    $nilai = preg_replace('/[\p{C}\s]+/u', ' ', $nilai) ?? '';
    return mb_substr(trim($nilai), 0, $maks);
}
function url_undangan(): string {
    global $https;
    return ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
         . rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/') . '/index.php';
}
