-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               8.0.30 - MySQL Community Server - GPL
-- Server OS:                    Win64
-- HeidiSQL Version:             12.1.0.6537
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for perpustakaan1
CREATE DATABASE IF NOT EXISTS `perpustakaan1` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `perpustakaan1`;

-- Dumping structure for table perpustakaan1.activity_logs
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table perpustakaan1.activity_logs: ~0 rows (approximately)
DELETE FROM `activity_logs`;

-- Dumping structure for table perpustakaan1.buku
CREATE TABLE IF NOT EXISTS `buku` (
  `id` int NOT NULL AUTO_INCREMENT,
  `judul` varchar(255) NOT NULL,
  `penulis` varchar(100) NOT NULL,
  `tahun_terbit` year DEFAULT NULL,
  `kategori_id` int DEFAULT NULL,
  `isbn` varchar(20) DEFAULT NULL,
  `stok` int DEFAULT '0',
  `deskripsi` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `kategori_id` (`kategori_id`),
  CONSTRAINT `buku_ibfk_1` FOREIGN KEY (`kategori_id`) REFERENCES `kategori` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table perpustakaan1.buku: ~4 rows (approximately)
DELETE FROM `buku`;
INSERT INTO `buku` (`id`, `judul`, `penulis`, `tahun_terbit`, `kategori_id`, `isbn`, `stok`, `deskripsi`, `created_at`, `updated_at`) VALUES
	(5, 'Ayam Kate di Ujung Barat', 'Tere Lie', '2025', 1, '701230024', 2, 'bagus bukunya', '2025-10-06 12:47:47', '2025-11-25 03:03:22'),
	(6, 'sejarah', 'faizal', '2023', 2, '13', 15, 'menceritakan tentang sejarah indonesia', '2025-10-07 06:18:46', '2025-11-17 06:21:49'),
	(8, 'dermaga', 'shela', '2024', 1, '1', 10, 'cerita anak rantau', '2025-11-21 04:20:52', '2025-11-24 05:14:21'),
	(9, 'Laut Bercerita', 'laila s', '2025', 2, '4521', 6, 'cerita tentang mas laut dan teman-temannya pada tragedi 1998', '2025-11-24 05:16:12', '2025-11-24 05:28:36');

-- Dumping structure for table perpustakaan1.kategori
CREATE TABLE IF NOT EXISTS `kategori` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama_kategori` varchar(50) NOT NULL,
  `deskripsi` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table perpustakaan1.kategori: ~2 rows (approximately)
DELETE FROM `kategori`;
INSERT INTO `kategori` (`id`, `nama_kategori`, `deskripsi`, `created_at`) VALUES
	(1, 'fiksi', 'sesuatu yang tidak nyata', '2025-10-06 09:06:06'),
	(2, 'non fiksi', 'buku pembelajaran', '2025-10-06 09:06:30');

-- Dumping structure for table perpustakaan1.notifikasi
CREATE TABLE IF NOT EXISTS `notifikasi` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `judul` varchar(255) NOT NULL,
  `pesan` text NOT NULL,
  `dibaca` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `notifikasi_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table perpustakaan1.notifikasi: ~0 rows (approximately)
DELETE FROM `notifikasi`;

-- Dumping structure for table perpustakaan1.pembayaran_denda
CREATE TABLE IF NOT EXISTS `pembayaran_denda` (
  `id` int NOT NULL AUTO_INCREMENT,
  `peminjaman_id` int NOT NULL,
  `jumlah_denda` decimal(10,2) NOT NULL,
  `jumlah_bayar` decimal(10,2) NOT NULL,
  `status` enum('pending','lunas') DEFAULT 'pending',
  `bukti_bayar` varchar(255) DEFAULT NULL,
  `verified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `peminjaman_id` (`peminjaman_id`),
  KEY `verified_by` (`verified_by`),
  CONSTRAINT `pembayaran_denda_ibfk_1` FOREIGN KEY (`peminjaman_id`) REFERENCES `peminjaman` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pembayaran_denda_ibfk_2` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table perpustakaan1.pembayaran_denda: ~0 rows (approximately)
DELETE FROM `pembayaran_denda`;

-- Dumping structure for table perpustakaan1.peminjaman
CREATE TABLE IF NOT EXISTS `peminjaman` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `buku_id` int NOT NULL,
  `tanggal_pinjam` date NOT NULL,
  `tanggal_jatuh_tempo` date NOT NULL,
  `tanggal_kembali` date DEFAULT NULL,
  `status` enum('dipinjam','dikembalikan','terlambat') DEFAULT 'dipinjam',
  `status_peminjaman` enum('menunggu','disetujui','ditolak') DEFAULT 'menunggu',
  `denda` decimal(10,2) DEFAULT '0.00',
  `approved_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status_bayar_denda` enum('belum_bayar','lunas') DEFAULT 'belum_bayar',
  `jumlah_bayar_denda` decimal(10,2) DEFAULT NULL,
  `tanggal_bayar_denda` date DEFAULT NULL,
  `verified_by_denda` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `buku_id` (`buku_id`),
  KEY `approved_by` (`approved_by`),
  KEY `verified_by_denda` (`verified_by_denda`),
  CONSTRAINT `peminjaman_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `peminjaman_ibfk_2` FOREIGN KEY (`buku_id`) REFERENCES `buku` (`id`) ON DELETE CASCADE,
  CONSTRAINT `peminjaman_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `peminjaman_ibfk_4` FOREIGN KEY (`verified_by_denda`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table perpustakaan1.peminjaman: ~16 rows (approximately)
DELETE FROM `peminjaman`;
INSERT INTO `peminjaman` (`id`, `user_id`, `buku_id`, `tanggal_pinjam`, `tanggal_jatuh_tempo`, `tanggal_kembali`, `status`, `status_peminjaman`, `denda`, `approved_by`, `created_at`, `updated_at`, `status_bayar_denda`, `jumlah_bayar_denda`, `tanggal_bayar_denda`, `verified_by_denda`) VALUES
	(2, 2, 5, '2025-10-07', '2025-10-14', '2025-10-21', 'dikembalikan', 'disetujui', 7000.00, 3, '2025-10-07 03:36:19', '2025-11-21 04:12:57', 'lunas', 7000.00, '2025-11-21', 6),
	(4, 2, 6, '2025-10-14', '2025-10-21', '2025-11-17', 'dikembalikan', 'disetujui', 27000.00, 3, '2025-10-14 16:02:27', '2025-11-24 05:09:07', 'lunas', 27000.00, '2025-11-24', 6),
	(7, 2, 5, '2025-11-24', '2025-12-01', NULL, 'dipinjam', 'ditolak', 0.00, 6, '2025-11-24 04:05:12', '2025-11-24 04:07:08', 'belum_bayar', NULL, NULL, NULL),
	(8, 2, 5, '2025-11-24', '2025-12-01', NULL, 'dipinjam', 'ditolak', 0.00, 6, '2025-11-24 04:05:20', '2025-11-24 04:07:05', 'belum_bayar', NULL, NULL, NULL),
	(9, 2, 5, '2025-11-24', '2025-12-01', NULL, 'dipinjam', 'ditolak', 0.00, 6, '2025-11-24 04:05:23', '2025-11-24 04:07:04', 'belum_bayar', NULL, NULL, NULL),
	(10, 2, 5, '2025-11-24', '2025-12-01', NULL, 'dipinjam', 'ditolak', 0.00, 6, '2025-11-24 04:05:24', '2025-11-24 04:07:03', 'belum_bayar', NULL, NULL, NULL),
	(11, 2, 5, '2025-11-24', '2025-12-01', NULL, 'dipinjam', 'ditolak', 0.00, 6, '2025-11-24 04:05:25', '2025-11-24 04:07:00', 'belum_bayar', NULL, NULL, NULL),
	(12, 2, 8, '2025-11-24', '2025-12-01', NULL, 'dipinjam', 'ditolak', 0.00, 6, '2025-11-24 04:05:27', '2025-11-24 04:13:37', 'belum_bayar', NULL, NULL, NULL),
	(13, 2, 8, '2025-11-24', '2025-12-01', NULL, 'dipinjam', 'ditolak', 0.00, 6, '2025-11-24 04:05:28', '2025-11-24 04:06:57', 'belum_bayar', NULL, NULL, NULL),
	(14, 2, 8, '2025-11-24', '2025-12-01', NULL, 'dipinjam', 'ditolak', 0.00, 6, '2025-11-24 04:05:29', '2025-11-24 04:06:51', 'belum_bayar', NULL, NULL, NULL),
	(15, 2, 8, '2025-11-24', '2025-12-01', NULL, 'dipinjam', 'ditolak', 0.00, 6, '2025-11-24 04:05:29', '2025-11-24 04:06:54', 'belum_bayar', NULL, NULL, NULL),
	(16, 2, 8, '2025-11-24', '2025-12-01', NULL, 'dipinjam', 'ditolak', 0.00, 6, '2025-11-24 04:05:30', '2025-11-24 04:06:50', 'belum_bayar', NULL, NULL, NULL),
	(17, 2, 5, '2025-11-24', '2025-12-01', NULL, 'dipinjam', 'ditolak', 0.00, 6, '2025-11-24 04:05:33', '2025-11-24 04:13:34', 'belum_bayar', NULL, NULL, NULL),
	(18, 2, 5, '2025-11-24', '2025-12-01', NULL, 'dipinjam', 'ditolak', 0.00, 6, '2025-11-24 04:05:35', '2025-11-24 04:06:46', 'belum_bayar', NULL, NULL, NULL),
	(19, 2, 5, '2025-11-24', '2025-12-01', NULL, 'dipinjam', 'menunggu', 0.00, NULL, '2025-11-24 04:20:59', '2025-11-24 04:20:59', 'belum_bayar', NULL, NULL, NULL),
	(20, 2, 5, '2025-11-25', '2025-12-02', '2025-11-25', 'dikembalikan', 'disetujui', 0.00, 6, '2025-11-25 02:58:11', '2025-11-25 03:03:22', 'belum_bayar', NULL, NULL, NULL);

-- Dumping structure for table perpustakaan1.users
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nisn` varchar(20) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('siswa','pustakawan','admin') DEFAULT 'siswa',
  `status` enum('aktif','nonaktif') DEFAULT 'aktif',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nisn` (`nisn`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table perpustakaan1.users: ~6 rows (approximately)
DELETE FROM `users`;
INSERT INTO `users` (`id`, `nisn`, `nama`, `email`, `password`, `role`, `status`, `created_at`, `updated_at`) VALUES
	(2, '701230001', 'ais', 'siswa@gmail.com', '$2y$10$uA3Z83umA9xN9BeiM81JkeVH7.lsEOvvVwff.PrzRp5mgZP6VxwGW', 'siswa', 'aktif', '2025-10-06 08:30:58', '2025-11-26 03:25:38'),
	(3, '701230002', 'Nadhif', 'pustakawan@gmail.com', '$2y$10$oXo8DqeLCRlmd7rBMNFVq.1mbd7dRmSy6PQuzl5EvIG5/kVCj20CK', 'pustakawan', 'aktif', '2025-10-06 08:30:58', '2025-11-25 03:08:40'),
	(6, '701230005', 'deni', 'deni123@gmail.com', '$2y$10$ohdgRAgEfW/BN4DlBY9HbeLFr4PcUGMhGUAV.AIGMPF/u29UfaSCm', 'siswa', 'aktif', '2025-11-17 05:44:39', '2025-11-26 02:33:12'),
	(10, '701230003', 'demoadmin', 'demoadmin@gmail.com', '$2y$10$2JCjBbyupgePGLmad2b1WuYDliWY5cAn9pbAbzSbfKuHbNOaYILLa', 'admin', 'aktif', '2025-11-26 02:23:12', '2025-11-26 02:32:49'),
	(11, '701230004', 'demosiswa', 'demosiswa@gmail.com', '$2y$10$2mHusi1sl5t/qJKB.Q2mOO1cCDvOeBK0z87dgcT8gcIZ5H8652TXi', 'siswa', 'aktif', '2025-11-26 02:31:51', '2025-11-26 02:33:03'),
	(12, '701230006', 'demopustakawan', 'demopustakwan@gmail.com', '$2y$10$iwVGIlIrz8LjY0.sbeABiuXcTrBQx29Mcjt.CyzUg6RU7Jdaws96y', 'pustakawan', 'aktif', '2025-11-26 02:34:39', '2025-11-26 02:34:39');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
