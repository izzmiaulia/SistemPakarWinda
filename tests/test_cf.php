<?php
/**
 * Uji Mesin Inferensi Certainty Factor
 * ------------------------------------
 * Angka acuan diambil langsung dari Bab IV naskah skripsi (bagian 4.1.1, 4.2,
 * 4.3, dan 4.3.1). Bila seluruh uji lulus, keluaran aplikasi dijamin sama
 * persis dengan yang tertulis di naskah.
 *
 * Jalankan:  php tests/test_cf.php
 */

require_once __DIR__ . '/../models/MesinInferensiCF.php';

// ─── Kerangka uji sederhana ──────────────────────────────────────────────────

$lulus = 0;
$gagal = 0;

function periksa(string $nama, $harapan, $aktual, float $toleransi = 0.0): void
{
    global $lulus, $gagal;

    if (is_float($harapan) || is_float($aktual)) {
        $cocok = abs((float) $harapan - (float) $aktual) <= $toleransi;
    } else {
        $cocok = $harapan === $aktual;
    }

    if ($cocok) {
        $lulus++;
        printf("  [ OK ] %s\n", $nama);
    } else {
        $gagal++;
        printf("  [GAGAL] %s\n", $nama);
        printf("         harapan : %s\n", var_export($harapan, true));
        printf("         aktual  : %s\n", var_export($aktual, true));
    }
}

function bagian(string $judul): void
{
    printf("\n%s\n%s\n", $judul, str_repeat('-', strlen($judul)));
}

// ─── Data acuan dari Bab IV ──────────────────────────────────────────────────

/** Bobot pakar tiap indikator (tabel 4.1.1). */
const CF_PAKAR = [
    'G01' => 0.8, 'G02' => 0.9, 'G03' => 0.8, 'G04' => 0.7,   // Dominance
    'G05' => 0.8, 'G06' => 0.9, 'G07' => 0.8, 'G08' => 0.7,   // Influence
    'G09' => 0.9, 'G10' => 0.8, 'G11' => 0.8, 'G12' => 0.7,   // Steadiness
    'G13' => 0.8, 'G14' => 0.9, 'G15' => 0.8, 'G16' => 0.7,   // Compliance
];

/** Jawaban pengguna pada Kasus 1 (bagian 4.3 dan 4.3.1). */
const CF_PENGGUNA_KASUS_1 = [
    'G01' => 1.0, 'G02' => 0.8, 'G03' => 0.6, 'G04' => 0.8,
    'G05' => 0.4, 'G06' => 0.6, 'G07' => 0.4, 'G08' => 0.6,
    'G09' => 0.6, 'G10' => 0.4, 'G11' => 0.6, 'G12' => 0.8,
    'G13' => 0.4, 'G14' => 0.6, 'G15' => 0.4, 'G16' => 0.6,
];

/** Pemetaan rule R01-R04. */
const RULE = [
    'Dominance'  => ['G01', 'G02', 'G03', 'G04'],
    'Influence'  => ['G05', 'G06', 'G07', 'G08'],
    'Steadiness' => ['G09', 'G10', 'G11', 'G12'],
    'Compliance' => ['G13', 'G14', 'G15', 'G16'],
];

// ─── 1. Skala jawaban pengguna (bagian 4.2) ─────────────────────────────────

bagian('1. Skala keyakinan pengguna');

periksa('Tidak = 0.0',        0.0, MesinInferensiCF::cfPengguna('Tidak'));
periksa('Kurang Yakin = 0.4', 0.4, MesinInferensiCF::cfPengguna('Kurang Yakin'));
periksa('Cukup Yakin = 0.6',  0.6, MesinInferensiCF::cfPengguna('Cukup Yakin'));
periksa('Yakin = 0.8',        0.8, MesinInferensiCF::cfPengguna('Yakin'));
periksa('Sangat Yakin = 1.0', 1.0, MesinInferensiCF::cfPengguna('Sangat Yakin'));
periksa('Label tak dikenal jatuh ke 0.0', 0.0, MesinInferensiCF::cfPengguna('Entah'));
periksa('Skala berisi tepat 5 pilihan', 5, count(MesinInferensiCF::daftarSkala()));

