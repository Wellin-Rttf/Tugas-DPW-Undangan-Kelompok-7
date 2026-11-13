CREATE TABLE IF NOT EXISTS `tb_admin` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `username`    VARCHAR(50)  NOT NULL UNIQUE,
  `password`    VARCHAR(255) NOT NULL,
  `dibuat_pada` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT IGNORE INTO `tb_admin` (`id`, `username`, `password`) VALUES
(1, 'admin', '$2y$10$aiWczWzv/AKicCRNJLvNaOEkHqT896QvxhMeW/S0rcDCVEtLIHx.O');
CREATE TABLE IF NOT EXISTS `tb_tamu` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nama`        VARCHAR(100) NOT NULL,
  `dibuat_pada` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_nama` (`nama`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `tb_ucapan` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tamu_id`     INT UNSIGNED NULL DEFAULT NULL,
  `nama`        VARCHAR(100) NOT NULL,
  `rsvp`        ENUM('Hadir','Tidak Hadir','Masih Ragu') NOT NULL,
  `jumlah_tamu` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `pesan`       TEXT NOT NULL,
  `tampil`      TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `diubah_at`   DATETIME     NULL DEFAULT NULL,
  UNIQUE KEY `uniq_tamu` (`tamu_id`),
  KEY `idx_rsvp` (`rsvp`),
  KEY `idx_tampil_waktu` (`tampil`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `tb_pengaturan` (
  `kunci` VARCHAR(50) NOT NULL PRIMARY KEY,
  `nilai` MEDIUMTEXT  NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `tb_tamu` (`id`, `nama`) VALUES
(1, 'Riaa'),
(2, 'Uli'),
(3, 'Deri'),
(4, 'Taufiq Lubis'),
(5, 'Josua Simatupang'),
(6, 'Ilham'),
(7, 'Alif Syahbani'),
(8, 'Denada Tessa'),
(9, 'Alwiy'),
(10, 'Doni Saputra'),
(11, 'Lestari Wulandari'),
(12, 'Fajar Nugroho'),
(13, 'Anisa Fitriani'),
(14, 'Bagas Prakoso'),
(15, 'Nur Aini'),
(16, 'Yusuf Hakim'),
(17, 'Citra Kirana'),
(18, 'Andi Wijaya'),
(19, 'Rina Marlina'),
(20, 'Eko Prasetyo'),
(21, 'Fitri Handayani'),
(22, 'Galih Permana'),
(23, 'Hana Safitri'),
(24, 'Irfan Maulidi'),
(25, 'Jihan Aulia'),
(26, 'Kevin Sihombing'),
(27, 'Lia Purnama'),
(28, 'Mario Siregar'),
(29, 'Nadia Putri'),
(30, 'Oki Setiawan'),
(31, 'Pandu Harahap'),
(32, 'Qori Amalia'),
(33, 'Reza Fahlevi'),
(34, 'Salsabila Nasution'),
(35, 'Taufik Hidayat'),
(36, 'Ulfa Rahmi'),
(37, 'Vino Bastian'),
(38, 'Winda Sari'),
(39, 'Xaverius Manurung'),
(40, 'Yuni Astuti'),
(41, 'Zaki Firmansyah'),
(42, 'Ayu Ningsih'),
(43, 'Bayu Anggara'),
(44, 'Cindy Marbun'),
(45, 'Dimas Aditya'),
(46, 'Elsa Pangaribuan'),
(47, 'Ferdi Simanjuntak'),
(48, 'Gita Permatasari'),
(49, 'Hasan Basri'),
(50, 'Intan Maharani');