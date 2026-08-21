<?php
// Autoload sederhana untuk me-load class
spl_autoload_register(function ($class_name) {
    $file = __DIR__ . '/../models/' . $class_name . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Inisialisasi Database
$database = new Database();
$db = $database->getConnection();
$conn = $db; // For backward compatibility with legacy scripts if any