// ─── 2. CF Evidence tiap indikator (tabel bagian 4.3) ───────────────────────

bagian('2. CF Evidence = CF pakar x CF pengguna');

$evidenceHarapan = [
    'G01' => 0.80, 'G02' => 0.72, 'G03' => 0.48, 'G04' => 0.56,
    'G05' => 0.32, 'G06' => 0.54, 'G07' => 0.32, 'G08' => 0.42,
    'G09' => 0.54, 'G10' => 0.32, 'G11' => 0.48, 'G12' => 0.56,
    'G13' => 0.32, 'G14' => 0.54, 'G15' => 0.32, 'G16' => 0.42,
];

foreach ($evidenceHarapan as $kode => $harapan) {
    $aktual = MesinInferensiCF::cfEvidence(CF_PAKAR[$kode], CF_PENGGUNA_KASUS_1[$kode]);
    periksa("CF({$kode})", $harapan, round($aktual, 5), 0.000001);
}

// ─── 3. Kombinasi bertahap Dominance (bagian 4.3) ───────────────────────────

bagian('3. Kombinasi bertahap - Dominance');

$cf = 0.80;
periksa('Awal (G01)', 0.80, round($cf, 5), 0.000001);

$cf = MesinInferensiCF::kombinasi($cf, 0.72);
periksa('+ G02 = 0.944', 0.944, round($cf, 5), 0.000001);

$cf = MesinInferensiCF::kombinasi($cf, 0.48);
periksa('+ G03 = 0.97088', 0.97088, round($cf, 5), 0.000001);

$cf = MesinInferensiCF::kombinasi($cf, 0.56);
periksa('+ G04 = 0.9871872', 0.9871872, $cf, 0.0000001);

// ─── 4. CF akhir keempat hipotesis (tabel bagian 4.3.1) ─────────────────────

bagian('4. CF akhir keempat hipotesis');

$harapanAkhir = [
    'Dominance'  => ['cf' => 0.98719, 'persen' => 98.72],
    'Influence'  => ['cf' => 0.87663, 'persen' => 87.66],
    'Steadiness' => ['cf' => 0.92843, 'persen' => 92.84],
    'Compliance' => ['cf' => 0.87663, 'persen' => 87.66],
];

$hasilPerHipotesis = [];

foreach (RULE as $tipe => $kodeGejala) {
    $gejalaData = [];
    foreach ($kodeGejala as $kode) {
        $gejalaData[] = [
            'kode_gejala' => $kode,
            'nama_gejala' => 'Indikator ' . $kode,
            'nilai_cf'    => CF_PAKAR[$kode],
            'cf_user'     => CF_PENGGUNA_KASUS_1[$kode],
        ];
    }

    $hasil = MesinInferensiCF::hitungDenganDetail($gejalaData);

    periksa("{$tipe} - CF akhir",   $harapanAkhir[$tipe]['cf'],     $hasil['cf_akhir'],   0.000001);
    periksa("{$tipe} - persentase", $harapanAkhir[$tipe]['persen'], $hasil['persentase'], 0.001);

    $hasilPerHipotesis[] = ['tipe' => $tipe, 'cf_akhir' => $hasil['cf_akhir']];
}

// ─── 5. Peringkat hipotesis ─────────────────────────────────────────────────

bagian('5. Peringkat hipotesis');

$peringkat = MesinInferensiCF::peringkatHipotesis($hasilPerHipotesis);

periksa('Peringkat 1 = Dominance',  'Dominance',  $peringkat[0]['tipe']);
periksa('Peringkat 2 = Steadiness', 'Steadiness', $peringkat[1]['tipe']);
periksa('Nomor peringkat teratas',  1,            $peringkat[0]['peringkat']);
periksa('Nomor peringkat kedua',    2,            $peringkat[1]['peringkat']);
periksa('Influence dan Compliance seri di peringkat 3', 3, $peringkat[2]['peringkat']);
periksa('Seri memperoleh nomor sama',                   3, $peringkat[3]['peringkat']);

