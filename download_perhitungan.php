<?php
/**
 * download_perhitungan.php
 *
 * Menghasilkan berkas Excel (.xlsx) berisi seluruh tahapan perhitungan
 * Certainty Factor untuk satu sesi konsultasi: penelusuran Backward Chaining,
 * CF evidence tiap indikator, kombinasi bertahap per hipotesis, dan peringkat
 * akhir keempat tipe.
 *
 * Diakses langsung, tidak melalui index.php.
 */

ob_start();
session_start();

if (!isset($_SESSION['login'])) {
    header('Location: index.php?page=login');
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/koneksi.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

$id_konsultasi = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id_konsultasi <= 0) {
    ob_end_clean();
    exit('Parameter id konsultasi tidak ditemukan.');
}

$konsultasiModel = new Konsultasi($db);
$detail          = $konsultasiModel->getDetailKonsultasi($id_konsultasi);

if (!$detail) {
    ob_end_clean();
    exit('Data konsultasi tidak ditemukan.');
}

// ─── Kelompokkan jawaban per hipotesis ──────────────────────────────────────
$perHipotesis = [];
foreach ($detail['jawaban'] as $j) {
    if (empty($j['id_kepribadian'])) {
        continue;
    }
    $perHipotesis[$j['id_kepribadian']][] = [
        'kode_gejala' => $j['kode_gejala'],
        'nama_gejala' => $j['nama_gejala'],
        'nilai_cf'    => (float) $j['cf_pakar'],
        'cf_user'     => (float) $j['cf_user'],
    ];
}

$labelSkala = array_flip(array_map('strval', MesinInferensiCF::SKALA));

// ─── Warna ──────────────────────────────────────────────────────────────────
const WARNA_JUDUL  = 'FF16A34A';
const WARNA_KEPALA = 'FF1E293B';
const WARNA_SUB    = 'FFF1F5F9';
const WARNA_SOROT  = 'FFDCFCE7';

$spreadsheet = new Spreadsheet();
$sheet       = $spreadsheet->getActiveSheet();
$sheet->setTitle('Perhitungan CF');

$baris = 1;

