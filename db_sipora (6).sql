-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Dec 04, 2025 at 12:22 AM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_sipora`
--

-- --------------------------------------------------------

--
-- Table structure for table `dokumen`
--

CREATE TABLE `dokumen` (
  `dokumen_id` int NOT NULL,
  `judul` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `abstrak` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `turnitin` int NOT NULL,
  `turnitin_file` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `kata_kunci` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `file_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tgl_unggah` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `uploader_id` int NOT NULL,
  `id_tema` int DEFAULT NULL,
  `id_jurusan` int DEFAULT NULL,
  `id_prodi` int DEFAULT NULL,
  `id_divisi` int DEFAULT NULL,
  `year_id` int DEFAULT NULL,
  `status_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dokumen`
--

INSERT INTO `dokumen` (`dokumen_id`, `judul`, `abstrak`, `turnitin`, `turnitin_file`, `kata_kunci`, `file_path`, `tgl_unggah`, `uploader_id`, `id_tema`, `id_jurusan`, `id_prodi`, `id_divisi`, `year_id`, `status_id`) VALUES
(122, 'Laporan akhir', 'laporan akhir', 10, '10_turnitin_1764675647.pdf', 'laporan akhir', '10_1764675647.pdf', '2025-12-02 11:40:47', 10, 19, 1, 1, 261, 202, 5),
(123, 'Topologi jaringan', 'jaringan komputer', 11, '10_turnitin_1764675808.pdf', 'jaringan', '10_1764675808.pdf', '2025-12-02 11:43:28', 10, 16, 1, 3, 263, 202, 4),
(124, 'sistem jaringan pada kopi', 'sistem jaringan kopi', 7, '25_turnitin_1764676059.pdf', 'kopi', '25_1764676059.pdf', '2025-12-02 11:47:39', 25, 8, 2, 10, 263, 202, 3),
(125, 'Otomotif kendaraan hemat energi', 'otomotif hemat energi', 8, '25_turnitin_1764676170.pdf', 'otomotif', '25_1764676170.pdf', '2025-12-02 11:49:30', 25, 2, 8, 26, 261, 202, 1),
(126, 'management kasir', 'management perkasiran minimarket', 8, '25_turnitin_1764676254.pdf', 'management', '25_1764676254.pdf', '2025-12-02 11:50:54', 25, 5, 5, 17, 261, 202, 5),
(127, 'sistem jaringan pada kopi', 'sistem jaringan pada kopi', 10, '10_turnitin_1764737447.pdf', 'kopi,sistem jaringan', '10_1764737447.pdf', '2025-12-03 04:50:47', 10, 9, 8, 27, 262, 202, 5);

-- --------------------------------------------------------

--
-- Table structure for table `dokumen_author`
--

CREATE TABLE `dokumen_author` (
  `dokumen_author_id` int NOT NULL,
  `dokumen_id` int NOT NULL,
  `author_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dokumen_keyword`
--

CREATE TABLE `dokumen_keyword` (
  `dokumen_keyword_id` int NOT NULL,
  `dokumen_id` int NOT NULL,
  `keyword_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `log_review`
--

