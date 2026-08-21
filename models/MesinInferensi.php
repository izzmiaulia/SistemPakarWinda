<?php
class MesinInferensi {
    /**
     * Menghitung nilai probabilitas dengan Dempster-Shafer
     * dan mengembalikan array detail langkah-langkah perhitungan.
     */
    public static function hitungDempsterShafer($beliefs) {
        if(count($beliefs) == 0) return 0;
        
        // Inisialisasi dengan gejala pertama
        $m_H = $beliefs[0];
        $m_Theta = 1 - $m_H;

        // Kombinasi Dempster Shafer untuk gejala ke-2 dan seterusnya
        for($i = 1; $i < count($beliefs); $i++) {
            $m2_H = $beliefs[$i];
            $m2_Theta = 1 - $m2_H;

            // M_baru(H) = M1(H)*M2(H) + M1(H)*M2(Theta) + M1(Theta)*M2(H)
            $m_baru_H = ($m_H * $m2_H) + ($m_H * $m2_Theta) + ($m_Theta * $m2_H);
            
            // M_baru(Theta) = M1(Theta) * M2(Theta)
            $m_baru_Theta = $m_Theta * $m2_Theta;

            $m_H = $m_baru_H;
            $m_Theta = $m_baru_Theta;
        }

        return round($m_H * 100, 2);
    }

    /**
     * Menghitung Dempster-Shafer dengan mengembalikan detail setiap langkah
     * untuk keperluan tampilan perhitungan manual.
     */
    public static function hitungDenganDetail($gejala_data) {
        // $gejala_data: array of ['kode_gejala', 'nama_gejala', 'nilai_belief']
        
        if (count($gejala_data) == 0) {
            return ['persentase' => 0, 'langkah' => []];
        }

        $langkah = [];

        // === LANGKAH 1: Inisialisasi Mass Function Awal ===
        $g1 = $gejala_data[0];
        $m_H     = (float) $g1['nilai_belief'];
        $m_Theta = round(1 - $m_H, 4);

        $langkah[] = [
            'judul'    => "Langkah 1: Inisialisasi — Gejala {$g1['kode_gejala']} ({$g1['nama_gejala']})",
            'uraian'   => "Gejala pertama digunakan sebagai nilai mass function awal (m₁).",
            'rumus'    => [
                "m₁({H})     = nilai belief gejala {$g1['kode_gejala']} = {$m_H}",
                "m₁({Θ})     = 1 − m₁({H}) = 1 − {$m_H} = {$m_Theta}",
            ],
            'm_H'     => $m_H,
            'm_Theta' => $m_Theta,
        ];

        // === LANGKAH 2+: Kombinasi Dempster-Shafer ===
        for ($i = 1; $i < count($gejala_data); $i++) {
            $g2       = $gejala_data[$i];
            $m2_H     = (float) $g2['nilai_belief'];
            $m2_Theta = round(1 - $m2_H, 4);

            // Tiga komponen numerator untuk M_baru(H)
            $comp1 = round($m_H * $m2_H, 6);      // M1(H) × M2(H)
            $comp2 = round($m_H * $m2_Theta, 6);  // M1(H) × M2(Θ)
            $comp3 = round($m_Theta * $m2_H, 6);  // M1(Θ) × M2(H)

            $m_baru_H     = round($comp1 + $comp2 + $comp3, 6);
            $m_baru_Theta = round($m_Theta * $m2_Theta, 6);
            
            // Conflict K = 0 karena hipotesis tunggal
            // (M1(H) × M2(¬H) + M1(¬H) × M2(H) tidak berlaku di single-hypothesis)
            // Normalisasi (1 - K) = 1

            $no = $i + 1;
            $langkah[] = [
                'judul'  => "Langkah {$no}: Kombinasi dengan Gejala {$g2['kode_gejala']} ({$g2['nama_gejala']})",
                'uraian' => "Gabungkan mass function sebelumnya (m" . ($i) . ") dengan gejala baru menggunakan aturan kombinasi Dempster.",
                'rumus'  => [
                    "m" . $i . "({H})   = {$m_H}   │   m" . $i . "({Θ}) = {$m_Theta}",
                    "m" . $no . "({H})   = nilai belief {$g2['kode_gejala']} = {$m2_H}   │   m" . $no . "({Θ}) = 1 − {$m2_H} = {$m2_Theta}",
                    "───────────────────────────────────────────────",
                    "Rumus: m_baru({H}) = [m" . $i . "({H})×m" . $no . "({H})] + [m" . $i . "({H})×m" . $no . "({Θ})] + [m" . $i . "({Θ})×m" . $no . "({H})]",
                    "m_baru({H}) = [{$m_H}×{$m2_H}] + [{$m_H}×{$m2_Theta}] + [{$m_Theta}×{$m2_H}]",
                    "m_baru({H}) = {$comp1} + {$comp2} + {$comp3} = {$m_baru_H}",
                    "",
                    "Rumus: m_baru({Θ}) = m" . $i . "({Θ}) × m" . $no . "({Θ})",
                    "m_baru({Θ}) = {$m_Theta} × {$m2_Theta} = {$m_baru_Theta}",
                    "",
                    "Nilai Konflik (K) = 0  →  Tidak ada konflik (hipotesis tunggal)",
                    "Faktor Normalisasi (1 − K) = 1",
                ],
                'm_H'     => $m_baru_H,
                'm_Theta' => $m_baru_Theta,
            ];

            $m_H     = $m_baru_H;
            $m_Theta = $m_baru_Theta;
        }

        // === LANGKAH AKHIR: Konversi ke Persentase ===
        $persentase = round($m_H * 100, 2);
        $langkah[] = [
            'judul'  => "Langkah Akhir: Konversi ke Persentase",
            'uraian' => "Nilai m_final({H}) diubah menjadi persentase keyakinan akhir.",
            'rumus'  => [
                "m_final({H}) = {$m_H}",
                "Persentase   = m_final({H}) × 100%",
                "Persentase   = {$m_H} × 100% = {$persentase}%",
            ],
            'm_H'     => $m_H,
            'm_Theta' => $m_Theta,
        ];

        return [
            'persentase' => $persentase,
            'langkah'    => $langkah,
        ];
    }
}
