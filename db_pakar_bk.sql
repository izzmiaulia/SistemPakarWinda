-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jul 20, 2026 at 03:46 AM
-- Server version: 8.0.30
-- PHP Version: 8.3.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_pakar_bk`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id_admin` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `nama_sekolah` varchar(100) DEFAULT NULL,
  `logo_sekolah` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id_admin`, `username`, `password`, `nama_lengkap`, `nama_sekolah`, `logo_sekolah`) VALUES
(1, 'gurubk', '$2y$10$ugXwa8swz1WZvorleI/IX.qA5bTBocTgfJUhfI7V89Xyz9rPBQ/t2', 'Guru BK (Admin)', 'SDN 4 BANJAR', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `aturan`
--

CREATE TABLE `aturan` (
  `id_aturan` int NOT NULL,
  `id_masalah` int NOT NULL,
  `id_gejala` int NOT NULL,
  `nilai_belief` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `aturan`
--

INSERT INTO `aturan` (`id_aturan`, `id_masalah`, `id_gejala`, `nilai_belief`) VALUES
(1, 1, 1, 0.6),
(2, 1, 2, 0.5),
(3, 1, 5, 0.8),
(4, 2, 2, 0.4),
(5, 2, 3, 0.7),
(6, 2, 4, 0.8),
(7, 3, 1, 0.4),
(8, 3, 2, 0.7),
(10, 1, 1, 0.8);

-- --------------------------------------------------------

--
-- Table structure for table `detail_konsultasi`
--

CREATE TABLE `detail_konsultasi` (
  `id_detail` int NOT NULL,
  `id_konsultasi` int NOT NULL,
  `id_gejala` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `detail_konsultasi`
--

INSERT INTO `detail_konsultasi` (`id_detail`, `id_konsultasi`, `id_gejala`) VALUES
(49, 27, 2),
(50, 27, 3),
(51, 27, 4),
(52, 28, 2),
(53, 28, 3),
(54, 28, 4),
(55, 29, 2),
(56, 29, 3);

-- --------------------------------------------------------

--
-- Table structure for table `gejala`
--

CREATE TABLE `gejala` (
  `id_gejala` int NOT NULL,
  `kode_gejala` varchar(10) NOT NULL,
  `nama_gejala` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gejala`
--

INSERT INTO `gejala` (`id_gejala`, `kode_gejala`, `nama_gejala`) VALUES
(1, 'G01', 'Sering mengantuk atau tidur di dalam kelas'),
(2, 'G02', 'Nilai akademik menurun secara drastis'),
(3, 'G03', 'Suka menyendiri dan menarik diri dari pergaulan teman sebaya'),
(4, 'G04', 'Terlihat cemas, ketakutan, atau gelisah saat berada di sekolah'),
(5, 'G05', 'Mata kemerahan dan tampak sangat lelah');

-- --------------------------------------------------------

--
-- Table structure for table `hasil_konsultasi`
--

