-- Database Undangan Pernikahan Welling & Sheila
-- Struktur Tabel & Akun Admin Bawaan (admin / admin123)

CREATE TABLE IF NOT EXISTS `tb_admin` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `username`    VARCHAR(50)  NOT NULL UNIQUE,
  `password`    VARCHAR(255) NOT NULL,
  `dibuat_pada` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Akun Default: Username: admin | Password: admin123
INSERT IGNORE INTO `tb_admin` (`id`, `username`, `password`) VALUES
(1, 'admin', '$2y$10$aiWczWzv/AKicCRNJLvNaOEkHqT896QvxhMeW/S0rcDCVEtLIHx.O');

CREATE TABLE IF NOT EXISTS `tb_ucapan` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nama`        VARCHAR(100) NOT NULL,
  `rsvp`        ENUM('Hadir','Tidak Hadir','Masih Ragu') NOT NULL,
  `jumlah_tamu` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `pesan`       TEXT NOT NULL,
  `tampil`      TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_rsvp` (`rsvp`),
  KEY `idx_tampil_waktu` (`tampil`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