// ─── 6. Rincian langkah ─────────────────────────────────────────────────────

bagian('6. Rincian langkah perhitungan');

$gejalaD = [];
foreach (RULE['Dominance'] as $kode) {
    $gejalaD[] = [
        'kode_gejala' => $kode,
        'nama_gejala' => 'Indikator ' . $kode,
        'nilai_cf'    => CF_PAKAR[$kode],
        'cf_user'     => CF_PENGGUNA_KASUS_1[$kode],
    ];
}
$detail = MesinInferensiCF::hitungDenganDetail($gejalaD);

// 1 langkah evidence + 3 kombinasi + 1 konversi = 5
periksa('Jumlah langkah = 5', 5, count($detail['langkah']));

$wajib = ['judul', 'uraian', 'rumus', 'cf_sebelum', 'cf_baru'];
$bentukBenar = true;
foreach ($detail['langkah'] as $lk) {
    foreach ($wajib as $kunci) {
        if (!array_key_exists($kunci, $lk)) {
            $bentukBenar = false;
        }
    }
    if (!is_array($lk['rumus'])) {
        $bentukBenar = false;
    }
}
periksa('Bentuk tiap langkah sesuai kontrak tampilan', true, $bentukBenar);
periksa('CF akhir pada langkah terakhir', 0.98719, $detail['langkah'][4]['cf_baru'], 0.000001);
periksa('Daftar evidence ikut dikembalikan', 4, count($detail['evidences']));

// ─── 7. Kasus tepi ──────────────────────────────────────────────────────────

bagian('7. Kasus tepi');

periksa('Tanpa evidence -> 0.0', 0.0, MesinInferensiCF::hitung([]), 0.000001);

$kosong = MesinInferensiCF::hitungDenganDetail([]);
periksa('Detail tanpa evidence tidak error', 0.0, $kosong['cf_akhir'], 0.000001);
periksa('Detail tanpa evidence tanpa langkah', 0, count($kosong['langkah']));

periksa('Satu evidence -> nilainya sendiri', 0.8, MesinInferensiCF::hitung([0.8]), 0.000001);

$satu = MesinInferensiCF::hitungDenganDetail([[
    'kode_gejala' => 'G01', 'nama_gejala' => 'Indikator G01',
    'nilai_cf'    => 0.8,   'cf_user'     => 1.0,
]]);
periksa('Detail satu evidence -> 0.8',   0.8,  $satu['cf_akhir'],   0.000001);
periksa('Detail satu evidence -> 80%',   80.0, $satu['persentase'], 0.001);

periksa('Jawaban "Tidak" menihilkan evidence', 0.0, MesinInferensiCF::cfEvidence(0.9, 0.0), 0.000001);

$semuaTidak = MesinInferensiCF::hitung([0.0, 0.0, 0.0, 0.0]);
periksa('Seluruh jawaban "Tidak" -> 0.0', 0.0, $semuaTidak, 0.000001);

periksa('CF tidak pernah melampaui 1.0', true, MesinInferensiCF::hitung([0.9, 0.9, 0.9, 0.9]) <= 1.0);
periksa('Kombinasi bersifat komutatif',
    round(MesinInferensiCF::hitung([0.3, 0.7]), 10),
    round(MesinInferensiCF::hitung([0.7, 0.3]), 10),
    0.0000000001
);

// ─── Ringkasan ──────────────────────────────────────────────────────────────

printf("\n%s\n", str_repeat('=', 52));
printf("  Lulus : %d\n", $lulus);
printf("  Gagal : %d\n", $gagal);
printf("%s\n", str_repeat('=', 52));

if ($gagal > 0) {
    printf("\nHASIL TIDAK COCOK DENGAN NASKAH.\n");
    exit(1);
}

printf("\nSeluruh uji lulus\n");
exit(0);
