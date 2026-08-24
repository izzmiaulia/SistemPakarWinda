-- =============================================================================
--  PakarBK - Sistem Pakar Bimbingan & Konseling
--  Skema basis data: identifikasi tipe kepribadian DISC
--  Metode: Certainty Factor + Backward Chaining
-- =============================================================================
--  Basis pengetahuan (indikator, hipotesis, aturan, bobot) mengikuti dokumen
--  penelitian tertanggal 21 Agustus 2026, Bab III dan Bab IV.
--
--  Collation memakai utf8mb4_general_ci agar dapat dipasang baik pada MariaDB
--  maupun MySQL 8. Jangan diganti ke utf8mb4_0900_ai_ci karena collation
--  tersebut hanya dikenali MySQL 8.
--
--  Pemasangan:
--      CREATE DATABASE db_pakar_bk CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
--      mysql -u root db_pakar_bk < db_pakar_bk.sql
-- =============================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `hasil_detail`;
DROP TABLE IF EXISTS `hasil_konsultasi`;
DROP TABLE IF EXISTS `detail_konsultasi`;
DROP TABLE IF EXISTS `riwayat_konsultasi`;
DROP TABLE IF EXISTS `aturan`;
DROP TABLE IF EXISTS `gejala`;
DROP TABLE IF EXISTS `kepribadian`;
DROP TABLE IF EXISTS `masalah`;
DROP TABLE IF EXISTS `siswa`;
DROP TABLE IF EXISTS `admin`;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------------
--  admin - akun pengguna sekaligus identitas sekolah
-- ---------------------------------------------------------------------------

