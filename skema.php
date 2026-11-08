<?php
/**
 * Migrasi skema otomatis (aman dijalankan berulang).
 * - Membuat tb_tamu dan tb_pengaturan bila belum ada
 * - Menambah kolom tamu_id & diubah_at pada tb_ucapan bila belum ada
 * - Mengisi tb_tamu dari data_tamu_awal.php bila masih kosong
 *
 * Hasilnya dicatat di sesi agar tidak diulang di setiap permintaan.
 */
const VERSI_SKEMA = 2;

function pastikan_skema(mysqli $db): void
{
    if (($_SESSION['versi_skema'] ?? 0) === VERSI_SKEMA) {
        return;
    }

    try {
        $db->query("CREATE TABLE IF NOT EXISTS `tb_tamu` (
            `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `nama`        VARCHAR(100) NOT NULL,
            `dibuat_pada` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY `idx_nama` (`nama`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->query("CREATE TABLE IF NOT EXISTS `tb_pengaturan` (
            `kunci` VARCHAR(50) NOT NULL PRIMARY KEY,
            `nilai` MEDIUMTEXT  NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $kolomTamu = $db->query("SHOW COLUMNS FROM `tb_ucapan` LIKE 'tamu_id'")->num_rows > 0;
        if (!$kolomTamu) {
            $db->query("ALTER TABLE `tb_ucapan` ADD COLUMN `tamu_id` INT UNSIGNED NULL DEFAULT NULL AFTER `id`");
            $db->query("ALTER TABLE `tb_ucapan` ADD UNIQUE KEY `uniq_tamu` (`tamu_id`)");
        }

        $kolomUbah = $db->query("SHOW COLUMNS FROM `tb_ucapan` LIKE 'diubah_at'")->num_rows > 0;
        if (!$kolomUbah) {
            $db->query("ALTER TABLE `tb_ucapan` ADD COLUMN `diubah_at` DATETIME NULL DEFAULT NULL");
        }

        $jumlah = (int) $db->query('SELECT COUNT(*) AS n FROM `tb_tamu`')->fetch_assoc()['n'];
        if ($jumlah === 0) {
            $berkas = __DIR__ . '/data_tamu_awal.php';
            $daftar = is_file($berkas) ? include $berkas : [];
            if (is_array($daftar) && $daftar) {
                $stmt = $db->prepare('INSERT INTO `tb_tamu` (`id`, `nama`) VALUES (?, ?)');
                foreach (array_values($daftar) as $i => $nama) {
                    $id = $i + 1;
                    $nama = trim((string) $nama);
                    if ($nama === '') {
                        continue;
                    }
                    $stmt->bind_param('is', $id, $nama);
                    $stmt->execute();
                }
            }
        }

        $_SESSION['versi_skema'] = VERSI_SKEMA;
    } catch (mysqli_sql_exception $e) {
        error_log('Migrasi skema gagal: ' . $e->getMessage());
    }
}
