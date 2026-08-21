<?php
class Aturan {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getAturanByMasalah($id_masalah) {
        $stmt = $this->db->prepare("
            SELECT a.*, g.kode_gejala, g.nama_gejala 
            FROM aturan a 
            JOIN gejala g ON a.id_gejala = g.id_gejala 
            WHERE a.id_masalah = :id_masalah
            ORDER BY g.kode_gejala ASC
        ");
        $stmt->bindParam(':id_masalah', $id_masalah);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getAturanByMasalahAndGejala($id_masalah, $id_gejala) {
        $stmt = $this->db->prepare("
            SELECT a.nilai_belief, g.kode_gejala, g.nama_gejala 
            FROM aturan a 
            JOIN gejala g ON a.id_gejala = g.id_gejala 
            WHERE a.id_masalah = :id_masalah AND a.id_gejala = :id_gejala
        ");
        $stmt->bindParam(':id_masalah', $id_masalah);
        $stmt->bindParam(':id_gejala', $id_gejala);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function tambahAturan($id_masalah, $id_gejala, $belief) {
        $stmt = $this->db->prepare("INSERT INTO aturan (id_masalah, id_gejala, nilai_belief) VALUES (:id_masalah, :id_gejala, :belief)");
        return $stmt->execute([':id_masalah' => $id_masalah, ':id_gejala' => $id_gejala, ':belief' => $belief]);
    }

    public function hapusAturan($id_aturan) {
        $stmt = $this->db->prepare("DELETE FROM aturan WHERE id_aturan = :id");
        return $stmt->execute([':id' => $id_aturan]);
    }

    public function cekRelasiAda($id_masalah, $id_gejala) {
        $stmt = $this->db->prepare("SELECT id_aturan FROM aturan WHERE id_masalah = :id_masalah AND id_gejala = :id_gejala");
        $stmt->execute([
            ':id_masalah' => $id_masalah,
            ':id_gejala' => $id_gejala
        ]);
        return $stmt->rowCount() > 0;
    }
}