CREATE TABLE `admin` (
  `id_admin`     int NOT NULL AUTO_INCREMENT,
  `username`     varchar(50)  NOT NULL,
  `password`     varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `nama_sekolah` varchar(100) DEFAULT NULL,
  `logo_sekolah` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_admin`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Kata sandi bawaan: gurubk123 -- WAJIB diganti setelah pemasangan.
INSERT INTO `admin` (`id_admin`, `username`, `password`, `nama_lengkap`, `nama_sekolah`, `logo_sekolah`) VALUES
(1, 'gurubk', '$2y$10$ugXwa8swz1WZvorleI/IX.qA5bTBocTgfJUhfI7V89Xyz9rPBQ/t2', 'Guru BK (Admin)', 'MTs Swasta TPI Gunung Pamela', NULL);

-- ---------------------------------------------------------------------------
--  siswa
-- ---------------------------------------------------------------------------

CREATE TABLE `siswa` (
  `id_siswa`      int NOT NULL AUTO_INCREMENT,
  `nis`           varchar(20)  NOT NULL,
  `nama_siswa`    varchar(100) NOT NULL,
  `kelas`         varchar(20)  NOT NULL,
  `jenis_kelamin` enum('L','P') NOT NULL,
  PRIMARY KEY (`id_siswa`),
  UNIQUE KEY `nis` (`nis`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data contoh untuk keperluan uji coba. Ganti dengan data sungguhan
-- setelah izin penggunaan data siswa diperoleh.
INSERT INTO `siswa` (`id_siswa`, `nis`, `nama_siswa`, `kelas`, `jenis_kelamin`) VALUES
(1, '1001', 'Contoh Siswa Satu', 'VII-A', 'L'),
(2, '1002', 'Contoh Siswa Dua',  'VII-A', 'P'),
(3, '1003', 'Contoh Siswa Tiga', 'VII-B', 'P');

-- ---------------------------------------------------------------------------
--  kepribadian - hipotesis H01..H04 beserta rule R01..R04
-- ---------------------------------------------------------------------------

CREATE TABLE `kepribadian` (
  `id_kepribadian` int NOT NULL AUTO_INCREMENT,
  `kode`           varchar(5)  NOT NULL,
  `kode_rule`      varchar(5)  NOT NULL,
  `tipe`           enum('D','I','S','C') NOT NULL,
  `nama`           varchar(50) NOT NULL,
  `deskripsi`      text,
  `rekomendasi`    text,
  PRIMARY KEY (`id_kepribadian`),
  UNIQUE KEY `kode` (`kode`),
  UNIQUE KEY `kode_rule` (`kode_rule`),
  UNIQUE KEY `tipe` (`tipe`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Kolom `rekomendasi` masih berisi penanda sementara. Isi dengan rumusan
-- tindak lanjut layanan BK dari guru bimbingan dan konseling.
INSERT INTO `kepribadian` (`id_kepribadian`, `kode`, `kode_rule`, `tipe`, `nama`, `deskripsi`, `rekomendasi`) VALUES
(1, 'H01', 'R01', 'D', 'Dominance',  'Kecenderungan ingin menguasai, mengarahkan, atau menentukan.', '[BELUM DIISI] Menunggu rumusan tindak lanjut layanan BK dari guru bimbingan dan konseling.'),
(2, 'H02', 'R02', 'I', 'Influence',  'Kecenderungan memengaruhi orang lain dan memiliki wibawa atau pengaruh.', '[BELUM DIISI] Menunggu rumusan tindak lanjut layanan BK dari guru bimbingan dan konseling.'),
(3, 'H03', 'R03', 'S', 'Steadiness', 'Kecenderungan mantap dan yakin dalam menghadapi situasi serta menyampaikan pendapat.', '[BELUM DIISI] Menunggu rumusan tindak lanjut layanan BK dari guru bimbingan dan konseling.'),
(4, 'H04', 'R04', 'C', 'Compliance', 'Kecenderungan menerima atau mengikuti keadaan maupun arahan.', '[BELUM DIISI] Menunggu rumusan tindak lanjut layanan BK dari guru bimbingan dan konseling.');

-- ---------------------------------------------------------------------------
--  gejala - 16 indikator G01..G16 (pernyataan swalapor)
-- ---------------------------------------------------------------------------

CREATE TABLE `gejala` (
  `id_gejala`   int NOT NULL AUTO_INCREMENT,
  `kode_gejala` varchar(10)  NOT NULL,
  `nama_gejala` varchar(255) NOT NULL,
  PRIMARY KEY (`id_gejala`),
  UNIQUE KEY `kode_gejala` (`kode_gejala`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `gejala` (`id_gejala`, `kode_gejala`, `nama_gejala`) VALUES
(1,  'G01', 'Saya memiliki keinginan untuk menguasai materi yang sedang dipelajari'),
(2,  'G02', 'Saya cenderung ingin menjadi pihak yang menentukan dalam kelompok'),
(3,  'G03', 'Saya cenderung mengarahkan teman ketika bekerja dalam kelompok'),
(4,  'G04', 'Saya nyaman mengambil peran sebagai pemimpin'),
(5,  'G05', 'Saya mampu memengaruhi teman untuk mengikuti ide saya'),
(6,  'G06', 'Saya memiliki wibawa atau pengaruh dalam kelompok'),
(7,  'G07', 'Saya mudah mengajak teman melakukan suatu kegiatan'),
(8,  'G08', 'Saya aktif berkomunikasi ketika berada dalam kelompok'),
(9,  'G09', 'Saya merasa mantap dan yakin ketika mengambil keputusan'),
(10, 'G10', 'Saya tetap tenang ketika mendapat ejekan dari teman'),
(11, 'G11', 'Saya mampu mempertahankan pendapat yang saya yakini'),
(12, 'G12', 'Saya mampu menyampaikan pendapat dengan tenang'),
(13, 'G13', 'Saya cenderung menerima keadaan ketika keputusan telah ditetapkan'),
(14, 'G14', 'Saya bersedia mengikuti arahan yang diberikan kepada saya'),
(15, 'G15', 'Saya cenderung menyesuaikan diri dengan keputusan kelompok'),
(16, 'G16', 'Saya lebih memilih mengikuti aturan sebelum bertindak');

-- ---------------------------------------------------------------------------
--  aturan - basis pengetahuan: relasi hipotesis x indikator + bobot pakar
-- ---------------------------------------------------------------------------

CREATE TABLE `aturan` (
  `id_aturan`      int NOT NULL AUTO_INCREMENT,
  `id_kepribadian` int   NOT NULL,
  `id_gejala`      int   NOT NULL,
  `nilai_cf`       float NOT NULL,
  PRIMARY KEY (`id_aturan`),
  UNIQUE KEY `relasi_unik` (`id_kepribadian`,`id_gejala`),
  KEY `id_gejala` (`id_gejala`),
  CONSTRAINT `aturan_ibfk_1` FOREIGN KEY (`id_kepribadian`) REFERENCES `kepribadian` (`id_kepribadian`) ON DELETE CASCADE,
  CONSTRAINT `aturan_ibfk_2` FOREIGN KEY (`id_gejala`)      REFERENCES `gejala` (`id_gejala`)           ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Bobot pakar mengikuti tabel 4.1.1 dokumen penelitian.
-- Nilai ini dapat diubah melalui halaman Aturan Pakar tanpa mengubah kode.
INSERT INTO `aturan` (`id_aturan`, `id_kepribadian`, `id_gejala`, `nilai_cf`) VALUES
-- R01 - Dominance
(1,  1, 1,  0.8),
(2,  1, 2,  0.9),
(3,  1, 3,  0.8),
(4,  1, 4,  0.7),
-- R02 - Influence
(5,  2, 5,  0.8),
(6,  2, 6,  0.9),
(7,  2, 7,  0.8),
(8,  2, 8,  0.7),
-- R03 - Steadiness
(9,  3, 9,  0.9),
(10, 3, 10, 0.8),
(11, 3, 11, 0.8),
(12, 3, 12, 0.7),
-- R04 - Compliance
(13, 4, 13, 0.8),
(14, 4, 14, 0.9),
(15, 4, 15, 0.8),
(16, 4, 16, 0.7);

-- ---------------------------------------------------------------------------
--  riwayat_konsultasi - satu baris per sesi konsultasi
-- ---------------------------------------------------------------------------

CREATE TABLE `riwayat_konsultasi` (
  `id_konsultasi` int      NOT NULL AUTO_INCREMENT,
  `id_siswa`      int      NOT NULL,
  `tanggal`       datetime NOT NULL,
  PRIMARY KEY (`id_konsultasi`),
  KEY `id_siswa` (`id_siswa`),
  CONSTRAINT `riwayat_konsultasi_ibfk_1` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id_siswa`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------------
--  detail_konsultasi - jawaban pengguna atas SETIAP indikator
--  Termasuk jawaban bernilai 0.0 ("Tidak"), agar sesi dapat direkonstruksi utuh.
--
--  Kolom cf_pakar menyimpan bobot pakar PADA SAAT konsultasi dijalankan.
--  Dengan begitu rincian perhitungan sebuah sesi tetap dapat direproduksi persis
--  walaupun bobot pada tabel `aturan` diubah setelahnya.
-- ---------------------------------------------------------------------------

CREATE TABLE `detail_konsultasi` (
  `id_detail`     int   NOT NULL AUTO_INCREMENT,
  `id_konsultasi` int   NOT NULL,
  `id_gejala`     int   NOT NULL,
  `cf_user`       float NOT NULL,
  `cf_pakar`      float NOT NULL,
  PRIMARY KEY (`id_detail`),
  UNIQUE KEY `jawaban_unik` (`id_konsultasi`,`id_gejala`),
  KEY `id_gejala` (`id_gejala`),
  CONSTRAINT `detail_konsultasi_ibfk_1` FOREIGN KEY (`id_konsultasi`) REFERENCES `riwayat_konsultasi` (`id_konsultasi`) ON DELETE CASCADE,
  CONSTRAINT `detail_konsultasi_ibfk_2` FOREIGN KEY (`id_gejala`)     REFERENCES `gejala` (`id_gejala`)                 ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------------
--  hasil_konsultasi - hipotesis dengan keyakinan tertinggi + log penelusuran
-- ---------------------------------------------------------------------------

CREATE TABLE `hasil_konsultasi` (
  `id_hasil`         int   NOT NULL AUTO_INCREMENT,
  `id_konsultasi`    int   NOT NULL,
  `id_kepribadian`   int   NOT NULL,
  `nilai_persentase` float NOT NULL,
  `log_proses`       text,
  PRIMARY KEY (`id_hasil`),
  UNIQUE KEY `hasil_unik` (`id_konsultasi`),
  KEY `id_kepribadian` (`id_kepribadian`),
  CONSTRAINT `hasil_konsultasi_ibfk_1` FOREIGN KEY (`id_konsultasi`)  REFERENCES `riwayat_konsultasi` (`id_konsultasi`) ON DELETE CASCADE,
  CONSTRAINT `hasil_konsultasi_ibfk_2` FOREIGN KEY (`id_kepribadian`) REFERENCES `kepribadian` (`id_kepribadian`)       ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------------
--  hasil_detail - skor KEEMPAT hipotesis pada satu sesi konsultasi
--  Tabel inilah yang menjadi dasar tabel perbandingan pada bab pembahasan.
-- ---------------------------------------------------------------------------

CREATE TABLE `hasil_detail` (
  `id_hasil_detail` int            NOT NULL AUTO_INCREMENT,
  `id_konsultasi`   int            NOT NULL,
  `id_kepribadian`  int            NOT NULL,
  `cf_akhir`        decimal(10,6)  NOT NULL,
  `persentase`      float          NOT NULL,
  `peringkat`       tinyint        NOT NULL,
  PRIMARY KEY (`id_hasil_detail`),
  UNIQUE KEY `hasil_detail_unik` (`id_konsultasi`,`id_kepribadian`),
  KEY `id_kepribadian` (`id_kepribadian`),
  CONSTRAINT `hasil_detail_ibfk_1` FOREIGN KEY (`id_konsultasi`)  REFERENCES `riwayat_konsultasi` (`id_konsultasi`) ON DELETE CASCADE,
  CONSTRAINT `hasil_detail_ibfk_2` FOREIGN KEY (`id_kepribadian`) REFERENCES `kepribadian` (`id_kepribadian`)       ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
