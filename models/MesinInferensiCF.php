<?php
/**
 * MesinInferensiCF
 * ----------------
 * Mesin inferensi Certainty Factor untuk identifikasi tipe kepribadian DISC.
 *
 * Rumus yang dipakai:
 *
 *     CF Evidence  = CF pakar x CF pengguna
 *     Kombinasi    = CF1 + CF2 * (1 - CF1)
 *
 * Catatan: hanya kasus CF positif yang diimplementasikan. Nilai bobot pakar
 * berada pada rentang 0.7-0.9 dan skala jawaban pengguna 0.0-1.0, sehingga
 * CF tidak pernah bernilai negatif. Aturan kombinasi untuk nilai negatif dan
 * berlawanan tanda tidak diperlukan.
 *
 * MB / MD: basis pengetahuan tidak memuat indikator yang menggugurkan hipotesis,
 * sehingga MD = 0 untuk seluruh indikator dan CF = MB - 0 = MB.
 */
class MesinInferensiCF
{
    /** Skala tingkat keyakinan pengguna. */
    const SKALA = [
        'Tidak'        => 0.0,
        'Kurang Yakin' => 0.4,
        'Cukup Yakin'  => 0.6,
        'Yakin'        => 0.8,
        'Sangat Yakin' => 1.0,
    ];

    /** Pembulatan nilai CF saat ditampilkan (bukan saat dihitung). */
    const PRESISI_CF = 5;

    /** Pembulatan persentase akhir. */
    const PRESISI_PERSEN = 2;

    /**
     * Nilai CF pengguna berdasarkan label jawaban.
     * Label yang tidak dikenali dianggap "Tidak" (0.0).
     */
    public static function cfPengguna(string $label): float
    {
        return self::SKALA[$label] ?? 0.0;
    }

    /**
     * Daftar pilihan jawaban beserta nilainya, untuk ditampilkan di form.
     */
    public static function daftarSkala(): array
    {
        return self::SKALA;
    }

    /**
     * CF Evidence = CF pakar x CF pengguna.
     */
    public static function cfEvidence(float $cfPakar, float $cfPengguna): float
    {
        return $cfPakar * $cfPengguna;
    }

    /**
     * Kombinasi dua nilai CF positif.
     *
     *     CF(1,2) = CF1 + CF2 * (1 - CF1)
     */
    public static function kombinasi(float $cf1, float $cf2): float
    {
        return $cf1 + $cf2 * (1 - $cf1);
    }

    /**
     * Gabungkan seluruh evidence menjadi satu nilai CF akhir.
     * Nilai antara TIDAK dibulatkan agar tidak terjadi akumulasi galat.
     *
     * @param float[] $evidences
     */
    public static function hitung(array $evidences): float
    {
        $evidences = array_values($evidences);
        if (count($evidences) === 0) {
            return 0.0;
        }

        $cf = (float) $evidences[0];
        for ($i = 1; $i < count($evidences); $i++) {
            $cf = self::kombinasi($cf, (float) $evidences[$i]);
        }

        return $cf;
    }

