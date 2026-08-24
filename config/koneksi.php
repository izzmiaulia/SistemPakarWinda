<?php
// Autoload sederhana untuk memuat kelas dari folder models/
spl_autoload_register(function ($class_name) {
    $file = __DIR__ . '/../models/' . $class_name . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Helper keamanan (token CSRF)
require_once __DIR__ . '/keamanan.php';

// Inisialisasi Database
$database = new Database();
$db       = $database->getConnection();
