<?php
class Siswa {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getAllSiswa() {
        $stmt = $this->db->prepare("SELECT * FROM siswa ORDER BY id_siswa DESC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getSiswaById($id) {
        $stmt = $this->db->prepare("SELECT * FROM siswa WHERE id_siswa = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function tambahSiswa($nis, $nama, $kelas, $jk) {
        $stmt = $this->db->prepare("INSERT INTO siswa (nis, nama_siswa, kelas, jenis_kelamin) VALUES (:nis, :nama, :kelas, :jk)");
        return $stmt->execute([':nis' => $nis, ':nama' => $nama, ':kelas' => $kelas, ':jk' => $jk]);
    }

    public function ubahSiswa($id, $nis, $nama, $kelas, $jk) {
        $stmt = $this->db->prepare("UPDATE siswa SET nis = :nis, nama_siswa = :nama, kelas = :kelas, jenis_kelamin = :jk WHERE id_siswa = :id");
        return $stmt->execute([':id' => $id, ':nis' => $nis, ':nama' => $nama, ':kelas' => $kelas, ':jk' => $jk]);
    }

    public function hapusSiswa($id) {
        $stmt = $this->db->prepare("DELETE FROM siswa WHERE id_siswa = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function getSiswaPaginated($keyword = '', $kelas = '', $limit = 10, $offset = 0) {
        $sql = "SELECT * FROM siswa WHERE 1=1";
        $params = [];

        if (!empty($keyword)) {
            $sql .= " AND (nama_siswa LIKE :keyword OR nis LIKE :keyword)";
            $params[':keyword'] = "%$keyword%";
        }
        if (!empty($kelas)) {
            $sql .= " AND kelas = :kelas";
            $params[':kelas'] = $kelas;
        }

        $sql .= " ORDER BY id_siswa DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        
        foreach($params as $key => &$val) {
            $stmt->bindParam($key, $val);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countSiswa($keyword = '', $kelas = '') {
        $sql = "SELECT COUNT(*) as total FROM siswa WHERE 1=1";
        $params = [];

        if (!empty($keyword)) {
            $sql .= " AND (nama_siswa LIKE :keyword OR nis LIKE :keyword)";
            $params[':keyword'] = "%$keyword%";
        }
        if (!empty($kelas)) {
            $sql .= " AND kelas = :kelas";
            $params[':kelas'] = $kelas;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row['total'];
    }

    public function cekNisAda($nis, $id_exclude = null) {
        $sql = "SELECT id_siswa FROM siswa WHERE nis = :nis";
        $params = [':nis' => $nis];

        if ($id_exclude) {
            $sql .= " AND id_siswa != :id_exclude";
            $params[':id_exclude'] = $id_exclude;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }

    public function getDaftarKelas() {
        $stmt = $this->db->prepare("SELECT DISTINCT kelas FROM siswa ORDER BY kelas ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
