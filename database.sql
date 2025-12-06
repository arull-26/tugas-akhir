-- --------------------------------------------------------
-- MEMBUAT DATABASE & MENGGUNAKAN DATABASE
-- --------------------------------------------------------

CREATE DATABASE IF NOT EXISTS `db_absensi` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `db_absensi`;

-- --------------------------------------------------------
-- HAPUS TABEL JIKA SUDAH ADA
-- --------------------------------------------------------

DROP TABLE IF EXISTS `absensi`;
DROP TABLE IF EXISTS `users`;

-- --------------------------------------------------------
-- TABEL USERS
-- --------------------------------------------------------

CREATE TABLE `users` (
  `id` INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `nama` VARCHAR(100) NOT NULL,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'pegawai') NOT NULL DEFAULT 'pegawai',
  `foto` VARCHAR(255) DEFAULT 'default.png',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- TABEL ABSENSI
-- --------------------------------------------------------

CREATE TABLE `absensi` (
  `id` INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `tanggal` DATE NOT NULL,
  `jam_masuk` TIME DEFAULT NULL,
  `jam_pulang` TIME DEFAULT NULL,
  `foto_masuk` VARCHAR(255) DEFAULT NULL,
  `foto_pulang` VARCHAR(255) DEFAULT NULL,
  `lat_masuk` DECIMAL(10, 8) DEFAULT NULL,
  `lng_masuk` DECIMAL(11, 8) DEFAULT NULL,
  `lat_pulang` DECIMAL(10, 8) DEFAULT NULL,
  `lng_pulang` DECIMAL(11, 8) DEFAULT NULL,
  `status` ENUM('Hadir', 'Terlambat', 'Pulang', 'Izin') NOT NULL,
  `keterangan` TEXT DEFAULT NULL,
  
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- DATA AWAL USERS
-- Password = admin123 (hashed)
-- --------------------------------------------------------

INSERT INTO `users` (`id`, `nama`, `username`, `password`, `role`) VALUES
(1, 'Admin Utama', 'admin', '$2y$10$92hF7j9gVzP2rV9N3L8A.uwB/2w.Q4.d4m0gT5L9mXQ9o7F4vJ0p.', 'admin'),
(2, 'Budi Santoso', 'budi', '$2y$10$92hF7j9gVzP2rV9N3L8A.uwB/2w.Q4.d4m0gT5L9mXQ9o7F4vJ0p.', 'pegawai');
