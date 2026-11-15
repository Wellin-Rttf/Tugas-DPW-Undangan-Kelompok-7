<?php
const VERSI_SKEMA = 3;

function kolom_ada(mysqli $db, string $tabel, string $kolom): bool
{
    return $db->query("SHOW COLUMNS FROM `$tabel` LIKE '$kolom'")->num_rows > 0;
}

function tabel_kosong(mysqli $db, string $tabel): bool
{
    return (int) $db->query("SELECT COUNT(*) FROM `$tabel`")->fetch_row()[0] === 0;
}

function pastikan_skema(mysqli $db): void
{
    if (($_SESSION['versi_skema'] ?? 0) === VERSI_SKEMA) {
        return;
    }

    $opsi = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
    $tabel = [
        "CREATE TABLE IF NOT EXISTS `tb_admin` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `username` VARCHAR(50) NOT NULL UNIQUE,
            `password` VARCHAR(255) NOT NULL,
            `dibuat_pada` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) $opsi",
        "CREATE TABLE IF NOT EXISTS `tb_tamu` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `nama` VARCHAR(100) NOT NULL,
            `dibuat_pada` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY `idx_nama` (`nama`)
        ) $opsi",
        "CREATE TABLE IF NOT EXISTS `tb_ucapan` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `tamu_id` INT UNSIGNED NULL DEFAULT NULL,
            `nama` VARCHAR(100) NOT NULL,
            `rsvp` ENUM('Hadir','Tidak Hadir','Masih Ragu') NOT NULL,
            `jumlah_tamu` TINYINT UNSIGNED NOT NULL DEFAULT 1,
            `pesan` TEXT NOT NULL,
            `tampil` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `diubah_at` DATETIME NULL DEFAULT NULL,
            UNIQUE KEY `uniq_tamu` (`tamu_id`),
            KEY `idx_rsvp` (`rsvp`),
            KEY `idx_tampil_waktu` (`tampil`, `created_at`)
        ) $opsi",
        "CREATE TABLE IF NOT EXISTS `tb_log` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `jenis` VARCHAR(20) NOT NULL,
            `teks` VARCHAR(255) NOT NULL,
            `dibuat_pada` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY `idx_waktu` (`dibuat_pada`)
        ) $opsi",
        "CREATE TABLE IF NOT EXISTS `tb_pengaturan` (
            `kunci` VARCHAR(50) NOT NULL PRIMARY KEY,
            `nilai` MEDIUMTEXT NOT NULL
        ) $opsi",
    ];

    try {
        foreach ($tabel as $sql) {
            $db->query($sql);
        }

        if (!kolom_ada($db, 'tb_ucapan', 'tamu_id')) {
            $db->query('ALTER TABLE `tb_ucapan` ADD COLUMN `tamu_id` INT UNSIGNED NULL DEFAULT NULL AFTER `id`');
            $db->query('ALTER TABLE `tb_ucapan` ADD UNIQUE KEY `uniq_tamu` (`tamu_id`)');
        }
        if (!kolom_ada($db, 'tb_ucapan', 'diubah_at')) {
            $db->query('ALTER TABLE `tb_ucapan` ADD COLUMN `diubah_at` DATETIME NULL DEFAULT NULL');
        }

        if (tabel_kosong($db, 'tb_admin')) {
            $stmt = $db->prepare("INSERT INTO `tb_admin` (`username`, `password`) VALUES ('admin', ?)");
            $hash = password_hash('admin123', PASSWORD_DEFAULT);
            $stmt->bind_param('s', $hash);
            $stmt->execute();
        }

        if (tabel_kosong($db, 'tb_tamu')) {
            $berkas = __DIR__ . '/data_tamu_awal.php';
            $daftar = is_file($berkas) ? include $berkas : [];
            $stmt = $db->prepare('INSERT INTO `tb_tamu` (`id`, `nama`) VALUES (?, ?)');
            foreach (array_values((array) $daftar) as $i => $nama) {
                $id = $i + 1;
                $nama = trim((string) $nama);
                if ($nama !== '') {
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
