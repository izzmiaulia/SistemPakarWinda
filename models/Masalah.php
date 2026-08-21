<?php
class Masalah {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getAllMasalah() {
        $stmt = $this->db->prepare("SELECT * FROM masalah ORDER BY kode_masalah ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getMasalahById($id) {
        $stmt = $this->db->prepare("SELECT * FROM masalah WHERE id_masalah = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function tambahMasalah($kode, $nama, $solusi) {
        $stmt = $this->db->prepare("INSERT INTO masalah (kode_masalah, nama_masalah, solusi) VALUES (:kode, :nama, :solusi)");
        return $stmt->execute([':kode' => $kode, ':nama' => $nama, ':solusi' => $solusi]);
    }

    public function ubahMasalah($id, $kode, $nama, $solusi) {
        $stmt = $this->db->prepare("UPDATE masalah SET kode_masalah = :kode, nama_masalah = :nama, solusi = :solusi WHERE id_masalah = :id");
        return $stmt->execute([':id' => $id, ':kode' => $kode, ':nama' => $nama, ':solusi' => $solusi]);
    }

    public function hapusMasalah($id) {
        $stmt = $this->db->prepare("DELETE FROM masalah WHERE id_masalah = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function getMasalahPaginated($keyword = '', $limit = 10, $offset = 0) {
        $sql = "SELECT * FROM masalah WHERE 1=1";
        $params = [];

        if (!empty($keyword)) {
            $sql .= " AND (nama_masalah LIKE :keyword OR kode_masalah LIKE :keyword)";
            $params[':keyword'] = "%$keyword%";
        }

        $sql .= " ORDER BY kode_masalah ASC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        
        foreach($params as $key => &$val) {
            $stmt->bindParam($key, $val);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countMasalah($keyword = '') {
        $sql = "SELECT COUNT(*) as total FROM masalah WHERE 1=1";
        $params = [];

        if (!empty($keyword)) {
            $sql .= " AND (nama_masalah LIKE :keyword OR kode_masalah LIKE :keyword)";
            $params[':keyword'] = "%$keyword%";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row['total'];
    }

    public function cekKodeAda($kode, $id_exclude = null) {
        $sql = "SELECT id_masalah FROM masalah WHERE kode_masalah = :kode";
        $params = [':kode' => $kode];

        if ($id_exclude) {
            $sql .= " AND id_masalah != :id_exclude";
            $params[':id_exclude'] = $id_exclude;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }

    public function getNextKode() {
        $stmt = $this->db->query("SELECT MAX(kode_masalah) as max_kode FROM masalah");
        $row = $stmt->fetch();
        $max_kode = $row['max_kode'];
        
        if (!$max_kode) {
            return 'M01';
        }
        
        // Ekstrak angka dari kode terakhir (misal 'M05' -> 5)
        $num = (int) substr($max_kode, 1);
        $num++;
        
        // Format kembali dengan awalan M dan 2 digit angka
        return 'M' . str_pad($num, 2, '0', STR_PAD_LEFT);
    }
}
