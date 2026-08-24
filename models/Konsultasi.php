<?php
/**
 * Konsultasi
 * ----------
 * Penyimpanan dan pembacaan sesi konsultasi: jawaban pengguna atas setiap
 * indikator, skor keempat hipotesis, serta hipotesis dengan keyakinan
 * tertinggi.
 */
class Konsultasi
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /** Kueri dasar riwayat beserta hipotesis pemenangnya. */
    private function sqlRiwayat()
    {
        return "
            SELECT r.id_konsultasi, r.tanggal,
                   s.nis, s.nama_siswa, s.kelas,
                   h.nilai_persentase,
                   k.kode, k.tipe, k.nama AS nama_kepribadian
              FROM riwayat_konsultasi r
              JOIN siswa s            ON r.id_siswa       = s.id_siswa
              JOIN hasil_konsultasi h ON r.id_konsultasi  = h.id_konsultasi
              JOIN kepribadian k      ON h.id_kepribadian = k.id_kepribadian
        ";
    }

    public function getRiwayat()
    {
        $stmt = $this->db->prepare($this->sqlRiwayat() . " ORDER BY r.tanggal DESC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getRiwayatPaginated($keyword = '', $limit = 10, $offset = 0)
    {
        $sql    = $this->sqlRiwayat() . " WHERE 1=1";
        $params = [];

        if (!empty($keyword)) {
            $sql .= " AND (s.nama_siswa LIKE :keyword OR s.kelas LIKE :keyword
                           OR s.nis LIKE :keyword OR k.nama LIKE :keyword)";
            $params[':keyword'] = "%$keyword%";
        }

        $sql .= " ORDER BY r.tanggal DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => &$val) {
            $stmt->bindParam($key, $val);
        }
        $stmt->bindValue(':limit',  (int) $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countRiwayat($keyword = '')
    {
        $sql = "
            SELECT COUNT(*) as total
              FROM riwayat_konsultasi r
              JOIN siswa s            ON r.id_siswa       = s.id_siswa
              JOIN hasil_konsultasi h ON r.id_konsultasi  = h.id_konsultasi
              JOIN kepribadian k      ON h.id_kepribadian = k.id_kepribadian
             WHERE 1=1
        ";
        $params = [];

        if (!empty($keyword)) {
            $sql .= " AND (s.nama_siswa LIKE :keyword OR s.kelas LIKE :keyword
                           OR s.nis LIKE :keyword OR k.nama LIKE :keyword)";
            $params[':keyword'] = "%$keyword%";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row['total'];
    }

    /**
     * Rincian satu sesi: identitas, hipotesis pemenang, jawaban tiap indikator,
     * dan skor keempat hipotesis.
     */
    public function getDetailKonsultasi($id_konsultasi)
    {
        $stmt = $this->db->prepare("
            SELECT r.id_konsultasi, r.tanggal,
                   s.nis, s.nama_siswa, s.kelas, s.jenis_kelamin,
                   h.nilai_persentase, h.log_proses,
                   k.id_kepribadian, k.kode, k.kode_rule, k.tipe,
                   k.nama AS nama_kepribadian, k.deskripsi, k.rekomendasi
              FROM riwayat_konsultasi r
              JOIN siswa s            ON r.id_siswa       = s.id_siswa
              JOIN hasil_konsultasi h ON r.id_konsultasi  = h.id_konsultasi
              JOIN kepribadian k      ON h.id_kepribadian = k.id_kepribadian
             WHERE r.id_konsultasi = :id
        ");
        $stmt->execute([':id' => $id_konsultasi]);
        $detail = $stmt->fetch();

        if (!$detail) {
            return null;
        }

        // Jawaban pengguna atas seluruh indikator, beserta bobot pakar saat itu.
        $stmt2 = $this->db->prepare("
            SELECT g.id_gejala, g.kode_gejala, g.nama_gejala,
                   d.cf_user, d.cf_pakar,
                   k.id_kepribadian, k.kode AS kode_kepribadian, k.kode_rule,
                   k.tipe, k.nama AS nama_kepribadian
              FROM detail_konsultasi d
              JOIN gejala g      ON d.id_gejala      = g.id_gejala
         LEFT JOIN aturan a      ON a.id_gejala      = g.id_gejala
         LEFT JOIN kepribadian k ON k.id_kepribadian = a.id_kepribadian
             WHERE d.id_konsultasi = :id
             ORDER BY g.kode_gejala ASC
        ");
        $stmt2->execute([':id' => $id_konsultasi]);
        $detail['jawaban'] = $stmt2->fetchAll();

        // Skor keempat hipotesis.
        $stmt3 = $this->db->prepare("
            SELECT hd.id_kepribadian, hd.cf_akhir, hd.persentase, hd.peringkat,
                   k.kode, k.kode_rule, k.tipe, k.nama, k.deskripsi, k.rekomendasi
              FROM hasil_detail hd
              JOIN kepribadian k ON hd.id_kepribadian = k.id_kepribadian
             WHERE hd.id_konsultasi = :id
             ORDER BY hd.peringkat ASC, k.kode ASC
        ");
        $stmt3->execute([':id' => $id_konsultasi]);
        $detail['peringkat'] = $stmt3->fetchAll();

        return $detail;
    }

    /**
     * Simpan satu sesi konsultasi secara utuh.
     *
     * @param int   $id_siswa
     * @param array $jawaban           [id_gejala => ['cf_user' => float, 'cf_pakar' => float]]
     *                                 Seluruh indikator, termasuk yang dijawab 0.0
     * @param array $hasilPerHipotesis [['id_kepribadian','cf_akhir','persentase','peringkat'], ...]
     * @param string|null $log_proses  Log penelusuran backward chaining
     * @return int id_konsultasi
     */
    public function simpanKonsultasi($id_siswa, array $jawaban, array $hasilPerHipotesis, $log_proses = null)
    {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare(
                "INSERT INTO riwayat_konsultasi (id_siswa, tanggal) VALUES (:id_siswa, :tanggal)"
            );
            $stmt->execute([':id_siswa' => $id_siswa, ':tanggal' => date('Y-m-d H:i:s')]);
            $id_konsultasi = (int) $this->db->lastInsertId();

            $stmtJawab = $this->db->prepare(
                "INSERT INTO detail_konsultasi (id_konsultasi, id_gejala, cf_user, cf_pakar)
                 VALUES (:id_kons, :id_gejala, :cf_user, :cf_pakar)"
            );
            foreach ($jawaban as $id_gejala => $nilai) {
                $stmtJawab->execute([
                    ':id_kons'   => $id_konsultasi,
                    ':id_gejala' => $id_gejala,
                    ':cf_user'   => is_array($nilai) ? $nilai['cf_user']  : $nilai,
                    ':cf_pakar'  => is_array($nilai) ? $nilai['cf_pakar'] : 0,
                ]);
            }

            $stmtDetail = $this->db->prepare(
                "INSERT INTO hasil_detail (id_konsultasi, id_kepribadian, cf_akhir, persentase, peringkat)
                 VALUES (:id_kons, :id_kepribadian, :cf_akhir, :persentase, :peringkat)"
            );

            $pemenang = null;
            foreach ($hasilPerHipotesis as $h) {
                $stmtDetail->execute([
                    ':id_kons'        => $id_konsultasi,
                    ':id_kepribadian' => $h['id_kepribadian'],
                    ':cf_akhir'       => $h['cf_akhir'],
                    ':persentase'     => $h['persentase'],
                    ':peringkat'      => $h['peringkat'],
                ]);

                if ((int) $h['peringkat'] === 1 && $pemenang === null) {
                    $pemenang = $h;
                }
            }

            if ($pemenang === null && count($hasilPerHipotesis) > 0) {
                $pemenang = $hasilPerHipotesis[0];
            }

            if ($pemenang !== null) {
                $stmtHasil = $this->db->prepare(
                    "INSERT INTO hasil_konsultasi (id_konsultasi, id_kepribadian, nilai_persentase, log_proses)
                     VALUES (:id_kons, :id_kepribadian, :persentase, :log_proses)"
                );
                $stmtHasil->execute([
                    ':id_kons'        => $id_konsultasi,
                    ':id_kepribadian' => $pemenang['id_kepribadian'],
                    ':persentase'     => $pemenang['persentase'],
                    ':log_proses'     => $log_proses,
                ]);
            }

            $this->db->commit();
            return $id_konsultasi;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /** Penghapusan cukup pada tabel induk; anaknya ikut terhapus lewat ON DELETE CASCADE. */
    public function hapusKonsultasi($id_konsultasi)
    {
        $stmt = $this->db->prepare("DELETE FROM riwayat_konsultasi WHERE id_konsultasi = :id");
        return $stmt->execute([':id' => (int) $id_konsultasi]);
    }

    public function hapusMassalKonsultasi($ids)
    {
        if (empty($ids)) {
            return false;
        }

        $ids          = array_map('intval', $ids);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $stmt = $this->db->prepare("DELETE FROM riwayat_konsultasi WHERE id_konsultasi IN ($placeholders)");
        return $stmt->execute($ids);
    }

    public function getStats()
    {
        $stats = [];

        $stats['siswa']       = $this->db->query("SELECT COUNT(*) as total FROM siswa")->fetch()['total'];
        $stats['kepribadian'] = $this->db->query("SELECT COUNT(*) as total FROM kepribadian")->fetch()['total'];
        $stats['gejala']      = $this->db->query("SELECT COUNT(*) as total FROM gejala")->fetch()['total'];
        $stats['konsultasi']  = $this->db->query("SELECT COUNT(*) as total FROM riwayat_konsultasi")->fetch()['total'];

        return $stats;
    }
}