CREATE TABLE `hasil_konsultasi` (
  `id_hasil` int NOT NULL,
  `id_konsultasi` int NOT NULL,
  `id_masalah` int NOT NULL,
  `nilai_persentase` float NOT NULL,
  `log_proses` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hasil_konsultasi`
--

INSERT INTO `hasil_konsultasi` (`id_hasil`, `id_konsultasi`, `id_masalah`, `nilai_persentase`, `log_proses`) VALUES
(27, 27, 2, 96.4, 'Sistem memulai pelacakan Backward Chaining untuk membuktikan dugaan awal (Goal): <strong>[M01] Kecanduan Gadget/Game Online</strong>.\nMenanyakan gejala <strong>G01</strong>: \"Sering mengantuk atau tidur di dalam kelas\". Jawaban: <span class=\'text-slate-400 font-bold\'>TIDAK</span>.\nHipotesis <strong>[M01] Kecanduan Gadget/Game Online</strong> dinyatakan <span class=\'text-red-600 font-bold\'>GUGUR</span> karena gejala yang terpenuhi tidak mencukupi.\nSistem melakukan <strong>Backtracking</strong>. Mengalihkan pengujian ke hipotesis baru: <strong>[M02] Mengalami Perundungan (Bullying)</strong>.\nMenanyakan gejala <strong>G02</strong>: \"Nilai akademik menurun secara drastis\". Jawaban: <span class=\'text-emerald-600 font-bold\'>YA</span>.\nMenanyakan gejala <strong>G03</strong>: \"Suka menyendiri dan menarik diri dari pergaulan teman sebaya\". Jawaban: <span class=\'text-emerald-600 font-bold\'>YA</span>.\nMenanyakan gejala <strong>G04</strong>: \"Terlihat cemas, ketakutan, atau gelisah saat berada di sekolah\". Jawaban: <span class=\'text-emerald-600 font-bold\'>YA</span>.'),
(28, 28, 2, 96.4, NULL),
(29, 29, 2, 82, 'Sistem memulai pelacakan Backward Chaining untuk membuktikan dugaan awal (Goal): <strong>[M02] Mengalami Perundungan (Bullying)</strong>.\nMenanyakan gejala <strong>G02</strong>: \"Nilai akademik menurun secara drastis\". Jawaban: <span class=\'text-emerald-600 font-bold\'>YA</span>.\nMenanyakan gejala <strong>G03</strong>: \"Suka menyendiri dan menarik diri dari pergaulan teman sebaya\". Jawaban: <span class=\'text-emerald-600 font-bold\'>YA</span>.\nMenanyakan gejala <strong>G04</strong>: \"Terlihat cemas, ketakutan, atau gelisah saat berada di sekolah\". Jawaban: <span class=\'text-slate-400 font-bold\'>TIDAK</span>.');

-- --------------------------------------------------------

--
-- Table structure for table `masalah`
--

CREATE TABLE `masalah` (
  `id_masalah` int NOT NULL,
  `kode_masalah` varchar(10) NOT NULL,
  `nama_masalah` varchar(100) NOT NULL,
  `solusi` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `masalah`
--

INSERT INTO `masalah` (`id_masalah`, `kode_masalah`, `nama_masalah`, `solusi`) VALUES
(1, 'M01', 'Kecanduan Gadget/Game Online', 'Melakukan pembatasan jam main secara bertahap, memberikan kegiatan alternatif, dan melibatkan orang tua untuk pengawasan di rumah.'),
(2, 'M02', 'Mengalami Perundungan (Bullying)', 'Memberikan ruang aman bagi korban untuk bercerita, melakukan mediasi dengan pelaku (jika memungkinkan), dan penguatan mental korban.'),
(3, 'M03', 'Kesulitan Belajar (Konsentrasi Rendah)', 'Menjadwalkan bimbingan belajar tambahan, menciptakan suasana belajar yang nyaman, serta mengevaluasi gaya belajar siswa.');

-- --------------------------------------------------------

--
-- Table structure for table `riwayat_konsultasi`
--

CREATE TABLE `riwayat_konsultasi` (
  `id_konsultasi` int NOT NULL,
  `id_siswa` int NOT NULL,
  `tanggal` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `riwayat_konsultasi`
--

INSERT INTO `riwayat_konsultasi` (`id_konsultasi`, `id_siswa`, `tanggal`) VALUES
(27, 3, '2026-07-15 17:46:04'),
(28, 3, '2026-07-15 17:46:52'),
(29, 2, '2026-07-16 01:57:41');

-- --------------------------------------------------------

--
-- Table structure for table `siswa`
--

CREATE TABLE `siswa` (
  `id_siswa` int NOT NULL,
  `nis` varchar(20) NOT NULL,
  `nama_siswa` varchar(100) NOT NULL,
  `kelas` varchar(20) NOT NULL,
  `jenis_kelamin` enum('L','P') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `siswa`
--

INSERT INTO `siswa` (`id_siswa`, `nis`, `nama_siswa`, `kelas`, `jenis_kelamin`) VALUES
(1, '1001', 'Andi Saputra', 'X IPA 1', 'L'),
(2, '1002', 'Budi Santoso', 'X IPS 2', 'L'),
(3, '1003', 'Citra Kirana', 'XI IPA 3', 'P');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id_admin`);

