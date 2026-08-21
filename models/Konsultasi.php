<?php
class Konsultasi {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getRiwayat() {
        $stmt = $this->db->prepare("
            SELECT r.id_konsultasi, r.tanggal, s.nis, s.nama_siswa, s.kelas,
                   h.nilai_persentase, m.kode_masalah, m.nama_masalah
            FROM riwayat_konsultasi r
            JOIN siswa s ON r.id_siswa = s.id_siswa
            JOIN hasil_konsultasi h ON r.id_konsultasi = h.id_konsultasi
            JOIN masalah m ON h.id_masalah = m.id_masalah
            ORDER BY r.tanggal DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getRiwayatPaginated($keyword = '', $limit = 10, $offset = 0) {
        $sql = "
            SELECT r.id_konsultasi, r.tanggal, s.nis, s.nama_siswa, s.kelas,
                   h.nilai_persentase, m.kode_masalah, m.nama_masalah
            FROM riwayat_konsultasi r
            JOIN siswa s ON r.id_siswa = s.id_siswa
            JOIN hasil_konsultasi h ON r.id_konsultasi = h.id_konsultasi
            JOIN masalah m ON h.id_masalah = m.id_masalah
            WHERE 1=1
        ";
        $params = [];

        if (!empty($keyword)) {
            $sql .= " AND (s.nama_siswa LIKE :keyword OR s.kelas LIKE :keyword OR s.nis LIKE :keyword OR m.nama_masalah LIKE :keyword)";
            $params[':keyword'] = "%$keyword%";
        }

        $sql .= " ORDER BY r.tanggal DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => &$val) {
            $stmt->bindParam($key, $val);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countRiwayat($keyword = '') {
        $sql = "
            SELECT COUNT(*) as total
            FROM riwayat_konsultasi r
            JOIN siswa s ON r.id_siswa = s.id_siswa
            JOIN hasil_konsultasi h ON r.id_konsultasi = h.id_konsultasi
            JOIN masalah m ON h.id_masalah = m.id_masalah
            WHERE 1=1
        ";
        $params = [];

        if (!empty($keyword)) {
            $sql .= " AND (s.nama_siswa LIKE :keyword OR s.kelas LIKE :keyword OR s.nis LIKE :keyword OR m.nama_masalah LIKE :keyword)";
            $params[':keyword'] = "%$keyword%";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch()['total'];
    }

    public function getDetailKonsultasi($id_konsultasi) {
        // Ambil info dasar
        $stmt = $this->db->prepare("
            SELECT r.id_konsultasi, r.tanggal, s.nis, s.nama_siswa, s.kelas, s.jenis_kelamin,
                   h.nilai_persentase, h.log_proses, m.id_masalah, m.kode_masalah, m.nama_masalah, m.solusi
            FROM riwayat_konsultasi r
            JOIN siswa s ON r.id_siswa = s.id_siswa
            JOIN hasil_konsultasi h ON r.id_konsultasi = h.id_konsultasi
            JOIN masalah m ON h.id_masalah = m.id_masalah
            WHERE r.id_konsultasi = :id
        ");
        $stmt->execute([':id' => $id_konsultasi]);
        $detail = $stmt->fetch();

        // Ambil gejala yang dipilih dalam sesi ini
        $stmt2 = $this->db->prepare("
            SELECT g.kode_gejala, g.nama_gejala, a.nilai_belief
            FROM detail_konsultasi dk
            JOIN gejala g ON dk.id_gejala = g.id_gejala
            JOIN hasil_konsultasi h ON dk.id_konsultasi = h.id_konsultasi
            JOIN aturan a ON a.id_gejala = g.id_gejala AND a.id_masalah = h.id_masalah
            WHERE dk.id_konsultasi = :id
            ORDER BY g.kode_gejala ASC
        ");
        $stmt2->execute([':id' => $id_konsultasi]);
        $detail['gejala'] = $stmt2->fetchAll();

        return $detail;
    }

    public function hapusKonsultasi($id_konsultasi) {
        // Hapus detail terlebih dahulu karena foreign key
        $stmt1 = $this->db->prepare("DELETE FROM detail_konsultasi WHERE id_konsultasi = :id");
        $stmt1->execute([':id' => $id_konsultasi]);

        $stmt2 = $this->db->prepare("DELETE FROM hasil_konsultasi WHERE id_konsultasi = :id");
        $stmt2->execute([':id' => $id_konsultasi]);

        $stmt3 = $this->db->prepare("DELETE FROM riwayat_konsultasi WHERE id_konsultasi = :id");
        return $stmt3->execute([':id' => $id_konsultasi]);
    }

    public function hapusMassalKonsultasi($ids) {
        if (empty($ids)) return false;
        
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        
        $stmt1 = $this->db->prepare("DELETE FROM detail_konsultasi WHERE id_konsultasi IN ($placeholders)");
        $stmt1->execute($ids);
        
        $stmt2 = $this->db->prepare("DELETE FROM hasil_konsultasi WHERE id_konsultasi IN ($placeholders)");
        $stmt2->execute($ids);
        
        $stmt3 = $this->db->prepare("DELETE FROM riwayat_konsultasi WHERE id_konsultasi IN ($placeholders)");
        return $stmt3->execute($ids);
    }

    public function simpanKonsultasi($id_siswa, $id_masalah, $gejala_terpilih, $persentase, $log_proses = null) {
        $tgl = date('Y-m-d H:i:s');
        
        $stmt = $this->db->prepare("INSERT INTO riwayat_konsultasi (id_siswa, tanggal) VALUES (:id_siswa, :tanggal)");
        $stmt->execute([':id_siswa' => $id_siswa, ':tanggal' => $tgl]);
        
        $id_konsultasi = $this->db->lastInsertId();

        $stmt_detail = $this->db->prepare("INSERT INTO detail_konsultasi (id_konsultasi, id_gejala) VALUES (:id_kons, :id_gej)");
        foreach($gejala_terpilih as $id_g) {
            $stmt_detail->execute([':id_kons' => $id_konsultasi, ':id_gej' => $id_g]);
        }

        $stmt_hasil = $this->db->prepare("INSERT INTO hasil_konsultasi (id_konsultasi, id_masalah, nilai_persentase, log_proses) VALUES (:id_kons, :id_masalah, :persentase, :log_proses)");
        $stmt_hasil->execute([
            ':id_kons' => $id_konsultasi, 
            ':id_masalah' => $id_masalah, 
            ':persentase' => $persentase,
            ':log_proses' => $log_proses
        ]);

        return $id_konsultasi;
    }

    public function getStats() {
        $stats = [];
        
        $stmt1 = $this->db->query("SELECT COUNT(*) as total FROM siswa");
        $stats['siswa'] = $stmt1->fetch()['total'];

        $stmt2 = $this->db->query("SELECT COUNT(*) as total FROM masalah");
        $stats['masalah'] = $stmt2->fetch()['total'];

        $stmt3 = $this->db->query("SELECT COUNT(*) as total FROM gejala");
        $stats['gejala'] = $stmt3->fetch()['total'];

        $stmt4 = $this->db->query("SELECT COUNT(*) as total FROM riwayat_konsultasi");
        $stats['konsultasi'] = $stmt4->fetch()['total'];

        return $stats;
    }
}