    /**
     * Hitung CF akhir sekaligus merinci setiap langkahnya, untuk ditampilkan
     * di halaman hasil dan diekspor ke Excel.
     *
     * Bentuk keluaran sengaja mengikuti MesinInferensi::hitungDenganDetail()
     * yang lama, supaya tampilan dan ekspor tidak perlu dirombak.
     *
     * @param array $gejalaData Tiap baris: [
     *     'kode_gejala' => string,
     *     'nama_gejala' => string,
     *     'nilai_cf'    => float,   // bobot pakar
     *     'cf_user'     => float,   // tingkat keyakinan pengguna
     * ]
     * @return array ['cf_akhir', 'persentase', 'evidences', 'langkah']
     */
    public static function hitungDenganDetail(array $gejalaData): array
    {
        $gejalaData = array_values($gejalaData);
        $langkah    = [];
        $evidences  = [];

        // Langkah 1: hitung CF evidence tiap indikator.
        $baris = [];
        foreach ($gejalaData as $g) {
            $cfPakar   = (float) $g['nilai_cf'];
            $cfUser    = (float) $g['cf_user'];
            $cfEv      = self::cfEvidence($cfPakar, $cfUser);
            $evidences[] = $cfEv;

            $baris[] = sprintf(
                'CF(%s) = %s x %s = %s',
                $g['kode_gejala'],
                self::angka($cfPakar),
                self::angka($cfUser),
                self::angka($cfEv)
            );
        }

        if (count($gejalaData) === 0) {
            return [
                'cf_akhir'   => 0.0,
                'persentase' => 0.0,
                'evidences'  => [],
                'langkah'    => [],
            ];
        }

        $langkah[] = [
            'judul'      => 'Langkah 1: Menghitung CF Evidence Tiap Indikator',
            'uraian'     => 'Nilai keyakinan pakar dikalikan dengan tingkat keyakinan yang dipilih pengguna.',
            'rumus'      => array_merge(
                ['Rumus: CF Evidence = CF Pakar x CF Pengguna', ''],
                $baris
            ),
            'cf_sebelum' => null,
            'cf_baru'    => self::bulat($evidences[0]),
        ];

        // Langkah 2 dan seterusnya: kombinasi bertahap.
        $cf = $evidences[0];

        if (count($evidences) === 1) {
            $langkah[] = [
                'judul'      => 'Langkah 2: Nilai Akhir',
                'uraian'     => 'Hanya terdapat satu indikator pendukung, sehingga tidak ada kombinasi yang perlu dilakukan.',
                'rumus'      => ['CF akhir = ' . self::angka($cf)],
                'cf_sebelum' => self::bulat($cf),
                'cf_baru'    => self::bulat($cf),
            ];
        }

        for ($i = 1; $i < count($evidences); $i++) {
            $cfLama = $cf;
            $cfBaru = self::kombinasi($cfLama, $evidences[$i]);

            $no   = $i + 1;
            $kode = $gejalaData[$i]['kode_gejala'];

            $langkah[] = [
                'judul'  => "Langkah {$no}: Kombinasi dengan {$kode}",
                'uraian' => 'Menggabungkan nilai keyakinan sebelumnya dengan CF evidence indikator berikutnya.',
                'rumus'  => [
                    'Rumus: CFkombinasi = CFlama + CFbaru x (1 - CFlama)',
                    '',
                    sprintf('CFlama = %s', self::angka($cfLama)),
                    sprintf('CF(%s) = %s', $kode, self::angka($evidences[$i])),
                    '',
                    sprintf(
                        'CFkombinasi = %s + [%s x (1 - %s)]',
                        self::angka($cfLama),
                        self::angka($evidences[$i]),
                        self::angka($cfLama)
                    ),
                    sprintf('CFkombinasi = %s', self::angka($cfBaru)),
                ],
                'cf_sebelum' => self::bulat($cfLama),
                'cf_baru'    => self::bulat($cfBaru),
            ];

            $cf = $cfBaru;
        }

        $persentase = self::persentase($cf);

        $langkah[] = [
            'judul'  => 'Langkah Akhir: Konversi ke Persentase',
            'uraian' => 'Nilai CF akhir diubah menjadi persentase tingkat keyakinan.',
            'rumus'  => [
                sprintf('CF akhir   = %s', self::angka($cf)),
                sprintf('Persentase = %s x 100%% = %s%%', self::angka($cf), $persentase),
            ],
            'cf_sebelum' => self::bulat($cf),
            'cf_baru'    => self::bulat($cf),
        ];

        return [
            'cf_akhir'   => self::bulat($cf),
            'persentase' => $persentase,
            'evidences'  => array_map([self::class, 'bulat'], $evidences),
            'langkah'    => $langkah,
        ];
    }

    /**
     * Urutkan hasil seluruh hipotesis dari yang tertinggi.
     * Nilai yang sama memperoleh nomor peringkat yang sama.
     *
     * @param array $perHipotesis Tiap baris minimal memuat 'cf_akhir'.
     * @return array Baris yang sama, terurut menurun, dengan tambahan 'peringkat'.
     */
    public static function peringkatHipotesis(array $perHipotesis): array
    {
        $data = array_values($perHipotesis);

        usort($data, function ($a, $b) {
            return $b['cf_akhir'] <=> $a['cf_akhir'];
        });

        $peringkat   = 0;
        $nilaiTerakhir = null;

        foreach ($data as $i => $baris) {
            $nilai = self::bulat((float) $baris['cf_akhir']);
            if ($nilaiTerakhir === null || $nilai !== $nilaiTerakhir) {
                $peringkat     = $i + 1;
                $nilaiTerakhir = $nilai;
            }
            $data[$i]['peringkat'] = $peringkat;
        }

        return $data;
    }

