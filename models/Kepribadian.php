<?php
/**
 * Kepribadian
 * -----------
 * Hipotesis tipe kepribadian DISC (H01..H04) beserta rule pendukungnya
 * (R01..R04). Menggantikan model Masalah pada versi sebelumnya.
 */
class Kepribadian
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getAllKepribadian()
    {
        $stmt = $this->db->prepare("SELECT * FROM kepribadian ORDER BY kode ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getKepribadianById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM kepribadian WHERE id_kepribadian = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function getKepribadianByTipe($tipe)
    {
        $stmt = $this->db->prepare("SELECT * FROM kepribadian WHERE tipe = :tipe");
        $stmt->bindParam(':tipe', $tipe);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function tambahKepribadian($kode, $kode_rule, $tipe, $nama, $deskripsi, $rekomendasi)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO kepribadian (kode, kode_rule, tipe, nama, deskripsi, rekomendasi)
             VALUES (:kode, :kode_rule, :tipe, :nama, :deskripsi, :rekomendasi)"
        );
        return $stmt->execute([
            ':kode'        => $kode,
            ':kode_rule'   => $kode_rule,
            ':tipe'        => $tipe,
            ':nama'        => $nama,
            ':deskripsi'   => $deskripsi,
            ':rekomendasi' => $rekomendasi,
        ]);
    }

    public function ubahKepribadian($id, $kode, $kode_rule, $tipe, $nama, $deskripsi, $rekomendasi)
    {
        $stmt = $this->db->prepare(
            "UPDATE kepribadian
                SET kode = :kode, kode_rule = :kode_rule, tipe = :tipe,
                    nama = :nama, deskripsi = :deskripsi, rekomendasi = :rekomendasi
              WHERE id_kepribadian = :id"
        );
        return $stmt->execute([
            ':id'          => $id,
            ':kode'        => $kode,
            ':kode_rule'   => $kode_rule,
            ':tipe'        => $tipe,
            ':nama'        => $nama,
            ':deskripsi'   => $deskripsi,
            ':rekomendasi' => $rekomendasi,
        ]);
    }

    public function hapusKepribadian($id)
    {
        $stmt = $this->db->prepare("DELETE FROM kepribadian WHERE id_kepribadian = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function getKepribadianPaginated($keyword = '', $limit = 10, $offset = 0)
    {
        $sql    = "SELECT * FROM kepribadian WHERE 1=1";
        $params = [];

        if (!empty($keyword)) {
            $sql .= " AND (nama LIKE :keyword OR kode LIKE :keyword OR tipe LIKE :keyword)";
            $params[':keyword'] = "%$keyword%";
        }

        $sql .= " ORDER BY kode ASC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => &$val) {
            $stmt->bindParam($key, $val);
        }
        $stmt->bindValue(':limit',  (int) $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countKepribadian($keyword = '')
    {
        $sql    = "SELECT COUNT(*) as total FROM kepribadian WHERE 1=1";
        $params = [];

        if (!empty($keyword)) {
            $sql .= " AND (nama LIKE :keyword OR kode LIKE :keyword OR tipe LIKE :keyword)";
            $params[':keyword'] = "%$keyword%";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row['total'];
    }

    public function cekKodeAda($kode, $id_exclude = null)
    {
        $sql    = "SELECT id_kepribadian FROM kepribadian WHERE kode = :kode";
        $params = [':kode' => $kode];

        if ($id_exclude) {
            $sql .= " AND id_kepribadian != :id_exclude";
            $params[':id_exclude'] = $id_exclude;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }

    public function cekTipeAda($tipe, $id_exclude = null)
    {
        $sql    = "SELECT id_kepribadian FROM kepribadian WHERE tipe = :tipe";
        $params = [':tipe' => $tipe];

        if ($id_exclude) {
            $sql .= " AND id_kepribadian != :id_exclude";
            $params[':id_exclude'] = $id_exclude;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }

    /** Kode hipotesis berikutnya, mis. H05. */
    public function getNextKode()
    {
        return $this->kodeBerikutnya('kode', 'H');
    }

    /** Kode rule berikutnya, mis. R05. */
    public function getNextKodeRule()
    {
        return $this->kodeBerikutnya('kode_rule', 'R');
    }

    private function kodeBerikutnya($kolom, $awalan)
    {
        $stmt = $this->db->query("SELECT MAX(`$kolom`) as max_kode FROM kepribadian");
        $row  = $stmt->fetch();
        $max  = $row['max_kode'];

        if (!$max) {
            return $awalan . '01';
        }

        $num = (int) substr($max, 1);
        $num++;

        return $awalan . str_pad($num, 2, '0', STR_PAD_LEFT);
    }
}
