<?php
class Database {
    private $host;
    private $user;
    private $pass;
    private $dbname;
    private $conn;

    public function __construct() {
        // Kredensial lokal (di-gitignore) menang atas nilai bawaan XAMPP.
        // Lihat config/local.example.php untuk cara mengaturnya.
        $lokal = [];
        $berkasLokal = __DIR__ . '/../config/local.php';
        if (file_exists($berkasLokal)) {
            $lokal = require $berkasLokal;
        }

        $this->host   = $lokal['db_host'] ?? 'localhost';
        $this->user   = $lokal['db_user'] ?? 'root';
        $this->pass   = $lokal['db_pass'] ?? '';
        $this->dbname = $lokal['db_name'] ?? 'db_pakar_bk';

        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->dbname . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $this->conn = new PDO($dsn, $this->user, $this->pass, $options);
        } catch (PDOException $e) {
            die("Koneksi Database OOP Gagal: " . $e->getMessage());
        }
    }

    public function getConnection() {
        return $this->conn;
    }
}
