<?php
class Gejala {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getAllGejala() {
        $stmt = $this->db->prepare("SELECT * FROM gejala ORDER BY kode_gejala ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getGejalaById($id) {
        $stmt = $this->db->prepare("SELECT * FROM gejala WHERE id_gejala = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function tambahGejala($kode, $nama, $id_admin = null) {
        $stmt = $this->db->prepare("INSERT INTO gejala (kode_gejala, nama_gejala, id_admin) VALUES (:kode, :nama, :id_admin)");
        return $stmt->execute([':kode' => $kode, ':nama' => $nama, ':id_admin' => $id_admin]);
    }

    public function ubahGejala($id, $kode, $nama, $id_admin = null) {
        $stmt = $this->db->prepare("UPDATE gejala SET kode_gejala = :kode, nama_gejala = :nama, id_admin = :id_admin WHERE id_gejala = :id");
        return $stmt->execute([':id' => $id, ':kode' => $kode, ':nama' => $nama, ':id_admin' => $id_admin]);
    }

    public function hapusGejala($id) {
        $stmt = $this->db->prepare("DELETE FROM gejala WHERE id_gejala = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function getGejalaPaginated($keyword = '', $limit = 10, $offset = 0) {
        $sql = "SELECT * FROM gejala WHERE 1=1";
        $params = [];

        if (!empty($keyword)) {
            $sql .= " AND (nama_gejala LIKE :keyword OR kode_gejala LIKE :keyword)";
            $params[':keyword'] = "%$keyword%";
        }

        $sql .= " ORDER BY kode_gejala ASC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        
        foreach($params as $key => &$val) {
            $stmt->bindParam($key, $val);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countGejala($keyword = '') {
        $sql = "SELECT COUNT(*) as total FROM gejala WHERE 1=1";
        $params = [];

        if (!empty($keyword)) {
            $sql .= " AND (nama_gejala LIKE :keyword OR kode_gejala LIKE :keyword)";
            $params[':keyword'] = "%$keyword%";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row['total'];
    }

    public function cekKodeAda($kode, $id_exclude = null) {
        $sql = "SELECT id_gejala FROM gejala WHERE kode_gejala = :kode";
        $params = [':kode' => $kode];

        if ($id_exclude) {
            $sql .= " AND id_gejala != :id_exclude";
            $params[':id_exclude'] = $id_exclude;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }

    public function getNextKode() {
        $stmt = $this->db->query("SELECT MAX(kode_gejala) as max_kode FROM gejala");
        $row = $stmt->fetch();
        $max_kode = $row['max_kode'];
        
        if (!$max_kode) {
            return 'G01';
        }
        
        // Ekstrak angka dari kode terakhir (misal 'G05' -> 5)
        $num = (int) substr($max_kode, 1);
        $num++;
        
        // Format kembali dengan awalan G dan 2 digit angka
        return 'G' . str_pad($num, 2, '0', STR_PAD_LEFT);
    }
}
