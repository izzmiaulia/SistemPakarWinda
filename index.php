<?php
// index.php - Front Controller
session_start();
ob_start(); // Tangkap potensi output whitespace yang bocor sebelum header dikirim

// 1. Muat Composer autoloader (bila tersedia) dan konfigurasi aplikasi
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

require 'config/koneksi.php';

// 2. Profil admin/sekolah yang dipakai di seluruh halaman
$stmtCheck = $db->query("SELECT COUNT(*) FROM admin");
if ($stmtCheck->fetchColumn() == 0) {
    $konfigLokal    = file_exists(__DIR__ . '/config/local.php') ? require __DIR__ . '/config/local.php' : [];
    $passwordAwal   = $konfigLokal['default_admin_password'] ?? 'GantiSegera#2026';
    $defHash        = password_hash($passwordAwal, PASSWORD_BCRYPT);
    $db->prepare("INSERT INTO admin (username, password, nama_lengkap, nama_sekolah, logo_sekolah, role, harus_ganti_password) VALUES (?, ?, ?, ?, ?, ?, ?)")
       ->execute(['gurubk', $defHash, 'Guru BK', 'MTs Swasta TPI Gunung Pamela', null, 'pakar', 1]);
}

$admin_profile = $db->query("SELECT * FROM admin ORDER BY id_admin ASC LIMIT 1")->fetch();

// 3. Ambil parameter page dari URL (default: dashboard)
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

// 4. Pemetaan rute ke berkas fisik di folder pages/
$routes = [
    'dashboard'   => 'pages/dashboard.php',
    'siswa'       => 'pages/master/siswa.php',
    'kepribadian' => 'pages/master/kepribadian.php',
    'gejala'      => 'pages/master/gejala.php',
    'aturan'      => 'pages/master/aturan.php',
    'konsultasi'  => 'pages/konsultasi/mulai.php',
    'proses'      => 'pages/konsultasi/proses.php',
    'hasil'       => 'pages/konsultasi/hasil.php',
    'riwayat'     => 'pages/konsultasi/riwayat.php',
    'profil'      => 'pages/profil.php',
    'login'       => 'pages/login.php',
    'logout'      => 'pages/logout.php',
];

// 5. Cek autentikasi (kecuali untuk halaman login)
if ($page !== 'login' && !isset($_SESSION['login'])) {
    header('Location: index.php?page=login');
    exit;
}

// 5b. Paksa ganti password sebelum mengakses halaman lain, kecuali profil sendiri
if (!empty($_SESSION['harus_ganti_password']) && !in_array($page, ['login', 'logout', 'profil'], true)) {
    header('Location: index.php?page=profil');
    exit;
}

// 6. Tentukan berkas konten
if (array_key_exists($page, $routes) && file_exists($routes[$page])) {
    $content = $routes[$page];
} else {
    $content = 'pages/dashboard.php';
}

// 7. Muat isi halaman lebih dulu. Output di-buffer sehingga header('Location: ...')
//    di dalam berkas konten tetap dapat dijalankan.
require $content;
$page_content = ob_get_clean();

// 8. Render layout
if ($page !== 'login') {
    require 'layouts/header.php';
    require 'layouts/sidebar.php';
} else {
    echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Login - PakarBK</title><script src="https://cdn.tailwindcss.com"></script><script src="https://unpkg.com/lucide@latest"></script><script>tailwind.config = { theme: { extend: { fontFamily: { sans: ["Inter", "sans-serif"] }, colors: { brand: { 50: "#f0fdf4", 100: "#dcfce7", 500: "#22c55e", 600: "#16a34a", 700: "#15803d", 900: "#14532d" } } } } }</script></head><body class="bg-slate-50">';
}

echo $page_content;

if ($page !== 'login') {
    require 'layouts/footer.php';
} else {
    echo '<script>lucide.createIcons();</script></body></html>';
}
