<?php
// index.php - Front Controller
session_start();
ob_start(); // Tangkap semua potensi output whitespace (spasi/enter) yang bocor

// 1. Muat Konfigurasi & Autoloader
// Muat Composer Autoloader
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

require 'config/koneksi.php';

// Inisialisasi & Query profil admin global
$admin_profile = null;
try {
    // Jalankan alter table jika belum ada kolomnya
    $db->query("ALTER TABLE admin ADD COLUMN IF NOT EXISTS nama_sekolah VARCHAR(100) DEFAULT NULL");
    $db->query("ALTER TABLE admin ADD COLUMN IF NOT EXISTS logo_sekolah VARCHAR(255) DEFAULT NULL");
} catch (PDOException $e) {
    try { $db->query("ALTER TABLE admin ADD COLUMN nama_sekolah VARCHAR(100) DEFAULT NULL"); } catch (PDOException $x) {}
    try { $db->query("ALTER TABLE admin ADD COLUMN logo_sekolah VARCHAR(255) DEFAULT NULL"); } catch (PDOException $x) {}
}

// Pastikan ada setidaknya satu admin terdaftar
$stmtCheck = $db->query("SELECT COUNT(*) FROM admin");
if ($stmtCheck->fetchColumn() == 0) {
    $defHash = password_hash('gurubk123', PASSWORD_BCRYPT);
    $db->prepare("INSERT INTO admin (username, password, nama_lengkap, nama_sekolah, logo_sekolah) VALUES (?, ?, ?, ?, ?)")
       ->execute(['gurubk', $defHash, 'Guru BK', 'SMK Negeri 1', null]);
}

$admin_profile = $db->query("SELECT * FROM admin ORDER BY id_admin ASC LIMIT 1")->fetch();

// 2. Ambil parameter page dari URL (default: dashboard)
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

// 3. Mapping rute ke file fisik di folder pages/
$routes = [
    'dashboard' => 'pages/dashboard.php',
    'siswa'     => 'pages/master/siswa.php',
    'masalah'   => 'pages/master/masalah.php',
    'gejala'    => 'pages/master/gejala.php',
    'aturan'    => 'pages/master/aturan.php',
    'konsultasi'=> 'pages/konsultasi/mulai.php',
    'proses'    => 'pages/konsultasi/proses.php',
    'hasil'     => 'pages/konsultasi/hasil.php',
    'riwayat'   => 'pages/konsultasi/riwayat.php',
    'profil'    => 'pages/profil.php',
    'login'     => 'pages/login.php',
    'logout'    => 'pages/logout.php'
];

// Cek autentikasi (kecuali untuk halaman login)
if ($page !== 'login' && !isset($_SESSION['login'])) {
    header('Location: index.php?page=login');
    exit;
}

// Cek apakah halaman valid
if (array_key_exists($page, $routes) && file_exists($routes[$page])) {
    $content = $routes[$page];
} else {
    // Fallback jika halaman tidak ditemukan (404)
    $content = 'pages/dashboard.php';
}

// 4. Proses aksi (jika ada POST form atau GET hapus yang butuh dieksekusi SEBELUM header HTML dikirim)
// Kita harus require file konten dulu jika ada proses header() redirect di dalamnya.
// Namun karena kita butuh header dan footer untuk dirender, maka kita pecah:
// Untuk sementara, kita membiarkan halaman konten menangani form/post sendiri. 
// Jika mereka melakukan redirect, mereka harus memanggil header() sebelum output HTML dikirim oleh layouts/header.php.
// Oleh karena itu, kita ubah pendekatannya: file konten HANYA berisi HTML/logika tampilan. Logika POST di-handle di atas atau dengan output buffering.

// Muat isi halaman terlebih dahulu. Jika ada eksekusi header('Location: ...') di dalamnya, dia akan berjalan lancar karena output di-buffer.
require $content;
$page_content = ob_get_clean();

// 5. Render Layout
if ($page !== 'login') {
    require 'layouts/header.php';
    require 'layouts/sidebar.php';
} else {
    // Basic HTML boilerplate for login
    echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Login - PakarBK</title><script src="https://cdn.tailwindcss.com"></script><script src="https://unpkg.com/lucide@latest"></script><script>tailwind.config = { theme: { extend: { fontFamily: { sans: ["Inter", "sans-serif"] }, colors: { brand: { 50: "#f0fdf4", 100: "#dcfce7", 500: "#22c55e", 600: "#16a34a", 700: "#15803d", 900: "#14532d" } } } } }</script></head><body class="bg-slate-50">';
}

// Cetak Konten Halaman
echo $page_content;

if ($page !== 'login') {
    require 'layouts/footer.php';
} else {
    echo '<script>lucide.createIcons();</script></body></html>';
}
?>