    /**
     * Susun log penelusuran Backward Chaining.
     *
     * Penelusuran berangkat dari hipotesis (H01..H04), menuju aturan
     * (R01..R04), lalu memeriksa indikator pendukungnya. Log dibangun ulang
     * dari data setiap kali dipanggil, sehingga tidak bergantung pada isi
     * sesi dan selalu konsisten antara layar, basis data, dan berkas ekspor.
     *
     * @param array $rencana Keluaran Aturan::getSemuaAturanPerKepribadian()
     * @param array $jawaban [id_gejala => cf_user] jawaban yang sudah masuk
     * @return string[] Baris log dalam teks polos
     */
    public static function logBackwardChaining(array $rencana, array $jawaban): array
    {
        $log   = [];
        $label = array_flip(array_map('strval', self::SKALA));

        foreach ($rencana as $h) {
            $kodeGejala = array_column($h['gejala'], 'kode_gejala');

            $log[] = sprintf(
                'Menetapkan %s (%s - %s) sebagai tujuan, lalu menelusuri aturan %s.',
                $h['kode'], $h['tipe'], $h['nama'], $h['kode_rule']
            );
            $log[] = sprintf(
                '  Aturan %s memeriksa %d indikator: %s.',
                $h['kode_rule'], count($kodeGejala), implode(', ', $kodeGejala)
            );

            $evidences = [];
            $terjawab  = 0;

            foreach ($h['gejala'] as $g) {
                if (!array_key_exists($g['id_gejala'], $jawaban)) {
                    continue;
                }

                $terjawab++;
                $cfUser = (float) $jawaban[$g['id_gejala']];
                $cfEv   = self::cfEvidence((float) $g['nilai_cf'], $cfUser);
                $evidences[] = $cfEv;

                $namaSkala = $label[(string) $cfUser] ?? (string) $cfUser;

                $log[] = sprintf(
                    '  %s dijawab "%s" (%s). CF evidence = %s x %s = %s.',
                    $g['kode_gejala'],
                    $namaSkala,
                    self::angka($cfUser),
                    self::angka((float) $g['nilai_cf']),
                    self::angka($cfUser),
                    self::angka($cfEv)
                );
            }

            if ($terjawab === 0) {
                $log[] = sprintf('  %s belum diperiksa.', $h['kode_rule']);
            } elseif ($terjawab < count($h['gejala'])) {
                $log[] = sprintf(
                    '  %s baru terisi %d dari %d indikator, penelusuran berlanjut.',
                    $h['kode_rule'], $terjawab, count($h['gejala'])
                );
            } else {
                $cf = self::hitung($evidences);
                $log[] = sprintf(
                    '  Seluruh indikator %s terperiksa. CF akhir %s = %s (%s%%). Hipotesis DIDUKUNG.',
                    $h['kode_rule'], $h['kode'], self::angka($cf), self::persentase($cf)
                );
            }

            $log[] = '';
        }

        // Buang baris kosong di akhir
        while (count($log) > 0 && end($log) === '') {
            array_pop($log);
        }

        return $log;
    }

    /** Pembulatan nilai CF untuk ditampilkan atau disimpan. */
    public static function bulat(float $cf): float
    {
        return round($cf, self::PRESISI_CF);
    }

    /** Konversi nilai CF menjadi persentase. */
    public static function persentase(float $cf): float
    {
        return round($cf * 100, self::PRESISI_PERSEN);
    }

    /** Format angka agar rapi dibaca pada rincian langkah. */
    private static function angka(float $n): string
    {
        $s = rtrim(rtrim(number_format($n, self::PRESISI_CF, '.', ''), '0'), '.');
        return $s === '' || $s === '-' ? '0' : $s;
    }
}
