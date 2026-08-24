-- =============================================================================
--  Migrasi inkremental: peran akun, wajib-ganti-password, kolom audit ringan
-- =============================================================================
--  Dipakai untuk database db_pakar_bk yang SUDAH ADA isinya (mis. riwayat
--  konsultasi hasil uji coba), sehingga TIDAK di-drop dan diimpor ulang.
--
--  Untuk pemasangan baru, cukup pakai db_pakar_bk.sql -- skema di sana sudah
--  memuat seluruh perubahan yang sama.
--
--  Jalankan sekali:
--      mysql -u root db_pakar_bk < migration_role_password_akun.sql
-- =============================================================================

-- 1. Peran akun + wajib ganti password
ALTER TABLE `admin`
  ADD COLUMN `role` enum('guru_bk','pakar') NOT NULL DEFAULT 'guru_bk' AFTER `logo_sekolah`,
  ADD COLUMN `harus_ganti_password` tinyint(1) NOT NULL DEFAULT 0 AFTER `role`;

-- Akun yang sudah ada dinaikkan ke peran 'pakar' (akses penuh) supaya tidak
-- terkunci dari halaman master data yang biasa dipakainya, dan diwajibkan
-- mengganti password karena nilai lamanya dipensiunkan dari kode.
UPDATE `admin` SET `role` = 'pakar', `harus_ganti_password` = 1 WHERE `username` = 'gurubk';

-- 2. Kolom audit ringan (siapa & kapan) di 4 tabel master data
ALTER TABLE `siswa`
  ADD COLUMN `id_admin` int NULL AFTER `jenis_kelamin`,
  ADD COLUMN `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `id_admin`,
  ADD COLUMN `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`,
  ADD KEY `id_admin` (`id_admin`),
  ADD CONSTRAINT `siswa_ibfk_admin` FOREIGN KEY (`id_admin`) REFERENCES `admin` (`id_admin`) ON DELETE SET NULL;

ALTER TABLE `kepribadian`
  ADD COLUMN `id_admin` int NULL AFTER `rekomendasi`,
  ADD COLUMN `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `id_admin`,
  ADD COLUMN `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`,
  ADD KEY `id_admin` (`id_admin`),
  ADD CONSTRAINT `kepribadian_ibfk_admin` FOREIGN KEY (`id_admin`) REFERENCES `admin` (`id_admin`) ON DELETE SET NULL;

ALTER TABLE `gejala`
  ADD COLUMN `id_admin` int NULL AFTER `nama_gejala`,
  ADD COLUMN `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `id_admin`,
  ADD COLUMN `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`,
  ADD KEY `id_admin` (`id_admin`),
  ADD CONSTRAINT `gejala_ibfk_admin` FOREIGN KEY (`id_admin`) REFERENCES `admin` (`id_admin`) ON DELETE SET NULL;

ALTER TABLE `aturan`
  ADD COLUMN `id_admin` int NULL AFTER `nilai_cf`,
  ADD COLUMN `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `id_admin`,
  ADD COLUMN `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`,
  ADD KEY `id_admin` (`id_admin`),
  ADD CONSTRAINT `aturan_ibfk_admin` FOREIGN KEY (`id_admin`) REFERENCES `admin` (`id_admin`) ON DELETE SET NULL;

-- 3. Atribusikan data yang sudah ada ke akun 'gurubk' (id_admin = 1)
UPDATE `siswa`       SET `id_admin` = 1 WHERE `id_admin` IS NULL;
UPDATE `kepribadian` SET `id_admin` = 1 WHERE `id_admin` IS NULL;
UPDATE `gejala`      SET `id_admin` = 1 WHERE `id_admin` IS NULL;
UPDATE `aturan`      SET `id_admin` = 1 WHERE `id_admin` IS NULL;

-- Verifikasi setelah menjalankan migrasi ini:
--   SELECT id_admin, username, role, harus_ganti_password FROM admin;
-- Harapan: gurubk -> role='pakar', harus_ganti_password=1