CREATE TABLE `log_review` (
  `log_id` int NOT NULL,
  `dokumen_id` int NOT NULL,
  `reviewer_id` int NOT NULL,
  `tgl_review` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `catatan_review` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `status_sebelum` int DEFAULT NULL,
  `status_sesudah` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `log_review`
--

INSERT INTO `log_review` (`log_id`, `dokumen_id`, `reviewer_id`, `tgl_review`, `catatan_review`, `status_sebelum`, `status_sesudah`) VALUES
(78, 126, 5, '2025-12-02 11:52:48', 'Dokumen diubah menjadi status Disetujui.', 1, 3),
(79, 126, 5, '2025-12-02 11:53:06', 'Dokumen diubah menjadi status Publikasi.', 3, 5),
(80, 122, 5, '2025-12-02 11:53:25', 'Dokumen diubah menjadi status Disetujui.', 1, 3),
(81, 122, 5, '2025-12-02 11:53:43', 'Dokumen diubah menjadi status Publikasi.', 3, 5),
(82, 124, 5, '2025-12-02 11:54:09', 'Dokumen diubah menjadi status Disetujui.', 1, 3),
(83, 123, 5, '2025-12-02 11:55:17', 'tidak sesuai format', 1, 4),
(84, 127, 5, '2025-12-03 12:37:58', 'Dokumen diubah menjadi status Disetujui.', 1, 3),
(85, 127, 5, '2025-12-03 12:38:17', 'Dokumen diubah menjadi status Publikasi.', 3, 5);

-- --------------------------------------------------------

--
-- Table structure for table `master_author`
--

CREATE TABLE `master_author` (
  `author_id` int NOT NULL,
  `nama_author` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `master_divisi`
--

CREATE TABLE `master_divisi` (
  `id_divisi` int NOT NULL,
  `nama_divisi` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `master_divisi`
--

INSERT INTO `master_divisi` (`id_divisi`, `nama_divisi`) VALUES
(261, 'Tugas Akhir'),
(262, 'PKL'),
(263, 'Publikasi'),
(264, 'General');

-- --------------------------------------------------------

--
-- Table structure for table `master_jurusan`
--

CREATE TABLE `master_jurusan` (
  `id_jurusan` int NOT NULL,
  `nama_jurusan` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `id_rumpun` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `master_jurusan`
--

INSERT INTO `master_jurusan` (`id_jurusan`, `nama_jurusan`, `id_rumpun`) VALUES
(1, 'Teknologi Informasi', NULL),
(2, 'Produksi Pertanian', NULL),
(3, 'Teknologi Pertanian', NULL),
(4, 'Peternakan', NULL),
(5, 'Manajemen Agribisnis', NULL),
(6, 'Bahasa Komunikasi dan Pariwisata', NULL),
(7, 'Kesehatan', NULL),
(8, 'Teknik', NULL),
(9, 'Bisnis', NULL),
(10, 'Kelas Internasional', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `master_keyword`
--

CREATE TABLE `master_keyword` (
  `keyword_id` int NOT NULL,
  `nama_keyword` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `master_prodi`
--

CREATE TABLE `master_prodi` (
  `id_jurusan` int NOT NULL,
  `id_prodi` int NOT NULL,
  `nama_prodi` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `master_prodi`
--

INSERT INTO `master_prodi` (`id_jurusan`, `id_prodi`, `nama_prodi`) VALUES
(1, 1, 'Teknik Informatika'),
(1, 2, 'Manajemen Informatika'),
(1, 3, 'Teknik Komputer'),
(1, 4, 'Teknologi Rekayasa Komputer'),
(2, 5, 'Produksi Tanaman Hortikultura'),
(2, 6, 'Produksi Tanaman Perkebunan'),
(2, 7, 'Teknik Produksi Benih'),
(2, 8, 'Teknologi Produksi Tanaman Pangan'),
(2, 9, 'Budidaya Tanaman Perkebunan'),
(2, 10, 'Pengelolaan Perkebunan Kopi'),
(3, 11, 'Keteknikan Pertanian'),
(3, 12, 'Teknologi Industri Pangan'),
(3, 13, 'Teknologi Rekayasa Pangan'),
(4, 14, 'Produksi Ternak'),
(4, 15, 'Manajemen Bisnis Unggas'),
(4, 16, 'Teknologi Pakan Ternak'),
(5, 17, 'Manajemen Agribisnis'),
(5, 18, 'Manajemen Agroindustri'),
(5, 19, 'Pascasarjana Agribisnis'),
(6, 20, 'Bahasa Inggris'),
(6, 21, 'Destinasi Pariwisata'),
(6, 22, 'Produksi Media Kampus Bondowoso'),
(7, 23, 'Manajemen Informasi Kesehatan'),
(7, 24, 'Gizi Klinik'),
(7, 25, 'Promosi Kesehatan'),
(8, 26, 'Teknik Mesin Otomotif'),
(8, 27, 'Teknik Energi Terbarukan'),
(8, 28, 'Teknologi Rekayasa Mekatronika'),
(9, 29, 'Akuntansi Sektor Publik'),
(9, 30, 'Manajemen Pemasaran Internasional'),
(9, 31, 'Bisnis Digital (Kampus Bondowoso)'),
(10, 32, 'Manajemen Informatika'),
(10, 33, 'Teknik Informatika'),
(10, 34, 'Manajemen Agroindustri');

-- --------------------------------------------------------

--
-- Table structure for table `master_rumpun`
--

CREATE TABLE `master_rumpun` (
  `id_rumpun` int NOT NULL,
  `nama_rumpun` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `master_rumpun`
--

INSERT INTO `master_rumpun` (`id_rumpun`, `nama_rumpun`) VALUES
(1, 'Rumpun Matematika dan Ilmu Pengetahuan Alam (MIPA)'),
(2, 'Rumpun Ilmu Tanaman'),
(3, 'Rumpun Ilmu Hewani'),
(4, 'Rumpun Ilmu Kesehatan'),
(5, 'Rumpun Ilmu Teknik'),
(6, 'Rumpun Ilmu Bahasa'),
(7, 'Rumpun Ilmu Ekonomi dan Bisnis'),
(8, 'Rumpun Ilmu Sosial, Politik, dan Humaniora'),
(9, 'Rumpun Ilmu Agama dan Filsafat'),
(10, 'Rumpun Ilmu Seni, Desain, dan Media'),
(11, 'Rumpun Ilmu Pendidikan'),
(12, 'Rumpun Umum / Lainnya');

-- --------------------------------------------------------

--
-- Table structure for table `master_status`
--

CREATE TABLE `master_status` (
  `id_status` int NOT NULL,
  `nama_status` varchar(50) NOT NULL,
  `deskripsi` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `master_status`
--

INSERT INTO `master_status` (`id_status`, `nama_status`, `deskripsi`, `created_at`) VALUES
(1, 'Draft', 'Dokumen dalam status draft', '2025-11-14 17:23:16'),
(2, 'Review', 'Dokumen sedang dalam proses review', '2025-11-14 17:23:16'),
(3, 'Approved', 'Dokumen telah disetujui', '2025-11-14 17:23:16'),
(4, 'Rejected', 'Dokumen ditolak', '2025-11-14 17:23:16');

-- --------------------------------------------------------

--
-- Table structure for table `master_status_dokumen`
--

CREATE TABLE `master_status_dokumen` (
  `status_id` int NOT NULL,
  `nama_status` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `master_status_dokumen`
--

INSERT INTO `master_status_dokumen` (`status_id`, `nama_status`) VALUES
(1, 'Menunggu Review'),
(2, 'Diperiksa'),
(3, 'Disetujui'),
(4, 'Ditolak'),
(5, 'Publikasi');

-- --------------------------------------------------------

--
-- Table structure for table `master_tahun`
--

CREATE TABLE `master_tahun` (
  `year_id` int NOT NULL,
  `tahun` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `master_tahun`
--

INSERT INTO `master_tahun` (`year_id`, `tahun`) VALUES
(202, '2025'),
(203, '2024'),
(204, '2023'),
(205, '2022'),
(206, '2021'),
(207, '2020'),
(208, '2019'),
(209, '2018'),
(210, '2017'),
(211, '2016'),
(212, '2015'),
(213, '2014'),
(214, '2013'),
(215, '2012'),
(216, '2011'),
(217, '2010'),
(218, '2004'),
(219, '2002'),
(220, '0202'),
(221, '0201'),
(222, '0031'),
(223, '0030'),
(224, '0028'),
(225, '0027'),
(226, '0026'),
(227, '0025'),
(228, '0024'),
(229, '0023'),
(230, '0022'),
(231, '0021'),
(232, '0020'),
(233, '0019'),
(234, '0015'),
(235, '0011'),
(236, '0006'),
(237, '0005'),
(238, '0004'),
(239, '0002'),
(240, '0001'),
(241, 'Not Specified');

-- --------------------------------------------------------

--
-- Table structure for table `master_tema`
--

CREATE TABLE `master_tema` (
  `id_tema` int NOT NULL,
  `id_rumpun` int DEFAULT NULL,
  `kode_tema` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nama_tema` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `master_tema`
--

INSERT INTO `master_tema` (`id_tema`, `id_rumpun`, `kode_tema`, `nama_tema`) VALUES
(2, 1, '111', 'Fisika'),
(3, 1, '112', 'Kimia'),
(4, 1, '113', 'Biologi'),
(5, 1, '120', 'Matematika'),
(6, 1, '123', 'Ilmu Komputer'),
(7, 2, '150', 'Ilmu Pertanian dan Perkebunan'),
(8, 2, '152', 'Hortikultura'),
(9, 2, '155', 'Perkebunan'),
(10, 2, '160', 'Teknologi dalam Ilmu Tanaman'),
(11, 2, '165', 'Teknologi Pangan dan Gizi'),
(12, 4, '350', 'Ilmu Kesehatan Umum'),
(13, 4, '353', 'Kebijakan dan Analisis Kesehatan'),
(14, 4, '354', 'Ilmu Gizi'),
(15, 4, '357', 'Promosi Kesehatan'),
(16, 5, '457', 'Teknik Komputer'),
(17, 5, '458', 'Teknik Informatika'),
(18, 5, '462', 'Teknologi Informasi'),
(19, 5, '463', 'Teknik Perangkat Lunak'),
(20, 11, '742', 'Pendidikan Bahasa Inggris'),
(21, 11, '772', 'Pendidikan Matematika'),
(22, 11, '773', 'Pendidikan Fisika');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `actor_id` int DEFAULT NULL,
  `doc_id` int DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `message` text,
  `icon_type` varchar(50) DEFAULT NULL,
  `icon_class` varchar(100) DEFAULT NULL,
  `status_name` varchar(100) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `actor_id`, `doc_id`, `type`, `title`, `message`, `icon_type`, `icon_class`, `status_name`, `is_read`, `created_at`) VALUES
(1, NULL, 5, 77, 'upload', 'Dokumen Baru', '<strong>admin</strong> mengunggah dokumen: \"Algoritma dan Pemrograman\"', 'info', 'bi-file-earmark-plus', NULL, 0, '2025-11-24 07:45:50'),
(2, 5, 5, 77, 'upload_confirm', 'Upload Berhasil', 'Dokumen \"Algoritma dan Pemrograman\" berhasil diunggah.', 'success', 'bi-check-circle-fill', NULL, 0, '2025-11-24 07:45:50'),
(3, NULL, 5, 78, 'upload', 'Dokumen Baru', '<strong>admin</strong> mengunggah dokumen: \"Algoritma dan Pemrograman\"', 'info', 'bi-file-earmark-plus', NULL, 0, '2025-11-24 07:47:37'),
(4, 5, 5, 78, 'upload_confirm', 'Upload Berhasil', 'Dokumen \"Algoritma dan Pemrograman\" berhasil diunggah.', 'success', 'bi-check-circle-fill', NULL, 0, '2025-11-24 07:47:37'),
(5, NULL, 5, 79, 'upload', 'Dokumen Baru', '<strong>admin</strong> mengunggah dokumen: \"Computer\"', 'info', 'bi-file-earmark-plus', NULL, 0, '2025-11-24 07:52:56'),
(6, 5, 5, 79, 'upload_confirm', 'Upload Berhasil', 'Dokumen \"Computer\" berhasil diunggah.', 'success', 'bi-check-circle-fill', NULL, 0, '2025-11-24 07:52:56'),
(7, NULL, 5, 80, 'upload', 'Dokumen Baru', '<strong>admin</strong> mengunggah dokumen: \"Computer\"', 'info', 'bi-file-earmark-plus', NULL, 0, '2025-11-24 08:00:15'),
(8, 5, 5, 80, 'upload_confirm', 'Upload Berhasil', 'Dokumen \"Computer\" berhasil diunggah.', 'success', 'bi-check-circle-fill', NULL, 0, '2025-11-24 08:00:15'),
(9, NULL, 5, 81, 'upload', 'Dokumen Baru', '<strong>admin</strong> mengunggah dokumen: \"Matematika Diskrit\"', 'info', 'bi-file-earmark-plus', NULL, 0, '2025-11-24 10:47:23'),
(10, 5, 5, 81, 'upload_confirm', 'Upload Berhasil', 'Dokumen \"Matematika Diskrit\" berhasil diunggah.', 'success', 'bi-check-circle-fill', NULL, 0, '2025-11-24 10:47:23'),
(11, NULL, 5, 82, 'upload', 'Dokumen Baru', '<strong>admin</strong> mengunggah dokumen: \"Algoritma dan Pemrograman\"', 'info', 'bi-file-earmark-plus', NULL, 0, '2025-11-24 11:26:06'),
(12, 5, 5, 82, 'upload_confirm', 'Upload Berhasil', 'Dokumen \"Algoritma dan Pemrograman\" berhasil diunggah.', 'success', 'bi-check-circle-fill', NULL, 0, '2025-11-24 11:26:06'),
(13, NULL, 5, 83, 'upload', 'Dokumen Baru', '<strong>admin</strong> mengunggah dokumen: \"Algoritma dan Pemrograman\"', 'info', 'bi-file-earmark-plus', NULL, 0, '2025-11-24 11:26:33'),
(14, 5, 5, 83, 'upload_confirm', 'Upload Berhasil', 'Dokumen \"Algoritma dan Pemrograman\" berhasil diunggah.', 'success', 'bi-check-circle-fill', NULL, 0, '2025-11-24 11:26:33'),
(15, 5, NULL, 83, 'document_status', 'Dokumen Disetujui', 'Dokumen \"Algoritma dan Pemrograman\" telah <strong>disetujui</strong> oleh reviewer', 'success', 'bi-check-circle-fill', NULL, 0, '2025-11-24 11:31:51'),
(16, 5, NULL, 82, 'document_status', 'Dokumen Disetujui', 'Dokumen \"Algoritma dan Pemrograman\" telah <strong>disetujui</strong> oleh reviewer', 'success', 'bi-check-circle-fill', NULL, 0, '2025-11-24 11:31:51'),
(17, 5, NULL, 81, 'document_status', 'Status Diperbarui', 'Dokumen \"Matematika Diskrit\" berstatus: <strong>Approved</strong>', 'info', 'bi-info-circle-fill', NULL, 0, '2025-11-24 11:31:51'),
(18, 5, NULL, 80, 'document_status', 'Status Diperbarui', 'Dokumen \"Computer\" berstatus: <strong>Approved</strong>', 'info', 'bi-info-circle-fill', NULL, 0, '2025-11-24 11:31:51'),
(19, 5, NULL, 79, 'document_status', 'Dokumen Ditolak', 'Dokumen \"Computer\" <strong>ditolak</strong>. Silakan periksa kembali dokumen Anda', 'danger', 'bi-x-circle-fill', NULL, 0, '2025-11-24 11:31:51'),
(20, 5, NULL, 78, 'document_status', 'Status Diperbarui', 'Dokumen \"Algoritma dan Pemrograman\" berstatus: <strong>Approved</strong>', 'info', 'bi-info-circle-fill', NULL, 0, '2025-11-24 11:31:51'),
(21, 5, NULL, 77, 'document_status', 'Dokumen Disetujui', 'Dokumen \"Algoritma dan Pemrograman\" telah <strong>disetujui</strong> oleh reviewer', 'success', 'bi-check-circle-fill', NULL, 0, '2025-11-24 11:31:51'),
(22, 5, NULL, 76, 'document_status', 'Dokumen Disetujui', 'Dokumen \"Algoritma dan Pemrograman\" telah <strong>disetujui</strong> oleh reviewer', 'success', 'bi-check-circle-fill', NULL, 0, '2025-11-24 11:31:51'),
(23, 5, NULL, 75, 'document_status', 'Dokumen Disetujui', 'Dokumen \"Matematika Diskrit\" telah <strong>disetujui</strong> oleh reviewer', 'success', 'bi-check-circle-fill', NULL, 0, '2025-11-24 11:31:51'),
(24, 5, NULL, 74, 'document_status', 'Dokumen Disetujui', 'Dokumen \"Matematika Diskrit\" telah <strong>disetujui</strong> oleh reviewer', 'success', 'bi-check-circle-fill', NULL, 0, '2025-11-24 11:31:51'),
(25, 5, NULL, 73, 'document_status', 'Dokumen Disetujui', 'Dokumen \"Matematika Diskrit\" telah <strong>disetujui</strong> oleh reviewer', 'success', 'bi-check-circle-fill', NULL, 0, '2025-11-24 11:31:51'),
(26, 5, NULL, 72, 'document_status', 'Dokumen Disetujui', 'Dokumen \"Matematika Diskrit\" telah <strong>disetujui</strong> oleh reviewer', 'success', 'bi-check-circle-fill', NULL, 0, '2025-11-24 11:31:51'),
(27, 5, NULL, 70, 'document_status', 'Dokumen Diterbitkan', 'Dokumen \"Algoritma dan Pemrograman\" telah <strong>diterbitkan</strong> dan tersedia untuk umum', 'success', 'bi-check-circle-fill', NULL, 0, '2025-11-24 11:31:51'),
(28, 5, NULL, 69, 'document_status', 'Status Diperbarui', 'Dokumen \"Matematika Diskrit\" berstatus: <strong>Approved</strong>', 'info', 'bi-info-circle-fill', NULL, 0, '2025-11-24 11:31:51'),
(29, 5, NULL, 71, 'new_document', 'Dokumen Baru', '<strong>admin02</strong> mengunggah dokumen: \"Computer\"', 'info', 'bi-file-earmark-plus', NULL, 0, '2025-11-24 11:31:51'),
(30, NULL, 5, 84, 'upload', 'Dokumen Baru', '<strong>admin</strong> mengunggah dokumen: \"Computer Networking\"', 'info', 'bi-file-earmark-plus', NULL, 0, '2025-11-24 11:42:47'),
(31, 5, 5, 84, 'upload_confirm', 'Upload Berhasil', 'Dokumen \"Computer Networking\" berhasil diunggah.', 'success', 'bi-check-circle-fill', NULL, 0, '2025-11-24 11:42:47'),
(32, NULL, 5, 85, 'upload', 'Dokumen Baru', '<strong>admin</strong> mengunggah dokumen: \"Computer Networking\"', 'info', 'bi-file-earmark-plus', NULL, 0, '2025-11-24 11:47:57'),
(33, 5, 5, 85, 'upload_confirm', 'Upload Berhasil', 'Dokumen \"Computer Networking\" berhasil diunggah.', 'success', 'bi-check-circle-fill', NULL, 0, '2025-11-24 11:47:57'),
(34, NULL, 5, 86, 'upload', 'Dokumen Baru', '<strong>admin</strong> mengunggah dokumen: \"Computer Networking\"', 'info', 'bi-file-earmark-plus', NULL, 0, '2025-11-24 11:48:18'),
(35, 5, 5, 86, 'upload_confirm', 'Upload Berhasil', 'Dokumen \"Computer Networking\" berhasil diunggah.', 'success', 'bi-check-circle-fill', NULL, 0, '2025-11-24 11:48:18'),
(36, NULL, 10, 87, 'upload', 'Dokumen Baru', '<strong>admin02</strong> mengunggah dokumen: \"Algoritma dan Pemrograman\"', 'info', 'bi-file-earmark-plus', NULL, 0, '2025-11-25 05:13:10'),
(37, 10, 10, 87, 'upload_confirm', 'Upload Berhasil', 'Dokumen \"Algoritma dan Pemrograman\" berhasil diunggah.', 'success', 'bi-check-circle-fill', NULL, 0, '2025-11-25 05:13:10'),
(38, NULL, 10, 88, 'upload', 'Dokumen Baru', '<strong>admin02</strong> mengunggah dokumen: \"Algoritma dan Pemrograman\"', 'info', 'bi-file-earmark-plus', NULL, 0, '2025-11-25 05:13:28'),
(39, 10, 10, 88, 'upload_confirm', 'Upload Berhasil', 'Dokumen \"Algoritma dan Pemrograman\" berhasil diunggah.', 'success', 'bi-check-circle-fill', NULL, 0, '2025-11-25 05:13:28'),
(40, NULL, 10, 89, 'upload', 'Dokumen Baru', '<strong>admin02</strong> mengunggah dokumen: \"Computer Networking\"', 'info', 'bi-file-earmark-plus', NULL, 0, '2025-11-25 05:47:29'),
(41, 10, 10, 89, 'upload_confirm', 'Upload Berhasil', 'Dokumen \"Computer Networking\" berhasil diunggah.', 'success', 'bi-check-circle-fill', NULL, 0, '2025-11-25 05:47:29'),
(42, NULL, 10, 90, 'upload', 'Dokumen Baru', '<strong>admin02</strong> mengunggah dokumen: \"Matematika Diskrit\"', 'info', 'bi-file-earmark-plus', NULL, 0, '2025-11-25 07:20:46'),
(43, 10, 10, 90, 'upload_confirm', 'Upload Berhasil', 'Dokumen \"Matematika Diskrit\" berhasil diunggah.', 'success', 'bi-check-circle-fill', NULL, 0, '2025-11-25 07:20:46'),
(44, NULL, 10, 91, 'upload', 'Dokumen Baru', '<strong>admin02</strong> mengunggah dokumen: \"Computer Networking\"', 'info', 'bi-file-earmark-plus', NULL, 0, '2025-11-25 07:34:30'),
(45, 10, 10, 91, 'upload_confirm', 'Upload Berhasil', 'Dokumen \"Computer Networking\" berhasil diunggah.', 'success', 'bi-check-circle-fill', NULL, 0, '2025-11-25 07:34:30'),
(46, NULL, 10, 92, 'upload', 'Dokumen Baru', '<strong>admin02</strong> mengunggah dokumen: \"Computer Networking\"', 'info', 'bi-file-earmark-plus', NULL, 0, '2025-11-25 07:35:03'),
(47, 10, 10, 92, 'upload_confirm', 'Upload Berhasil', 'Dokumen \"Computer Networking\" berhasil diunggah.', 'success', 'bi-check-circle-fill', NULL, 0, '2025-11-25 07:35:03'),
(48, NULL, 10, 93, 'upload', 'Dokumen Baru', '<strong>admin02</strong> mengunggah dokumen: \"Computer Networking\"', 'info', 'bi-file-earmark-plus', NULL, 0, '2025-11-25 07:39:36'),
(49, 10, 10, 93, 'upload_confirm', 'Upload Berhasil', 'Dokumen \"Computer Networking\" berhasil diunggah.', 'success', 'bi-check-circle-fill', NULL, 0, '2025-11-25 07:39:36'),
(50, NULL, 10, 94, 'upload', 'Dokumen Baru', '<strong>admin02</strong> mengunggah dokumen: \"Computer Networking\"', 'info', 'bi-file-earmark-plus', NULL, 0, '2025-11-25 07:39:56'),
(51, 10, 10, 94, 'upload_confirm', 'Upload Berhasil', 'Dokumen \"Computer Networking\" berhasil diunggah.', 'success', 'bi-check-circle-fill', NULL, 0, '2025-11-25 07:39:57'),
(52, NULL, 10, 95, 'upload', 'Dokumen Baru', '<strong>admin02</strong> mengunggah dokumen: \"Algoritma dan Pemrograman\"', 'info', 'bi-file-earmark-plus', NULL, 0, '2025-11-25 19:20:38'),
(53, 10, 10, 95, 'upload_confirm', 'Upload Berhasil', 'Dokumen \"Algoritma dan Pemrograman\" berhasil diunggah.', 'success', 'bi-check-circle-fill', NULL, 0, '2025-11-25 19:20:38'),
(54, NULL, 10, 96, 'upload', 'Dokumen Baru', '<strong>admin02</strong> mengunggah dokumen: \"Algoritma dan Pemrograman\"', 'info', 'bi-file-earmark-plus', NULL, 0, '2025-11-25 19:20:45'),
(55, 10, 10, 96, 'upload_confirm', 'Upload Berhasil', 'Dokumen \"Algoritma dan Pemrograman\" berhasil diunggah.', 'success', 'bi-check-circle-fill', NULL, 0, '2025-11-25 19:20:45'),
(56, NULL, 10, 97, 'upload', 'Dokumen Baru', '<strong>admin02</strong> mengunggah dokumen: \"hai\"', 'info', 'bi-file-earmark-plus', NULL, 0, '2025-11-25 19:22:52'),
(57, 10, 10, 97, 'upload_confirm', 'Upload Berhasil', 'Dokumen \"hai\" berhasil diunggah.', 'success', 'bi-check-circle-fill', NULL, 0, '2025-11-25 19:22:52'),
(58, NULL, 10, 98, 'upload', 'Dokumen Baru', '<strong>admin02</strong> mengunggah dokumen: \"hai\"', 'info', 'bi-file-earmark-plus', NULL, 0, '2025-11-25 19:24:01'),
(59, 10, 10, 98, 'upload_confirm', 'Upload Berhasil', 'Dokumen \"hai\" berhasil diunggah.', 'success', 'bi-check-circle-fill', NULL, 0, '2025-11-25 19:24:01'),
(60, NULL, 10, 99, 'upload', 'Dokumen Baru', '<strong>admin02</strong> mengunggah dokumen: \"sibal\"', 'info', 'bi-file-earmark-plus', NULL, 0, '2025-11-25 19:26:34'),
(61, 10, 10, 99, 'upload_confirm', 'Upload Berhasil', 'Dokumen \"sibal\" berhasil diunggah.', 'success', 'bi-check-circle-fill', NULL, 0, '2025-11-25 19:26:34'),
(62, NULL, 5, 100, 'upload', 'Dokumen Baru', '<strong>admin</strong> mengunggah dokumen: \"Matematika Diskrit\"', 'info', 'bi-file-earmark-plus', NULL, 0, '2025-11-25 19:33:30'),
(63, 5, 5, 100, 'upload_confirm', 'Upload Berhasil', 'Dokumen \"Matematika Diskrit\" berhasil diunggah.', 'success', 'bi-check-circle-fill', NULL, 0, '2025-11-25 19:33:30'),
(64, NULL, 5, 101, 'upload', 'Dokumen Baru', '<strong>admin</strong> mengunggah dokumen: \"Matematika Diskrit\"', 'info', 'bi-file-earmark-plus', NULL, 0, '2025-11-25 19:39:02'),
(65, 5, 5, 101, 'upload_confirm', 'Upload Berhasil', 'Dokumen \"Matematika Diskrit\" berhasil diunggah.', 'success', 'bi-check-circle-fill', NULL, 0, '2025-11-25 19:39:02'),
(66, NULL, 5, 102, 'upload', 'Dokumen Baru', '<strong>admin</strong> mengunggah dokumen: \"Matematika Diskrit\"', 'info', 'bi-file-earmark-plus', NULL, 0, '2025-11-25 19:43:05'),
(67, 5, 5, 102, 'upload_confirm', 'Upload Berhasil', 'Dokumen \"Matematika Diskrit\" berhasil diunggah.', 'success', 'bi-check-circle-fill', NULL, 0, '2025-11-25 19:43:05'),
(68, NULL, 5, 103, 'upload', 'Dokumen Baru', '<strong>admin</strong> mengunggah dokumen: \"Matematika Diskrit\"', 'info', 'bi-file-earmark-plus', NULL, 0, '2025-11-25 19:43:51'),
(69, 5, 5, 103, 'upload_confirm', 'Upload Berhasil', 'Dokumen \"Matematika Diskrit\" berhasil diunggah.', 'success', 'bi-check-circle-fill', NULL, 0, '2025-11-25 19:43:51'),
(70, NULL, 10, 104, 'upload', 'Dokumen Baru', '<strong>admin02</strong> mengunggah dokumen: \"Analisis Teknologi IoT\"', 'info', 'bi-file-earmark-plus', NULL, 0, '2025-11-30 21:16:59'),
(71, 10, 10, 104, 'upload_confirm', 'Upload Berhasil', 'Dokumen \"Analisis Teknologi IoT\" berhasil diunggah.', 'success', 'bi-check-circle-fill', NULL, 0, '2025-11-30 21:16:59'),
(72, NULL, 10, 105, 'upload', 'Dokumen Baru', '<strong>admin02</strong> mengunggah dokumen: \"Uji Kualitas Mobile SIPORA\"', 'info', 'bi-file-earmark-plus', NULL, 0, '2025-11-30 21:21:22'),
(73, 10, 10, 105, 'upload_confirm', 'Upload Berhasil', 'Dokumen \"Uji Kualitas Mobile SIPORA\" berhasil diunggah.', 'success', 'bi-check-circle-fill', NULL, 0, '2025-11-30 21:21:22'),
(74, NULL, 10, 106, 'upload', 'Dokumen Baru', '<strong>admin02</strong> mengunggah dokumen: \"Pengujian Website SIPORA\"', 'info', 'bi-file-earmark-plus', NULL, 0, '2025-11-30 21:27:53'),
(75, 10, 10, 106, 'upload_confirm', 'Upload Berhasil', 'Dokumen \"Pengujian Website SIPORA\" berhasil diunggah.', 'success', 'bi-check-circle-fill', NULL, 0, '2025-11-30 21:27:53'),
(76, NULL, 10, 107, 'upload', 'Dokumen Baru', '<strong>admin02</strong> mengunggah dokumen: \"Pengujian Web SLearn\"', 'info', 'bi-file-earmark-plus', NULL, 0, '2025-11-30 22:06:34'),
(77, 10, 10, 107, 'upload_confirm', 'Upload Berhasil', 'Dokumen \"Pengujian Web SLearn\" berhasil diunggah.', 'success', 'bi-check-circle-fill', NULL, 0, '2025-11-30 22:06:34'),
(78, NULL, 10, 108, 'upload', 'Dokumen Baru', '<strong>admin02</strong> mengunggah dokumen: \"TOPOLOGI JARINGAN SMK 2\"', 'info', 'bi-file-earmark-plus', NULL, 0, '2025-11-30 22:07:38'),
(79, 10, 10, 108, 'upload_confirm', 'Upload Berhasil', 'Dokumen \"TOPOLOGI JARINGAN SMK 2\" berhasil diunggah.', 'success', 'bi-check-circle-fill', NULL, 0, '2025-11-30 22:07:38'),
(80, NULL, 10, 109, 'upload', 'Dokumen Baru', '<strong>admin02</strong> mengunggah dokumen: \"Pengujian Website SIPORA POLIJE\"', 'info', 'bi-file-earmark-plus', NULL, 0, '2025-12-01 08:31:52'),
(81, 10, 10, 109, 'upload_confirm', 'Upload Berhasil', 'Dokumen \"Pengujian Website SIPORA POLIJE\" berhasil diunggah.', 'success', 'bi-check-circle-fill', NULL, 0, '2025-12-01 08:31:52'),
(82, NULL, 10, 110, 'upload', 'Dokumen Baru', '<strong>admin02</strong> mengunggah dokumen: \"Pengujian Web SIPORA POLIJE\"', 'info', 'bi-file-earmark-plus', NULL, 0, '2025-12-01 08:45:59'),
(83, 10, 10, 110, 'upload_confirm', 'Upload Berhasil', 'Dokumen \"Pengujian Web SIPORA POLIJE\" berhasil diunggah.', 'success', 'bi-check-circle-fill', NULL, 0, '2025-12-01 08:45:59'),
(84, NULL, 10, 112, 'upload', 'Dokumen Baru', '<strong>Rizal</strong> mengunggah dokumen: \"Algoritma dan Pemrograman\"', 'info', 'bi-file-earmark-plus', NULL, 0, '2025-12-02 13:27:30'),
(85, 10, 10, 112, 'upload_confirm', 'Upload Berhasil', 'Dokumen \"Algoritma dan Pemrograman\" berhasil diunggah.', 'success', 'bi-check-circle-fill', NULL, 0, '2025-12-02 13:27:30'),
(86, NULL, 10, 114, 'upload', 'Dokumen Baru', '<strong>Rizal</strong> mengunggah dokumen: \"Algoritma dan Pemrograman\"', 'info', 'bi-file-earmark-plus', NULL, 0, '2025-12-02 17:55:50'),
(87, 10, 10, 114, 'upload_confirm', 'Upload Berhasil', 'Dokumen \"Algoritma dan Pemrograman\" berhasil diunggah.', 'success', 'bi-check-circle-fill', NULL, 0, '2025-12-02 17:55:50'),
(88, NULL, 10, 115, 'upload', 'Dokumen Baru', '<strong>Rizal</strong> mengunggah dokumen: \"Uji Kualitas Mobile SIPORA\"', 'info', 'bi-file-earmark-plus', NULL, 0, '2025-12-02 17:56:45'),
(89, 10, 10, 115, 'upload_confirm', 'Upload Berhasil', 'Dokumen \"Uji Kualitas Mobile SIPORA\" berhasil diunggah.', 'success', 'bi-check-circle-fill', NULL, 0, '2025-12-02 17:56:45'),
(90, NULL, 10, 116, 'upload', 'Dokumen Baru', '<strong>Rizal</strong> mengunggah dokumen: \"Menyelami Dunia Kecerdasan Buatan\"', 'info', 'bi-file-earmark-plus', NULL, 0, '2025-12-02 18:04:46'),
(91, 10, 10, 116, 'upload_confirm', 'Upload Berhasil', 'Dokumen \"Menyelami Dunia Kecerdasan Buatan\" berhasil diunggah.', 'success', 'bi-check-circle-fill', NULL, 0, '2025-12-02 18:04:46'),
(92, NULL, 10, 117, 'upload', 'Dokumen Baru', '<strong>Rizal</strong> mengunggah dokumen: \"Menyelami Dunia Kecerdasan Buatan\"', 'info', 'bi-file-earmark-plus', NULL, 0, '2025-12-02 18:09:45'),
(93, 10, 10, 117, 'upload_confirm', 'Upload Berhasil', 'Dokumen \"Menyelami Dunia Kecerdasan Buatan\" berhasil diunggah.', 'success', 'bi-check-circle-fill', NULL, 0, '2025-12-02 18:09:45'),
(94, NULL, 10, 118, 'upload', 'Dokumen Baru', '<strong>Rizal</strong> mengunggah dokumen: \"Menyelami Dunia Kecerdasan Buatan\"', 'info', 'bi-file-earmark-plus', NULL, 0, '2025-12-02 18:09:58'),
(95, 10, 10, 118, 'upload_confirm', 'Upload Berhasil', 'Dokumen \"Menyelami Dunia Kecerdasan Buatan\" berhasil diunggah.', 'success', 'bi-check-circle-fill', NULL, 0, '2025-12-02 18:09:58'),
(96, NULL, 10, 119, 'upload', 'Dokumen Baru', '<strong>Rizal</strong> mengunggah dokumen: \"Menyelami Dunia Kecerdasan Buatan\"', 'info', 'bi-file-earmark-plus', NULL, 0, '2025-12-02 18:10:25'),
(97, 10, 10, 119, 'upload_confirm', 'Upload Berhasil', 'Dokumen \"Menyelami Dunia Kecerdasan Buatan\" berhasil diunggah.', 'success', 'bi-check-circle-fill', NULL, 0, '2025-12-02 18:10:25'),
(98, NULL, 10, 120, 'upload', 'Dokumen Baru', '<strong>Rizal</strong> mengunggah dokumen: \"Menyelami Dunia Kecerdasan Buatan\"', 'info', 'bi-file-earmark-plus', NULL, 0, '2025-12-02 18:10:48'),
(99, 10, 10, 120, 'upload_confirm', 'Upload Berhasil', 'Dokumen \"Menyelami Dunia Kecerdasan Buatan\" berhasil diunggah.', 'success', 'bi-check-circle-fill', NULL, 0, '2025-12-02 18:10:48'),
(100, NULL, 10, 121, 'upload', 'Dokumen Baru', '<strong>Rizal</strong> mengunggah dokumen: \"Menyelami Dunia Kecerdasan Buatan\"', 'info', 'bi-file-earmark-plus', NULL, 0, '2025-12-02 18:10:59'),
(101, 10, 10, 121, 'upload_confirm', 'Upload Berhasil', 'Dokumen \"Menyelami Dunia Kecerdasan Buatan\" berhasil diunggah.', 'success', 'bi-check-circle-fill', NULL, 0, '2025-12-02 18:10:59'),
(102, NULL, 10, 122, 'upload', 'Dokumen Baru', '<strong>Rizal</strong> mengunggah dokumen: \"Laporan akhir\"', 'info', 'bi-file-earmark-plus', NULL, 0, '2025-12-02 18:40:47'),
(103, 10, 10, 122, 'upload_confirm', 'Upload Berhasil', 'Dokumen \"Laporan akhir\" berhasil diunggah.', 'success', 'bi-check-circle-fill', NULL, 0, '2025-12-02 18:40:47'),
(104, NULL, 10, 123, 'upload', 'Dokumen Baru', '<strong>Rizal</strong> mengunggah dokumen: \"Topologi jaringan\"', 'info', 'bi-file-earmark-plus', NULL, 0, '2025-12-02 18:43:29'),
(105, 10, 10, 123, 'upload_confirm', 'Upload Berhasil', 'Dokumen \"Topologi jaringan\" berhasil diunggah.', 'success', 'bi-check-circle-fill', NULL, 0, '2025-12-02 18:43:29'),
(106, NULL, 25, 124, 'upload', 'Dokumen Baru', '<strong>Robith</strong> mengunggah dokumen: \"sistem jaringan pada kopi\"', 'info', 'bi-file-earmark-plus', NULL, 0, '2025-12-02 18:47:39'),
(107, 25, 25, 124, 'upload_confirm', 'Upload Berhasil', 'Dokumen \"sistem jaringan pada kopi\" berhasil diunggah.', 'success', 'bi-check-circle-fill', NULL, 0, '2025-12-02 18:47:39'),
(108, NULL, 25, 125, 'upload', 'Dokumen Baru', '<strong>Robith</strong> mengunggah dokumen: \"Otomotif kendaraan hemat energi\"', 'info', 'bi-file-earmark-plus', NULL, 0, '2025-12-02 18:49:30'),
(109, 25, 25, 125, 'upload_confirm', 'Upload Berhasil', 'Dokumen \"Otomotif kendaraan hemat energi\" berhasil diunggah.', 'success', 'bi-check-circle-fill', NULL, 0, '2025-12-02 18:49:30'),
(110, NULL, 25, 126, 'upload', 'Dokumen Baru', '<strong>Robith</strong> mengunggah dokumen: \"management kasir\"', 'info', 'bi-file-earmark-plus', NULL, 0, '2025-12-02 18:50:54'),
(111, 25, 25, 126, 'upload_confirm', 'Upload Berhasil', 'Dokumen \"management kasir\" berhasil diunggah.', 'success', 'bi-check-circle-fill', NULL, 0, '2025-12-02 18:50:54'),
(112, NULL, 10, 127, 'upload', 'Dokumen Baru', '<strong>Rizal</strong> mengunggah dokumen: \"sistem jaringan pada kopi\"', 'info', 'bi-file-earmark-plus', NULL, 0, '2025-12-03 11:50:47'),
(113, 10, 10, 127, 'upload_confirm', 'Upload Berhasil', 'Dokumen \"sistem jaringan pada kopi\" berhasil diunggah.', 'success', 'bi-check-circle-fill', NULL, 0, '2025-12-03 11:50:47');

-- --------------------------------------------------------

--
-- Table structure for table `notifikasi`
--

CREATE TABLE `notifikasi` (
  `id_notif` int NOT NULL,
  `user_id` int NOT NULL,
  `judul` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `isi` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `status` enum('unread','read') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'unread',
  `waktu` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifikasi`
--

INSERT INTO `notifikasi` (`id_notif`, `user_id`, `judul`, `isi`, `status`, `waktu`) VALUES
(10, 6, 'Dokumen Disetujui', 'Dokumen \'sisi\' telah diperbarui statusnya.', 'unread', '2025-11-04 07:29:25'),
(11, 10, 'Dokumen Publikasi', 'Dokumen \'Matematika Diskrit\' telah diperbarui statusnya menjadi Publikasi.', 'unread', '2025-11-11 02:24:38'),
(12, 10, 'Dokumen Disetujui', 'Dokumen \'Computer Jaringan\' telah diperbarui statusnya menjadi Disetujui.', 'unread', '2025-11-11 02:47:29'),
(13, 10, 'Dokumen Publikasi', 'Dokumen \'Computer Jaringan\' telah diperbarui statusnya menjadi Publikasi.', 'unread', '2025-11-11 02:48:14'),
(14, 10, 'Dokumen Publikasi', 'Dokumen \'Computer Jaringan\' telah diperbarui statusnya menjadi Publikasi.', 'unread', '2025-11-11 02:48:19'),
(15, 10, 'Dokumen Disetujui', 'Dokumen \'Computer\' telah diperbarui statusnya menjadi Disetujui.', 'unread', '2025-11-11 03:07:37'),
(16, 10, 'Dokumen Disetujui', 'Dokumen \'Computer\' telah diperbarui statusnya menjadi Disetujui.', 'unread', '2025-11-13 01:21:29'),
(17, 10, 'Dokumen Disetujui', 'Dokumen \'Computer\' telah diperbarui statusnya menjadi Disetujui.', 'unread', '2025-11-13 01:21:33'),
(18, 10, 'Dokumen Disetujui', 'Dokumen \'Computer\' telah diperbarui statusnya menjadi Disetujui.', 'unread', '2025-11-13 01:21:36'),
(19, 10, 'Dokumen Disetujui', 'Dokumen \'Computer\' telah diperbarui statusnya menjadi Disetujui.', 'unread', '2025-11-13 01:21:40'),
(20, 10, 'Dokumen Disetujui', 'Dokumen \'Computer\' telah diperbarui statusnya menjadi Disetujui.', 'unread', '2025-11-13 01:21:44'),
(21, 10, 'Dokumen Publikasi', 'Dokumen \'Computer\' telah diperbarui statusnya menjadi Publikasi.', 'unread', '2025-11-13 01:21:54'),
(22, 10, 'Dokumen Disetujui', 'Dokumen \'Algoritma dan Pemrograman\' telah diperbarui statusnya menjadi Disetujui.', 'unread', '2025-11-16 13:43:01'),
(23, 10, 'Dokumen Disetujui', 'Dokumen \'Kewarganegaraan\' telah diperbarui statusnya menjadi Disetujui.', 'unread', '2025-11-16 13:47:46'),
(24, 10, 'Dokumen Disetujui', 'Dokumen \'Kewarganegaraan\' telah diperbarui statusnya menjadi Disetujui.', 'unread', '2025-11-16 13:47:51'),
(25, 10, 'Dokumen Publikasi', 'Dokumen \'Kewarganegaraan\' telah diperbarui statusnya menjadi Publikasi.', 'unread', '2025-11-16 13:48:37'),
(26, 10, 'Dokumen Disetujui', 'Dokumen \'Algoritma dan Pemrograman\' telah diperbarui statusnya menjadi Disetujui.', 'unread', '2025-11-16 14:12:47'),
(27, 10, 'Dokumen Publikasi', 'Dokumen \'Algoritma dan Pemrograman\' telah diperbarui statusnya menjadi Publikasi.', 'unread', '2025-11-16 14:12:57'),
(28, 10, 'Dokumen Publikasi', 'Dokumen \'Algoritma dan Pemrograman\' telah diperbarui statusnya menjadi Publikasi.', 'unread', '2025-11-17 01:09:54'),
(29, 10, 'Dokumen Disetujui', 'Dokumen \'sibal\' telah diperbarui statusnya menjadi Disetujui.', 'unread', '2025-11-18 06:37:45'),
(30, 10, 'Dokumen Publikasi', 'Dokumen \'sibal\' telah diperbarui statusnya menjadi Publikasi.', 'unread', '2025-11-18 06:38:01'),
(31, 10, 'Dokumen Ditolak', 'Dokumen \'Algoritma dan Pemrograman\' telah diperbarui statusnya menjadi Ditolak.', 'unread', '2025-11-18 06:40:45'),
(32, 10, 'Dokumen Ditolak', 'Dokumen \'Computer Networking\' telah diperbarui statusnya menjadi Ditolak.', 'unread', '2025-11-18 06:45:55'),
(33, 10, 'Dokumen Disetujui', 'Dokumen \'Algoritma dan Pemrograman\' telah berubah status menjadi Disetujui.', 'unread', '2025-11-20 13:49:52'),
(34, 10, 'Dokumen Publikasi', 'Dokumen \'Algoritma dan Pemrograman\' telah berubah status menjadi Publikasi.', 'unread', '2025-11-20 13:50:03'),
(35, 10, 'Dokumen Disetujui', 'Dokumen \'Matematika Diskrit\' telah berubah status menjadi Disetujui.', 'unread', '2025-11-21 02:34:31'),
(36, 10, 'Dokumen Disetujui', 'Dokumen \'Matematika Diskrit\' telah berubah status menjadi Disetujui.', 'unread', '2025-11-21 02:34:36'),
(37, 10, 'Dokumen Publikasi', 'Dokumen \'Matematika Diskrit\' telah berubah status menjadi Publikasi.', 'unread', '2025-11-21 02:34:46'),
(38, 5, 'Dokumen Disetujui', 'Dokumen \'Algoritma dan Pemrograman\' telah berubah status menjadi Disetujui.', 'unread', '2025-11-21 02:39:26'),
(39, 5, 'Dokumen Disetujui', 'Dokumen \'Matematika Diskrit\' telah berubah status menjadi Disetujui.', 'unread', '2025-11-21 02:39:36'),
(40, 5, 'Dokumen Publikasi', 'Dokumen \'Algoritma dan Pemrograman\' telah berubah status menjadi Publikasi.', 'unread', '2025-11-21 02:39:44'),
(41, 5, 'Dokumen Publikasi', 'Dokumen \'Algoritma dan Pemrograman\' telah berubah status menjadi Publikasi.', 'unread', '2025-11-21 02:39:52'),
(42, 10, 'Dokumen Disetujui', 'Dokumen \'Computer\' telah berubah status menjadi Disetujui.', 'unread', '2025-11-24 00:28:25'),
(43, 5, 'Dokumen Disetujui', 'Dokumen \'Algoritma dan Pemrograman\' telah berubah status menjadi Disetujui.', 'unread', '2025-11-24 01:12:34'),
(44, 5, 'Dokumen Disetujui', 'Dokumen \'Algoritma dan Pemrograman\' telah berubah status menjadi Disetujui.', 'unread', '2025-11-24 01:12:38'),
(45, 5, 'Dokumen Disetujui', 'Dokumen \'Matematika Diskrit\' telah berubah status menjadi Disetujui.', 'unread', '2025-11-24 03:47:42'),
(46, 5, 'Dokumen Ditolak', 'Dokumen \'Computer\' telah berubah status menjadi Ditolak.', 'unread', '2025-11-24 03:47:56'),
(47, 5, 'Dokumen Ditolak', 'Dokumen \'Computer\' telah berubah status menjadi Ditolak.', 'unread', '2025-11-24 03:47:59'),
(48, 5, 'Dokumen Disetujui', 'Dokumen \'Computer\' telah berubah status menjadi Disetujui.', 'unread', '2025-11-24 03:48:06'),
(49, 10, 'Dokumen Disetujui', 'Dokumen \'sibal\' telah berubah status menjadi Disetujui.', 'unread', '2025-11-25 12:27:50'),
(50, 10, 'Dokumen Publikasi', 'Dokumen \'sibal\' telah berubah status menjadi Publikasi.', 'unread', '2025-11-25 12:28:00'),
(51, 10, 'Dokumen Disetujui', 'Dokumen \'Pengujian Website SIPORA\' telah diperbarui statusnya menjadi Disetujui.', 'unread', '2025-11-30 14:37:35'),
(52, 10, 'Dokumen Disetujui', 'Dokumen \'Uji Kualitas Mobile SIPORA\' telah diperbarui statusnya menjadi Disetujui.', 'unread', '2025-11-30 14:37:55'),
(53, 10, 'Dokumen Disetujui', 'Dokumen \'Analisis Teknologi IoT\' telah diperbarui statusnya menjadi Disetujui.', 'unread', '2025-11-30 14:38:04'),
(54, 10, 'Dokumen Publikasi', 'Dokumen \'Analisis Teknologi IoT\' telah diperbarui statusnya menjadi Publikasi.', 'unread', '2025-11-30 14:46:00'),
(55, 10, 'Dokumen Publikasi', 'Dokumen \'Uji Kualitas Mobile SIPORA\' telah diperbarui statusnya menjadi Publikasi.', 'unread', '2025-11-30 14:46:05'),
(56, 10, 'Dokumen Publikasi', 'Dokumen \'Pengujian Website SIPORA\' telah diperbarui statusnya menjadi Publikasi.', 'unread', '2025-11-30 14:46:08'),
(57, 10, 'Dokumen Disetujui', 'Dokumen \'Pengujian Web SLearn\' telah diperbarui statusnya menjadi Disetujui.', 'unread', '2025-11-30 15:09:34'),
(58, 10, 'Dokumen Disetujui', 'Dokumen \'Pengujian Website SIPORA POLIJE\' telah diperbarui statusnya menjadi Disetujui.', 'unread', '2025-12-01 01:34:26'),
(59, 10, 'Dokumen Publikasi', 'Dokumen \'Pengujian Website SIPORA POLIJE\' telah diperbarui statusnya menjadi Publikasi.', 'unread', '2025-12-01 01:35:35'),
(60, 10, 'Dokumen Disetujui', 'Dokumen \'Algoritma dan Pemrograman\' telah diperbarui statusnya menjadi Disetujui.', 'unread', '2025-12-02 06:27:42'),
(61, 10, 'Dokumen Publikasi', 'Dokumen \'Algoritma dan Pemrograman\' telah diperbarui statusnya menjadi Publikasi.', 'unread', '2025-12-02 06:52:40'),
(62, 10, 'Dokumen Publikasi', 'Dokumen \'Algoritma dan Pemrograman\' telah diperbarui statusnya menjadi Publikasi.', 'unread', '2025-12-02 06:54:54'),
(63, 10, 'Dokumen Disetujui', 'Dokumen \'Menyelami Dunia Kecerdasan Buatan\' telah diperbarui statusnya menjadi Disetujui.', 'unread', '2025-12-02 11:26:31'),
(64, 10, 'Dokumen Disetujui', 'Dokumen \'Memahami Perubahan Iklim dan Dampaknya\' telah diperbarui statusnya menjadi Disetujui.', 'unread', '2025-12-02 11:26:37'),
(65, 10, 'Dokumen Disetujui', 'Dokumen \'Cara Efektif Mengelola Waktu di Era Digital\' telah diperbarui statusnya menjadi Disetujui.', 'unread', '2025-12-02 11:26:41'),
(66, 10, 'Dokumen Disetujui', 'Dokumen \'Menyelami Dunia Kecerdasan Buatan\' telah diperbarui statusnya menjadi Disetujui.', 'unread', '2025-12-02 11:26:45'),
(67, 10, 'Dokumen Disetujui', 'Dokumen \'Sejarah Perkembangan Internet\' telah diperbarui statusnya menjadi Disetujui.', 'unread', '2025-12-02 11:26:49'),
(68, 10, 'Dokumen Disetujui', 'Dokumen \'Seni Fotografi Digital: Dasar dan Teknik Kreatif\' telah diperbarui statusnya menjadi Disetujui.', 'unread', '2025-12-02 11:26:52'),
(69, 10, 'Dokumen Disetujui', 'Dokumen \'Panduan Memulai Investasi untuk Pemula\' telah diperbarui statusnya menjadi Disetujui.', 'unread', '2025-12-02 11:26:56'),
(70, 10, 'Dokumen Disetujui', 'Dokumen \'Pentingnya Energi Terbarukan untuk Masa Depan\' telah diperbarui statusnya menjadi Disetujui.', 'unread', '2025-12-02 11:27:00'),
(71, 10, 'Dokumen Publikasi', 'Dokumen \'Menyelami Dunia Kecerdasan Buatan\' telah diperbarui statusnya menjadi Publikasi.', 'unread', '2025-12-02 11:27:47'),
(72, 10, 'Dokumen Publikasi', 'Dokumen \'Memahami Perubahan Iklim dan Dampaknya\' telah diperbarui statusnya menjadi Publikasi.', 'unread', '2025-12-02 11:27:52'),
(73, 10, 'Dokumen Publikasi', 'Dokumen \'Cara Efektif Mengelola Waktu di Era Digital\' telah diperbarui statusnya menjadi Publikasi.', 'unread', '2025-12-02 11:27:57'),
(74, 10, 'Dokumen Publikasi', 'Dokumen \'Menyelami Dunia Kecerdasan Buatan\' telah diperbarui statusnya menjadi Publikasi.', 'unread', '2025-12-02 11:28:01'),
(75, 10, 'Dokumen Publikasi', 'Dokumen \'Sejarah Perkembangan Internet\' telah diperbarui statusnya menjadi Publikasi.', 'unread', '2025-12-02 11:28:06'),
(76, 10, 'Dokumen Publikasi', 'Dokumen \'Seni Fotografi Digital: Dasar dan Teknik Kreatif\' telah diperbarui statusnya menjadi Publikasi.', 'unread', '2025-12-02 11:28:11'),
(77, 10, 'Dokumen Publikasi', 'Dokumen \'Panduan Memulai Investasi untuk Pemula\' telah diperbarui statusnya menjadi Publikasi.', 'unread', '2025-12-02 11:28:15'),
(78, 10, 'Dokumen Publikasi', 'Dokumen \'Panduan Memulai Investasi untuk Pemula\' telah diperbarui statusnya menjadi Publikasi.', 'unread', '2025-12-02 11:28:19'),
(79, 10, 'Dokumen Publikasi', 'Dokumen \'Pentingnya Energi Terbarukan untuk Masa Depan\' telah diperbarui statusnya menjadi Publikasi.', 'unread', '2025-12-02 11:28:23'),
(80, 25, 'Dokumen Disetujui', 'Dokumen \'management kasir\' telah diperbarui statusnya menjadi Disetujui.', 'unread', '2025-12-02 11:52:48'),
(81, 25, 'Dokumen Publikasi', 'Dokumen \'management kasir\' telah diperbarui statusnya menjadi Publikasi.', 'unread', '2025-12-02 11:53:06'),
(82, 10, 'Dokumen Disetujui', 'Dokumen \'Laporan akhir\' telah diperbarui statusnya menjadi Disetujui.', 'unread', '2025-12-02 11:53:25'),
(83, 10, 'Dokumen Publikasi', 'Dokumen \'Laporan akhir\' telah diperbarui statusnya menjadi Publikasi.', 'unread', '2025-12-02 11:53:43'),
(84, 25, 'Dokumen Disetujui', 'Dokumen \'sistem jaringan pada kopi\' telah diperbarui statusnya menjadi Disetujui.', 'unread', '2025-12-02 11:54:09'),
(85, 10, 'Dokumen Ditolak', 'Dokumen \'Topologi jaringan\' telah diperbarui statusnya menjadi Ditolak.', 'unread', '2025-12-02 11:55:18'),
(86, 10, 'Dokumen Disetujui', 'Dokumen \'sistem jaringan pada kopi\' telah diperbarui statusnya menjadi Disetujui.', 'unread', '2025-12-03 12:37:58'),
(87, 10, 'Dokumen Publikasi', 'Dokumen \'sistem jaringan pada kopi\' telah diperbarui statusnya menjadi Publikasi.', 'unread', '2025-12-03 12:38:17');

-- --------------------------------------------------------

--
-- Table structure for table `search_history`
--

CREATE TABLE `search_history` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `keyword` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `search_history`
--

INSERT INTO `search_history` (`id`, `user_id`, `keyword`, `created_at`, `deleted_at`) VALUES
(1, 14, 'analisis', '2025-11-28 09:24:40', '2025-11-28 16:25:06'),
(2, 14, 'analisis', '2025-11-28 07:46:24', '2025-11-28 16:25:06'),
(3, 14, 'talitha', '2025-11-28 09:24:29', '2025-11-28 16:24:35'),
(4, 14, 'talitha', '2025-11-28 07:46:56', '2025-11-28 16:24:35'),
(5, 14, 'talitha', '2025-11-28 07:56:31', '2025-11-28 16:24:35'),
(6, 14, 'talitha', '2025-11-28 07:56:32', '2025-11-28 16:24:35'),
(7, 14, 'analisis', '2025-11-28 07:56:42', '2025-11-28 16:25:06'),
(8, 14, 'analisis', '2025-11-28 07:56:56', '2025-11-28 16:25:06'),
(9, 14, 'talitha', '2025-11-28 07:57:10', '2025-11-28 16:24:35'),
(10, 14, 'ayam', '2025-11-28 07:57:19', '2025-11-28 16:24:35'),
(11, 14, 'analisis', '2025-11-28 07:58:10', '2025-11-28 16:25:06'),
(12, 14, 'analisis', '2025-11-28 08:32:51', '2025-11-28 16:25:06'),
(13, 14, 'talitha', '2025-11-28 08:33:01', '2025-11-28 16:24:35'),
(14, 14, 'analisis', '2025-11-28 08:33:10', '2025-11-28 16:25:06'),
(15, 14, 'analisis', '2025-11-28 08:40:38', '2025-11-28 16:25:06'),
(16, 14, 'analisis', '2025-11-28 08:41:11', '2025-11-28 16:25:06'),
(17, 14, 'analisis', '2025-11-28 08:46:04', '2025-11-28 16:25:06'),
(18, 14, 'talitha', '2025-11-28 08:46:15', '2025-11-28 16:24:35'),
(19, 14, 'analisis', '2025-11-28 09:03:00', '2025-11-28 16:25:06'),
(20, 14, 'analisis', '2025-11-28 09:03:13', '2025-11-28 16:25:06'),
(21, 14, 'analisis', '2025-11-28 09:03:30', '2025-11-28 16:25:06'),
(22, 14, 'talitha', '2025-11-28 09:04:10', '2025-11-28 16:24:35'),
(23, 14, 'talitah', '2025-11-28 09:09:43', '2025-11-28 16:24:35'),
(24, 14, 'talitha', '2025-11-28 09:09:56', '2025-11-28 16:24:35'),
(25, 14, 'ayam', '2025-11-28 09:10:04', '2025-11-28 16:24:35'),
(26, 14, 'teknologi', '2025-11-28 09:12:02', '2025-11-28 16:24:35'),
(27, 14, 'manajemen', '2025-11-28 09:12:10', '2025-11-28 16:24:35');

-- --------------------------------------------------------

--
-- Table structure for table `trending_keywords`
--

CREATE TABLE `trending_keywords` (
  `id` int NOT NULL,
  `keyword` varchar(255) NOT NULL,
  `search_count` int DEFAULT '1',
  `last_searched` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `trending_keywords`
--

INSERT INTO `trending_keywords` (`id`, `keyword`, `search_count`, `last_searched`) VALUES
(1, 'talitha', 2, '2025-11-28 16:24:29'),
(2, 'analisis', 2, '2025-11-28 16:24:41');

-- --------------------------------------------------------

--
-- Table structure for table `turnitin`
--

CREATE TABLE `turnitin` (
  `id_turnitin` int NOT NULL,
  `id_divisi` int DEFAULT NULL,
  `turnitin_score` varchar(10) DEFAULT NULL,
  `turnitin_link` varchar(500) DEFAULT NULL,
  `file_turnitin` varchar(500) DEFAULT NULL,
  `uploader_id` int NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id_user` int NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `nim` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','mahasiswa') DEFAULT 'mahasiswa',
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id_user`, `nama_lengkap`, `nim`, `email`, `username`, `password_hash`, `role`, `status`, `created_at`) VALUES
(5, 'admin', 'H942233', 'e41240390@student.polije.ac.id', 'admin', '$2y$10$KJJbh9WcWDWD5lyP85Xdm.mb8/n7Dcbpe3NsCvaINJFxwsu3go.2m', 'admin', 'approved', '2025-11-04 04:42:11'),
(6, 'ratu', 'E41240153', 'e41240153@student.polije.ac.id', 'ratu', '$2y$10$rIdDm9eC0mlVWHvXF/2uG.DFmRmDnSZZNYzJyiqRAIv04TKvF79dy', 'mahasiswa', 'approved', '2025-11-04 04:44:13'),
(10, 'Rizal', '2323132', 'e41240390@student.polije.ac.id', 'Rizal', '$2y$10$FEiqYSzbj7fFVNbWwDE/leSdM6Gw3ZqtD7MnKaVi6ie4KKKF9wiNK', 'mahasiswa', 'approved', '2025-11-10 14:41:07'),
(14, 'Ratu Alyvia Meydiandra', '65528910408', 'adminratu@admin.polije.ac.id', 'adminratu', '$2y$10$gozXkJA0CnA83LH0wsFipebqwKbWXZQUvRGmnLx3u/ikdtIPLfWsC', 'admin', 'approved', '2025-11-27 01:41:12'),
(19, 'Talitha Syahla', 'E41240073', 'e41240073@student.polije.ac.id', 'TALITHASYAHLA', '$2y$10$XVHskcqxaNeK1ANH1yRfl.yfHPgC9rawPq.8yjOZCE0rSkP1C8Bam', 'mahasiswa', 'approved', '2025-11-30 14:03:21'),
(25, 'Tijani Robith', 'E41240108', 'e41240108@student.polije.ac.id', 'Robith', '$argon2id$v=19$m=65536,t=4,p=1$QzlMVVdVWFRyY2t5WXRlZw$z5OtBAuhrRDoAkXD9CU17MzxZ5PaSb78XW9jVlrl9Wo', 'mahasiswa', 'approved', '2025-12-02 11:44:38'),
(26, 'M. ANANG MA\'RUF', 'E41240259', 'e41240259@student.polije.ac.id', 'anang123', '$argon2id$v=19$m=65536,t=4,p=1$dEpVb1NLTXJjdlRPNmlUQw$uUPqStYiF39ljaTckuD+lHZYQ3wd+Lx/8mDpJoj932I', 'mahasiswa', 'approved', '2025-12-02 12:33:49');

-- --------------------------------------------------------

--
-- Table structure for table `user_profile`
--

CREATE TABLE `user_profile` (
  `id_profile` int NOT NULL,
  `id_user` int NOT NULL,
  `foto_profil` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `user_profile`
--

INSERT INTO `user_profile` (`id_profile`, `id_user`, `foto_profil`, `updated_at`) VALUES
(1, 5, 'profile_5.jpg', '2025-11-06 07:45:22'),
(2, 10, NULL, '2025-11-24 01:20:15'),
(3, 14, 'profile_14.png', '2025-12-02 09:07:30');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `dokumen`
--
ALTER TABLE `dokumen`
  ADD PRIMARY KEY (`dokumen_id`),
  ADD KEY `uploader_id` (`uploader_id`),
  ADD KEY `id_tema` (`id_tema`),
  ADD KEY `id_jurusan` (`id_jurusan`),
  ADD KEY `id_prodi` (`id_prodi`),
  ADD KEY `year_id` (`year_id`),
  ADD KEY `status_id` (`status_id`),
  ADD KEY `fk_dokumen_divisi` (`id_divisi`);

--
-- Indexes for table `dokumen_author`
--
ALTER TABLE `dokumen_author`
  ADD PRIMARY KEY (`dokumen_author_id`),
  ADD KEY `dokumen_id` (`dokumen_id`),
  ADD KEY `author_id` (`author_id`);

--
-- Indexes for table `dokumen_keyword`
--
ALTER TABLE `dokumen_keyword`
  ADD PRIMARY KEY (`dokumen_keyword_id`),
  ADD KEY `dokumen_id` (`dokumen_id`),
  ADD KEY `keyword_id` (`keyword_id`);

--
-- Indexes for table `log_review`
--
ALTER TABLE `log_review`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `dokumen_id` (`dokumen_id`),
  ADD KEY `reviewer_id` (`reviewer_id`),
  ADD KEY `status_sebelum` (`status_sebelum`),
  ADD KEY `status_sesudah` (`status_sesudah`);

--
-- Indexes for table `master_author`
--
ALTER TABLE `master_author`
  ADD PRIMARY KEY (`author_id`);

--
-- Indexes for table `master_divisi`
--
ALTER TABLE `master_divisi`
  ADD PRIMARY KEY (`id_divisi`);

--
-- Indexes for table `master_jurusan`
--
ALTER TABLE `master_jurusan`
  ADD PRIMARY KEY (`id_jurusan`),
  ADD KEY `id_rumpun` (`id_rumpun`);

--
-- Indexes for table `master_keyword`
--
ALTER TABLE `master_keyword`
  ADD PRIMARY KEY (`keyword_id`);

--
-- Indexes for table `master_prodi`
--
ALTER TABLE `master_prodi`
  ADD PRIMARY KEY (`id_prodi`),
  ADD KEY `id_jurusan` (`id_jurusan`);

--
-- Indexes for table `master_rumpun`
--
ALTER TABLE `master_rumpun`
  ADD PRIMARY KEY (`id_rumpun`);

--
-- Indexes for table `master_status`
--
ALTER TABLE `master_status`
  ADD PRIMARY KEY (`id_status`);

--
-- Indexes for table `master_status_dokumen`
--
ALTER TABLE `master_status_dokumen`
  ADD PRIMARY KEY (`status_id`);

--
-- Indexes for table `master_tahun`
--
ALTER TABLE `master_tahun`
  ADD PRIMARY KEY (`year_id`);

--
-- Indexes for table `master_tema`
--
ALTER TABLE `master_tema`
  ADD PRIMARY KEY (`id_tema`),
  ADD KEY `id_rumpun` (`id_rumpun`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifikasi`
--
ALTER TABLE `notifikasi`
  ADD PRIMARY KEY (`id_notif`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `search_history`
--
ALTER TABLE `search_history`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `trending_keywords`
--
ALTER TABLE `trending_keywords`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `keyword` (`keyword`);

--
-- Indexes for table `turnitin`
--
ALTER TABLE `turnitin`
  ADD PRIMARY KEY (`id_turnitin`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`);

--
-- Indexes for table `user_profile`
--
ALTER TABLE `user_profile`
  ADD PRIMARY KEY (`id_profile`),
  ADD KEY `id_user` (`id_user`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `dokumen`
--
ALTER TABLE `dokumen`
  MODIFY `dokumen_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=128;

--
-- AUTO_INCREMENT for table `dokumen_author`
--
ALTER TABLE `dokumen_author`
  MODIFY `dokumen_author_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dokumen_keyword`
--
ALTER TABLE `dokumen_keyword`
  MODIFY `dokumen_keyword_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `log_review`
--
ALTER TABLE `log_review`
  MODIFY `log_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=86;

--
-- AUTO_INCREMENT for table `master_author`
--
ALTER TABLE `master_author`
  MODIFY `author_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `master_divisi`
--
ALTER TABLE `master_divisi`
  MODIFY `id_divisi` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=265;

--
-- AUTO_INCREMENT for table `master_keyword`
--
ALTER TABLE `master_keyword`
  MODIFY `keyword_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `master_rumpun`
--
ALTER TABLE `master_rumpun`
  MODIFY `id_rumpun` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `master_status`
--
ALTER TABLE `master_status`
  MODIFY `id_status` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `master_status_dokumen`
--
ALTER TABLE `master_status_dokumen`
  MODIFY `status_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `master_tahun`
--
ALTER TABLE `master_tahun`
  MODIFY `year_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=242;

--
-- AUTO_INCREMENT for table `master_tema`
--
ALTER TABLE `master_tema`
  MODIFY `id_tema` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=114;

--
-- AUTO_INCREMENT for table `notifikasi`
--
ALTER TABLE `notifikasi`
  MODIFY `id_notif` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=88;

--
-- AUTO_INCREMENT for table `search_history`
--
ALTER TABLE `search_history`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `trending_keywords`
--
ALTER TABLE `trending_keywords`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `turnitin`
--
ALTER TABLE `turnitin`
  MODIFY `id_turnitin` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `user_profile`
--
ALTER TABLE `user_profile`
  MODIFY `id_profile` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `dokumen`
--
ALTER TABLE `dokumen`
  ADD CONSTRAINT `fk_dokumen_divisi` FOREIGN KEY (`id_divisi`) REFERENCES `master_divisi` (`id_divisi`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_dokumen_jurusan` FOREIGN KEY (`id_jurusan`) REFERENCES `master_jurusan` (`id_jurusan`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_dokumen_prodi` FOREIGN KEY (`id_prodi`) REFERENCES `master_prodi` (`id_prodi`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_dokumen_status` FOREIGN KEY (`status_id`) REFERENCES `master_status_dokumen` (`status_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_dokumen_tahun` FOREIGN KEY (`year_id`) REFERENCES `master_tahun` (`year_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_dokumen_tema` FOREIGN KEY (`id_tema`) REFERENCES `master_tema` (`id_tema`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_dokumen_uploader` FOREIGN KEY (`uploader_id`) REFERENCES `users` (`id_user`) ON DELETE CASCADE;

--
-- Constraints for table `dokumen_author`
--
ALTER TABLE `dokumen_author`
  ADD CONSTRAINT `dokumen_author_ibfk_2` FOREIGN KEY (`author_id`) REFERENCES `master_author` (`author_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_dokumen_author_author` FOREIGN KEY (`author_id`) REFERENCES `master_author` (`author_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_dokumen_author_dokumen` FOREIGN KEY (`dokumen_id`) REFERENCES `dokumen` (`dokumen_id`) ON DELETE CASCADE;

--
-- Constraints for table `dokumen_keyword`
--
ALTER TABLE `dokumen_keyword`
  ADD CONSTRAINT `fk_dokumen_keyword_dokumen` FOREIGN KEY (`dokumen_id`) REFERENCES `dokumen` (`dokumen_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_dokumen_keyword_keyword` FOREIGN KEY (`keyword_id`) REFERENCES `master_keyword` (`keyword_id`) ON DELETE CASCADE;

--
-- Constraints for table `log_review`
--
ALTER TABLE `log_review`
  ADD CONSTRAINT `fk_log_dokumen` FOREIGN KEY (`dokumen_id`) REFERENCES `dokumen` (`dokumen_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_log_reviewer` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`id_user`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_log_status_sebelum` FOREIGN KEY (`status_sebelum`) REFERENCES `master_status_dokumen` (`status_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_log_status_sesudah` FOREIGN KEY (`status_sesudah`) REFERENCES `master_status_dokumen` (`status_id`) ON DELETE SET NULL;

--
-- Constraints for table `master_prodi`
--
ALTER TABLE `master_prodi`
  ADD CONSTRAINT `master_prodi_ibfk_1` FOREIGN KEY (`id_jurusan`) REFERENCES `master_jurusan` (`id_jurusan`);

--
-- Constraints for table `master_tema`
--
ALTER TABLE `master_tema`
  ADD CONSTRAINT `master_tema_ibfk_1` FOREIGN KEY (`id_rumpun`) REFERENCES `master_rumpun` (`id_rumpun`);

--
-- Constraints for table `notifikasi`
--
ALTER TABLE `notifikasi`
  ADD CONSTRAINT `notifikasi_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id_user`);

--
-- Constraints for table `user_profile`
--
ALTER TABLE `user_profile`
  ADD CONSTRAINT `user_profile_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
