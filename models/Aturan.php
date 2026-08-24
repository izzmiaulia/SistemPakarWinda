<?php
/**
 * Aturan
 * ------
 * Basis pengetahuan: relasi antara hipotesis kepribadian dan indikator,
 * beserta nilai keyakinan pakar (CF pakar) pada rentang 0..1.
 */
class Aturan
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /** Seluruh indikator pendukung satu hipotesis, terurut menurut kode. */
    public function getAturanByKepribadian($id_kepribadian)
    {
        $stmt = $this->db->prepare("
            SELECT a.*, g.kode_gejala, g.nama_gejala
              FROM aturan a
              JOIN gejala g ON a.id_gejala = g.id_gejala
             WHERE a.id_kepribadian = :id_kepribadian
             ORDER BY g.kode_gejala ASC
        ");
        $stmt->bindParam(':id_kepribadian', $id_kepribadian);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** Satu baris aturan berdasarkan pasangan hipotesis dan indikator. */
    public function getAturanByKepribadianAndGejala($id_kepribadian, $id_gejala)
    {
        $stmt = $this->db->prepare("
            SELECT a.id_aturan, a.nilai_cf, g.id_gejala, g.kode_gejala, g.nama_gejala
              FROM aturan a
              JOIN gejala g ON a.id_gejala = g.id_gejala
             WHERE a.id_kepribadian = :id_kepribadian
               AND a.id_gejala      = :id_gejala
        ");
        $stmt->bindParam(':id_kepribadian', $id_kepribadian);
        $stmt->bindParam(':id_gejala', $id_gejala);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function getAturanById($id_aturan)
    {
        $stmt = $this->db->prepare("
            SELECT a.*, g.kode_gejala, g.nama_gejala, k.kode, k.nama AS nama_kepribadian
              FROM aturan a
              JOIN gejala g      ON a.id_gejala      = g.id_gejala
              JOIN kepribadian k ON a.id_kepribadian = k.id_kepribadian
             WHERE a.id_aturan = :id
        ");
        $stmt->execute([':id' => $id_aturan]);
        return $stmt->fetch();
    }

    /**
     * Seluruh aturan sekaligus, dikelompokkan menurut hipotesis.
     * Dipakai mesin inferensi agar tidak melakukan kueri berulang.
     */
    public function getSemuaAturanPerKepribadian()
    {
        $stmt = $this->db->query("
            SELECT k.id_kepribadian, k.kode, k.kode_rule, k.tipe, k.nama, k.deskripsi, k.rekomendasi,
                   g.id_gejala, g.kode_gejala, g.nama_gejala, a.nilai_cf
              FROM kepribadian k
              JOIN aturan a ON a.id_kepribadian = k.id_kepribadian
              JOIN gejala g ON g.id_gejala      = a.id_gejala
             ORDER BY k.kode ASC, g.kode_gejala ASC
        ");

        $hasil = [];
        foreach ($stmt->fetchAll() as $row) {
            $id = $row['id_kepribadian'];

            if (!isset($hasil[$id])) {
                $hasil[$id] = [
                    'id_kepribadian' => $id,
                    'kode'           => $row['kode'],
                    'kode_rule'      => $row['kode_rule'],
                    'tipe'           => $row['tipe'],
                    'nama'           => $row['nama'],
                    'deskripsi'      => $row['deskripsi'],
                    'rekomendasi'    => $row['rekomendasi'],
                    'gejala'         => [],
                ];
            }

            $hasil[$id]['gejala'][] = [
                'id_gejala'   => $row['id_gejala'],
                'kode_gejala' => $row['kode_gejala'],
                'nama_gejala' => $row['nama_gejala'],
                'nilai_cf'    => (float) $row['nilai_cf'],
            ];
        }

        return array_values($hasil);
    }

    public function tambahAturan($id_kepribadian, $id_gejala, $nilai_cf)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO aturan (id_kepribadian, id_gejala, nilai_cf)
             VALUES (:id_kepribadian, :id_gejala, :nilai_cf)"
        );
        return $stmt->execute([
            ':id_kepribadian' => $id_kepribadian,
            ':id_gejala'      => $id_gejala,
            ':nilai_cf'       => $nilai_cf,
        ]);
    }

    /**
     * Ubah nilai keyakinan pakar pada satu aturan.
     * Dipakai agar hasil validasi pakar dapat diterapkan tanpa mengubah kode.
     */
    public function ubahNilaiCf($id_aturan, $nilai_cf)
    {
        $stmt = $this->db->prepare("UPDATE aturan SET nilai_cf = :nilai_cf WHERE id_aturan = :id");
        return $stmt->execute([':id' => $id_aturan, ':nilai_cf' => $nilai_cf]);
    }

    public function hapusAturan($id_aturan)
    {
        $stmt = $this->db->prepare("DELETE FROM aturan WHERE id_aturan = :id");
        return $stmt->execute([':id' => $id_aturan]);
    }

    public function cekRelasiAda($id_kepribadian, $id_gejala)
    {
        $stmt = $this->db->prepare(
            "SELECT id_aturan FROM aturan
              WHERE id_kepribadian = :id_kepribadian AND id_gejala = :id_gejala"
        );
        $stmt->execute([
            ':id_kepribadian' => $id_kepribadian,
            ':id_gejala'      => $id_gejala,
        ]);
        return $stmt->rowCount() > 0;
    }
}