--
-- Indexes for table `aturan`
--
ALTER TABLE `aturan`
  ADD PRIMARY KEY (`id_aturan`),
  ADD KEY `id_masalah` (`id_masalah`),
  ADD KEY `id_gejala` (`id_gejala`);

--
-- Indexes for table `detail_konsultasi`
--
ALTER TABLE `detail_konsultasi`
  ADD PRIMARY KEY (`id_detail`),
  ADD KEY `id_konsultasi` (`id_konsultasi`),
  ADD KEY `id_gejala` (`id_gejala`);

--
-- Indexes for table `gejala`
--
ALTER TABLE `gejala`
  ADD PRIMARY KEY (`id_gejala`),
  ADD UNIQUE KEY `kode_gejala` (`kode_gejala`);

--
-- Indexes for table `hasil_konsultasi`
--
ALTER TABLE `hasil_konsultasi`
  ADD PRIMARY KEY (`id_hasil`),
  ADD KEY `id_konsultasi` (`id_konsultasi`),
  ADD KEY `id_masalah` (`id_masalah`);

--
-- Indexes for table `masalah`
--
ALTER TABLE `masalah`
  ADD PRIMARY KEY (`id_masalah`),
  ADD UNIQUE KEY `kode_masalah` (`kode_masalah`);

--
-- Indexes for table `riwayat_konsultasi`
--
ALTER TABLE `riwayat_konsultasi`
  ADD PRIMARY KEY (`id_konsultasi`),
  ADD KEY `id_siswa` (`id_siswa`);

--
-- Indexes for table `siswa`
--
ALTER TABLE `siswa`
  ADD PRIMARY KEY (`id_siswa`),
  ADD UNIQUE KEY `nis` (`nis`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id_admin` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `aturan`
--
ALTER TABLE `aturan`
  MODIFY `id_aturan` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `detail_konsultasi`
--
ALTER TABLE `detail_konsultasi`
  MODIFY `id_detail` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `gejala`
--
ALTER TABLE `gejala`
  MODIFY `id_gejala` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `hasil_konsultasi`
--
ALTER TABLE `hasil_konsultasi`
  MODIFY `id_hasil` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `masalah`
--
ALTER TABLE `masalah`
  MODIFY `id_masalah` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `riwayat_konsultasi`
--
ALTER TABLE `riwayat_konsultasi`
  MODIFY `id_konsultasi` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `siswa`
--
ALTER TABLE `siswa`
  MODIFY `id_siswa` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `aturan`
--
ALTER TABLE `aturan`
  ADD CONSTRAINT `aturan_ibfk_1` FOREIGN KEY (`id_masalah`) REFERENCES `masalah` (`id_masalah`) ON DELETE CASCADE,
  ADD CONSTRAINT `aturan_ibfk_2` FOREIGN KEY (`id_gejala`) REFERENCES `gejala` (`id_gejala`) ON DELETE CASCADE;

--
-- Constraints for table `detail_konsultasi`
--
ALTER TABLE `detail_konsultasi`
  ADD CONSTRAINT `detail_konsultasi_ibfk_1` FOREIGN KEY (`id_konsultasi`) REFERENCES `riwayat_konsultasi` (`id_konsultasi`) ON DELETE CASCADE,
  ADD CONSTRAINT `detail_konsultasi_ibfk_2` FOREIGN KEY (`id_gejala`) REFERENCES `gejala` (`id_gejala`) ON DELETE CASCADE;

--
-- Constraints for table `hasil_konsultasi`
--
ALTER TABLE `hasil_konsultasi`
  ADD CONSTRAINT `hasil_konsultasi_ibfk_1` FOREIGN KEY (`id_konsultasi`) REFERENCES `riwayat_konsultasi` (`id_konsultasi`) ON DELETE CASCADE,
  ADD CONSTRAINT `hasil_konsultasi_ibfk_2` FOREIGN KEY (`id_masalah`) REFERENCES `masalah` (`id_masalah`) ON DELETE CASCADE;

--
-- Constraints for table `riwayat_konsultasi`
--
ALTER TABLE `riwayat_konsultasi`
  ADD CONSTRAINT `riwayat_konsultasi_ibfk_1` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id_siswa`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