/** Tulis judul bagian. */
$judulBagian = function (string $teks) use ($sheet, &$baris) {
    $sheet->setCellValue("A{$baris}", $teks);
    $sheet->mergeCells("A{$baris}:E{$baris}");
    $sheet->getStyle("A{$baris}")->getFont()->setBold(true)->setSize(11)->getColor()->setARGB('FFFFFFFF');
    $sheet->getStyle("A{$baris}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(WARNA_KEPALA);
    $sheet->getStyle("A{$baris}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
    $sheet->getRowDimension($baris)->setRowHeight(20);
    $baris += 2;
};

/** Tulis satu baris pasangan label-nilai. */
$barisData = function (string $label, $nilai) use ($sheet, &$baris) {
    $sheet->setCellValue("A{$baris}", $label);
    $sheet->setCellValue("B{$baris}", $nilai);
    $sheet->mergeCells("B{$baris}:E{$baris}");
    $sheet->getStyle("A{$baris}")->getFont()->setBold(true);
    $baris++;
};

// ─── Kepala dokumen ─────────────────────────────────────────────────────────
$sheet->setCellValue('A1', 'LAPORAN PERHITUNGAN CERTAINTY FACTOR');
$sheet->mergeCells('A1:E1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setARGB('FFFFFFFF');
$sheet->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(WARNA_JUDUL);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
$sheet->getRowDimension(1)->setRowHeight(26);

$sheet->setCellValue('A2', 'Sistem Pakar Bimbingan dan Konseling - Identifikasi Tipe Kepribadian DISC');
$sheet->mergeCells('A2:E2');
$sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(9);

$baris = 4;

// ─── Identitas ──────────────────────────────────────────────────────────────
$judulBagian('IDENTITAS KONSULTASI');
$barisData('Nama Siswa',   $detail['nama_siswa']);
$barisData('NIS',          $detail['nis']);
$barisData('Kelas',        $detail['kelas']);
$barisData('Tanggal',      date('d F Y, H:i', strtotime($detail['tanggal'])) . ' WIB');
$barisData('Hasil Akhir',  $detail['tipe'] . ' - ' . $detail['nama_kepribadian'] . '  (' . $detail['nilai_persentase'] . '%)');
$baris++;

// ─── Rumus ──────────────────────────────────────────────────────────────────
$judulBagian('RUMUS YANG DIGUNAKAN');
$sheet->setCellValue("A{$baris}", 'CF Evidence  =  CF Pakar  x  CF Pengguna');
$sheet->mergeCells("A{$baris}:E{$baris}");
$baris++;
$sheet->setCellValue("A{$baris}", 'CF Kombinasi =  CF1 + [ CF2 x ( 1 - CF1 ) ]');
$sheet->mergeCells("A{$baris}:E{$baris}");
$baris++;
$sheet->setCellValue("A{$baris}", 'Keterangan: seluruh nilai CF bernilai positif, sehingga hanya aturan kombinasi CF positif yang dipakai. MD = 0 untuk semua indikator.');
$sheet->mergeCells("A{$baris}:E{$baris}");
$sheet->getStyle("A{$baris}")->getFont()->setItalic(true)->setSize(9);
$baris += 2;

// ─── Tahap I: Backward Chaining ─────────────────────────────────────────────
$judulBagian('TAHAP I: PENELUSURAN MUNDUR (BACKWARD CHAINING)');
if (!empty($detail['log_proses'])) {
    foreach (explode("\n", $detail['log_proses']) as $barisLog) {
        if (trim($barisLog) === '') {
            $baris++;
            continue;
        }
        $sheet->setCellValue("A{$baris}", $barisLog);
        $sheet->mergeCells("A{$baris}:E{$baris}");
        if (!str_starts_with($barisLog, ' ')) {
            $sheet->getStyle("A{$baris}")->getFont()->setBold(true);
        }
        $baris++;
    }
}
$baris++;

// ─── Tahap II: perhitungan tiap hipotesis ───────────────────────────────────
$judulBagian('TAHAP II: PERHITUNGAN CERTAINTY FACTOR TIAP HIPOTESIS');

foreach ($detail['peringkat'] as $p) {
    $gejalaData = $perHipotesis[$p['id_kepribadian']] ?? [];
    $hitung     = MesinInferensiCF::hitungDenganDetail($gejalaData);

    // Sub-judul hipotesis
    $sheet->setCellValue("A{$baris}", sprintf(
        'Peringkat %d  |  %s (%s - %s)  melalui aturan %s',
        $p['peringkat'], $p['kode'], $p['tipe'], $p['nama'], $p['kode_rule']
    ));
    $sheet->mergeCells("A{$baris}:E{$baris}");
    $sheet->getStyle("A{$baris}")->getFont()->setBold(true);
    $sheet->getStyle("A{$baris}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(WARNA_SUB);
    $baris++;

    // Tabel CF evidence
    $kepala = ['Kode', 'Indikator', 'CF Pakar', 'Jawaban', 'CF Evidence'];
    foreach ($kepala as $i => $teks) {
        $kolom = chr(ord('A') + $i);
        $sheet->setCellValue("{$kolom}{$baris}", $teks);
        $sheet->getStyle("{$kolom}{$baris}")->getFont()->setBold(true)->setSize(9);
        $sheet->getStyle("{$kolom}{$baris}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE2E8F0');
    }
    $barisAwalTabel = $baris;
    $baris++;

    foreach ($gejalaData as $g) {
        $cfEv  = MesinInferensiCF::cfEvidence($g['nilai_cf'], $g['cf_user']);
        $nama  = $labelSkala[(string) $g['cf_user']] ?? $g['cf_user'];

        $sheet->setCellValue("A{$baris}", $g['kode_gejala']);
        $sheet->setCellValue("B{$baris}", $g['nama_gejala']);
        $sheet->setCellValue("C{$baris}", $g['nilai_cf']);
        $sheet->setCellValue("D{$baris}", $nama . ' (' . number_format($g['cf_user'], 1) . ')');
        $sheet->setCellValue("E{$baris}", round($cfEv, 4));
        $baris++;
    }

    $sheet->getStyle("A{$barisAwalTabel}:E" . ($baris - 1))
          ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    $baris++;

    // Langkah kombinasi
    foreach ($hitung['langkah'] as $lk) {
        $sheet->setCellValue("A{$baris}", $lk['judul']);
        $sheet->mergeCells("A{$baris}:E{$baris}");
        $sheet->getStyle("A{$baris}")->getFont()->setBold(true)->setSize(10);
        $baris++;

        foreach ($lk['rumus'] as $barisRumus) {
            if ($barisRumus === '') {
                $baris++;
                continue;
            }
            $sheet->setCellValue("A{$baris}", '    ' . $barisRumus);
            $sheet->mergeCells("A{$baris}:E{$baris}");
            $baris++;
        }
        $baris++;
    }

    // Hasil hipotesis
    $sheet->setCellValue("A{$baris}", 'CF Akhir ' . $p['kode']);
    $sheet->setCellValue("B{$baris}", $hitung['cf_akhir'] . '   =   ' . $hitung['persentase'] . '%');
    $sheet->mergeCells("B{$baris}:E{$baris}");
    $sheet->getStyle("A{$baris}:E{$baris}")->getFont()->setBold(true);
    $sheet->getStyle("A{$baris}:E{$baris}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(WARNA_SOROT);
    $sheet->getStyle("A{$baris}:E{$baris}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    $baris += 3;
}

// ─── Tahap III: peringkat akhir ─────────────────────────────────────────────
$judulBagian('TAHAP III: PERBANDINGAN DAN PERINGKAT AKHIR');

$kepala = ['Peringkat', 'Kode', 'Tipe Kepribadian', 'CF Akhir', 'Persentase'];
foreach ($kepala as $i => $teks) {
    $kolom = chr(ord('A') + $i);
    $sheet->setCellValue("{$kolom}{$baris}", $teks);
    $sheet->getStyle("{$kolom}{$baris}")->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
    $sheet->getStyle("{$kolom}{$baris}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(WARNA_KEPALA);
}
$barisAwalTabel = $baris;
$baris++;

foreach ($detail['peringkat'] as $p) {
    $sheet->setCellValue("A{$baris}", $p['peringkat']);
    $sheet->setCellValue("B{$baris}", $p['kode']);
    $sheet->setCellValue("C{$baris}", $p['tipe'] . ' - ' . $p['nama']);
    $sheet->setCellValue("D{$baris}", (float) $p['cf_akhir']);
    $sheet->setCellValue("E{$baris}", $p['persentase'] . '%');

    if ((int) $p['peringkat'] === 1) {
        $sheet->getStyle("A{$baris}:E{$baris}")->getFont()->setBold(true);
        $sheet->getStyle("A{$baris}:E{$baris}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(WARNA_SOROT);
    }
    $baris++;
}
$sheet->getStyle("A{$barisAwalTabel}:E" . ($baris - 1))
      ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
$baris += 2;

$sheet->setCellValue("A{$baris}", 'Kesimpulan: kecenderungan tipe kepribadian tertinggi adalah '
    . $detail['tipe'] . ' - ' . $detail['nama_kepribadian'] . ' dengan tingkat keyakinan '
    . $detail['nilai_persentase'] . '%.');
$sheet->mergeCells("A{$baris}:E{$baris}");
$sheet->getStyle("A{$baris}")->getFont()->setBold(true);
$baris += 2;

if (!empty($detail['rekomendasi']) && !str_starts_with(trim($detail['rekomendasi']), '[BELUM DIISI]')) {
    $judulBagian('REKOMENDASI LAYANAN BIMBINGAN DAN KONSELING');
    $sheet->setCellValue("A{$baris}", $detail['rekomendasi']);
    $sheet->mergeCells("A{$baris}:E{$baris}");
    $sheet->getStyle("A{$baris}")->getAlignment()->setWrapText(true);
    $sheet->getRowDimension($baris)->setRowHeight(60);
}

// ─── Lebar kolom ────────────────────────────────────────────────────────────
$sheet->getColumnDimension('A')->setWidth(18);
$sheet->getColumnDimension('B')->setWidth(58);
$sheet->getColumnDimension('C')->setWidth(14);
$sheet->getColumnDimension('D')->setWidth(20);
$sheet->getColumnDimension('E')->setWidth(16);

// ─── Kirim berkas ───────────────────────────────────────────────────────────
$namaBerkas = sprintf(
    'Perhitungan_CF_%s_%s.xlsx',
    preg_replace('/[^A-Za-z0-9]/', '', $detail['nis']),
    date('Ymd_His', strtotime($detail['tanggal']))
);

ob_end_clean();

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $namaBerkas . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
