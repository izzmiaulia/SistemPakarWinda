<?php
/**
 * Salin berkas ini menjadi `config/local.php` dan sesuaikan nilainya.
 * `config/local.php` sudah masuk .gitignore — tidak akan ikut ter-commit.
 *
 * Kalau `config/local.php` tidak ada, aplikasi memakai nilai bawaan XAMPP
 * (host localhost, user root, tanpa password) supaya tetap bisa langsung
 * dijalankan di lingkungan pengembangan lokal.
 */

return [
    // Koneksi database
    'db_host'   => 'localhost',
    'db_user'   => 'root',
    'db_pass'   => '',
    'db_name'   => 'db_pakar_bk',

    // Password bawaan akun admin pertama, dipakai HANYA saat tabel `admin`
    // masih kosong (pemasangan baru). Akun ini otomatis wajib mengganti
    // password pada login pertama (lihat kolom harus_ganti_password).
    'default_admin_password' => 'GantiSegera#2026',
];
