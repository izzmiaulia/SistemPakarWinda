<?php
/**
 * download_excel.php
 * File ini diakses langsung (bukan melalui index.php) untuk menghasilkan
 * file Excel (.xlsx) berisi tahapan perhitungan manual Dempster-Shafer.
 */

// Tangkap semua output (whitespace/notice/warning) agar tidak merusak file Excel
ob_start();

session_start();

// Cek autentikasi
if (!isset($_SESSION['login'])) {
    header('Location: index.php?page=login');
    exit;
}

if (!isset($_GET['id'])) {
    die('Parameter ID konsultasi tidak ditemukan.');
}

$id_konsultasi = (int) $_GET['id'];

// Load dependencies
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/models/MesinInferensi.php';
require_once __DIR__ . '/models/Konsultasi.php';
require_once __DIR__ . '/models/Aturan.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Color;

// ─── Ambil Data Konsultasi ────────────────────────────────────────────────────
$konsultasiModel = new Konsultasi($db);
$detail = $konsultasiModel->getDetailKonsultasi($id_konsultasi);

if (!$detail) {
    die('Data konsultasi tidak ditemukan.');
}

// Hitung ulang dengan detail langkah menggunakan data dari DB
$aturanModel = new Aturan($db);
$gejala_data = $detail['gejala']; // ['kode_gejala', 'nama_gejala', 'nilai_belief']

$hasil   = MesinInferensi::hitungDenganDetail($gejala_data);
$persentase = $hasil['persentase'];
$langkah    = $hasil['langkah'];

// Tentukan level keparahan
if ($persentase >= 80)      { $level = 'Tinggi (Perlu Penanganan Segera)'; }
elseif ($persentase >= 50)  { $level = 'Sedang (Perlu Perhatian)'; }
else                        { $level = 'Rendah (Keyakinan Kurang)'; }

// ─── Warna Palette ───────────────────────────────────────────────────────────
$CLR_HEADER_BG   = '1E293B'; // slate-800
$CLR_HEADER_FG   = 'FFFFFF';
$CLR_BRAND_BG    = '16A34A'; // brand-600 green
$CLR_BRAND_LIGHT = 'F0FDF4'; // green-50
$CLR_STEP_BG     = '1E40AF'; // blue-800
$CLR_STEP_LIGHT  = 'EFF6FF'; // blue-50
$CLR_LAST_BG     = '16A34A'; // final step green
$CLR_LAST_LIGHT  = 'DCFCE7';
$CLR_ROW_ALT     = 'F8FAFC'; // slate-50
$CLR_BORDER      = 'E2E8F0'; // slate-200
$CLR_TABLE_HEAD  = '334155'; // slate-700
$CLR_FORMULA_BG  = 'F1F5F9'; // slate-100

// ─── Helper: Terapkan border tipis ke range ──────────────────────────────────
function applyThinBorder($sheet, $range, $color = 'CBD5E1') {
    $sheet->getStyle($range)->applyFromArray([
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => $color],
            ],
        ],
    ]);
}

// ─── Buat Spreadsheet ─────────────────────────────────────────────────────────
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Perhitungan DS');

// Set lebar kolom
$sheet->getColumnDimension('A')->setWidth(5);
$sheet->getColumnDimension('B')->setWidth(22);
$sheet->getColumnDimension('C')->setWidth(50);
$sheet->getColumnDimension('D')->setWidth(18);
$sheet->getColumnDimension('E')->setWidth(18);
$sheet->getColumnDimension('F')->setWidth(18);

$row = 1;

// ══════════════════════════════════════════════════════════════════════════════
// BAGIAN 1 — HEADER DOKUMEN
// ══════════════════════════════════════════════════════════════════════════════
$sheet->mergeCells("A{$row}:F{$row}");
$sheet->setCellValue("A{$row}", 'LAPORAN PERHITUNGAN DEMPSTER-SHAFER');
$sheet->getStyle("A{$row}")->applyFromArray([
    'font'      => ['bold' => true, 'size' => 14, 'color' => ['rgb' => $CLR_HEADER_FG]],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $CLR_HEADER_BG]],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
]);
$sheet->getRowDimension($row)->setRowHeight(30);
$row++;

$sheet->mergeCells("A{$row}:F{$row}");
$sheet->setCellValue("A{$row}", 'Sistem Pakar Identifikasi Masalah Perilaku Siswa – Metode Dempster-Shafer & Backward Chaining');
$sheet->getStyle("A{$row}")->applyFromArray([
    'font'      => ['italic' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '334155']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
]);
$sheet->getRowDimension($row)->setRowHeight(18);
$row += 2;

// ─── Info Sesi ───────────────────────────────────────────────────────────────
$infoData = [
    ['Tanggal Konsultasi', date('d F Y, H:i', strtotime($detail['tanggal'])) . ' WIB'],
    ['Nama Siswa',         $detail['nama_siswa']],
    ['NIS',                $detail['nis']],
    ['Kelas',              $detail['kelas']],
    ['Hipotesis Masalah',  "[{$detail['kode_masalah']}] {$detail['nama_masalah']}"],
    ['Nilai Keyakinan',    "{$persentase}%  →  {$level}"],
];

foreach ($infoData as $info) {
    $sheet->setCellValue("B{$row}", $info[0]);
    $sheet->setCellValue("C{$row}", $info[1]);
    $sheet->getStyle("B{$row}")->applyFromArray([
        'font' => ['bold' => true, 'size' => 10],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8FAFC']],
    ]);
    $sheet->getStyle("C{$row}")->applyFromArray([
        'font' => ['size' => 10],
    ]);
    applyThinBorder($sheet, "B{$row}:C{$row}");
    $row++;
}
$row++;

// ══════════════════════════════════════════════════════════════════════════════
// BAGIAN 2 — RUMUS FORMAL
// ══════════════════════════════════════════════════════════════════════════════
$sheet->mergeCells("A{$row}:F{$row}");
$sheet->setCellValue("A{$row}", 'RUMUS KOMBINASI DEMPSTER-SHAFER');
$sheet->getStyle("A{$row}")->applyFromArray([
    'font'      => ['bold' => true, 'size' => 11, 'color' => ['rgb' => $CLR_HEADER_FG]],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $CLR_STEP_BG]],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
]);
$sheet->getRowDimension($row)->setRowHeight(20);
$row++;

$rumusBaris = [
    'm_baru({H})  =  [ m₁({H}) × m₂({H}) ]  +  [ m₁({H}) × m₂({Θ}) ]  +  [ m₁({Θ}) × m₂({H}) ]',
    'm_baru({Θ})  =  m₁({Θ}) × m₂({Θ})',
    'Keterangan:  {H} = Himpunan hipotesis  |  {Θ} = Frame of Discernment  |  K = 0 (hipotesis tunggal, tidak ada konflik)',
];
foreach ($rumusBaris as $rb) {
    $sheet->mergeCells("A{$row}:F{$row}");
    $sheet->setCellValue("A{$row}", $rb);
    $sheet->getStyle("A{$row}")->applyFromArray([
        'font'      => ['name' => 'Courier New', 'size' => 10, 'color' => ['rgb' => '1E3A5F']],
        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $CLR_STEP_LIGHT]],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'indent' => 2],
    ]);
    $row++;
}
$row++;

// ══════════════════════════════════════════════════════════════════════════════
// BAGIAN 3 — TAHAP I: PENELUSURAN MUNDUR (BACKWARD CHAINING)
// ══════════════════════════════════════════════════════════════════════════════
$sheet->mergeCells("A{$row}:F{$row}");
$sheet->setCellValue("A{$row}", 'TAHAP I: PENELUSURAN MUNDUR (BACKWARD CHAINING)');
$sheet->getStyle("A{$row}")->applyFromArray([
    'font'      => ['bold' => true, 'size' => 11, 'color' => ['rgb' => $CLR_HEADER_FG]],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $CLR_HEADER_BG]],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
]);
$sheet->getRowDimension($row)->setRowHeight(20);
$row++;

// Info BC
$sheet->mergeCells("A{$row}:F{$row}");
$sheet->setCellValue("A{$row}", 'Sistem menetapkan Goal/Masalah lalu mencari premis-premis gejala yang sesuai di basis aturan:');
$sheet->getStyle("A{$row}")->applyFromArray([
    'font'      => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '475569']],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $CLR_ROW_ALT]],
]);
$row++;

// Tulis Log Penelusuran BC jika ada
if (!empty($detail['log_proses'])) {
    $sheet->mergeCells("A{$row}:F{$row}");
    $sheet->setCellValue("A{$row}", 'LOG PROSES PELACAKAN & BACKTRACKING:');
    $sheet->getStyle("A{$row}")->applyFromArray([
        'font'      => ['bold' => true, 'size' => 9, 'color' => ['rgb' => '1E293B']],
        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $CLR_FORMULA_BG]],
    ]);
    $row++;
    
    $logLines = explode("\n", $detail['log_proses']);
    foreach ($logLines as $line) {
        $sheet->mergeCells("A{$row}:F{$row}");
        // Hilangkan tag HTML log agar bersih di Excel
        $cleanLine = strip_tags($line);
        $sheet->setCellValue("A{$row}", '  • ' . $cleanLine);
        $sheet->getStyle("A{$row}")->applyFromArray([
            'font'      => ['name' => 'Courier New', 'size' => 8, 'color' => ['rgb' => '334155']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $CLR_FORMULA_BG]],
        ]);
        $row++;
    }
    $row++;
}

// Header tabel BC
$tblHeaders = ['No', 'Kode Gejala', 'Nama Gejala', 'Status Fakta', 'm({H})', 'm({Θ})'];
$cols = ['A', 'B', 'C', 'D', 'E', 'F'];
foreach ($tblHeaders as $i => $h) {
    $sheet->setCellValue("{$cols[$i]}{$row}", $h);
    $sheet->getStyle("{$cols[$i]}{$row}")->applyFromArray([
        'font'      => ['bold' => true, 'color' => ['rgb' => $CLR_HEADER_FG], 'size' => 10],
        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $CLR_TABLE_HEAD]],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    ]);
}
applyThinBorder($sheet, "A{$row}:F{$row}");
$row++;

$semua_gejala_masalah = $aturanModel->getAturanByMasalah($detail['id_masalah']);
$gejala_terpilih_kodes = array_column($gejala_data, 'kode_gejala');

foreach ($semua_gejala_masalah as $idx => $g) {
    $isTerpenuhi = in_array($g['kode_gejala'], $gejala_terpilih_kodes);
    $statusText = $isTerpenuhi ? 'Terpenuhi' : 'Tidak Terpenuhi';
    $mH = $isTerpenuhi ? (float)$g['nilai_belief'] : '—';
    $mT = $isTerpenuhi ? round(1 - $mH, 4) : '—';
    
    $bg = ($idx % 2 === 0) ? 'FFFFFF' : $CLR_ROW_ALT;
    $statusColor = $isTerpenuhi ? '15803D' : '94A3B8'; // Hijau vs Abu-abu
    
    $rowData = [$idx + 1, $g['kode_gejala'], $g['nama_gejala'], $statusText, $mH, $mT];
    foreach ($cols as $ci => $col) {
        $sheet->setCellValue("{$col}{$row}", $rowData[$ci]);
        $sheet->getStyle("{$col}{$row}")->applyFromArray([
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bg]],
            'alignment' => ['horizontal' => ($ci >= 3) ? Alignment::HORIZONTAL_CENTER : Alignment::HORIZONTAL_LEFT],
            'font'      => [
                'size' => 10,
                'bold' => ($ci === 3 && $isTerpenuhi) || ($ci === 4 && $isTerpenuhi),
                'color' => ($ci === 3) ? ['rgb' => $statusColor] : ['rgb' => '000000']
            ]
        ]);
    }
    applyThinBorder($sheet, "A{$row}:F{$row}");
    $row++;
}
$row++;

// ══════════════════════════════════════════════════════════════════════════════
// BAGIAN 4 — TAHAP II: KOMBINASI PROBABILITAS (DEMPSTER-SHAFER)
// ══════════════════════════════════════════════════════════════════════════════
$sheet->mergeCells("A{$row}:F{$row}");
$sheet->setCellValue("A{$row}", 'TAHAP II: KOMBINASI PROBABILITAS (DEMPSTER-SHAFER)');
$sheet->getStyle("A{$row}")->applyFromArray([
    'font'      => ['bold' => true, 'size' => 11, 'color' => ['rgb' => $CLR_HEADER_FG]],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $CLR_HEADER_BG]],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
]);
$sheet->getRowDimension($row)->setRowHeight(20);
$row++;

$totalLangkah = count($langkah);
foreach ($langkah as $lIdx => $lk) {
    $isLast = ($lIdx === $totalLangkah - 1);
    $headerBg = $isLast ? $CLR_BRAND_BG : $CLR_STEP_BG;
    $bodyBg   = $isLast ? $CLR_LAST_LIGHT : $CLR_STEP_LIGHT;

    // --- Header Langkah ---
    $sheet->mergeCells("A{$row}:F{$row}");
    $sheet->setCellValue("A{$row}", $lk['judul']);
    $sheet->getStyle("A{$row}")->applyFromArray([
        'font'      => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $headerBg]],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'indent' => 1],
    ]);
    $sheet->getRowDimension($row)->setRowHeight(18);
    $row++;

    // --- Uraian ---
    $sheet->mergeCells("A{$row}:F{$row}");
    $sheet->setCellValue("A{$row}", $lk['uraian']);
    $sheet->getStyle("A{$row}")->applyFromArray([
        'font'      => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '475569']],
        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bodyBg]],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'indent' => 2],
    ]);
    $row++;

    // --- Baris Rumus ---
    foreach ($lk['rumus'] as $baris) {
        if ($baris === '') { $row++; continue; }
        if (str_starts_with($baris, '───')) {
            $sheet->mergeCells("B{$row}:F{$row}");
            $sheet->setCellValue("B{$row}", str_repeat('─', 60));
            $sheet->getStyle("B{$row}")->applyFromArray([
                'font' => ['color' => ['rgb' => '94A3B8'], 'size' => 9],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bodyBg]],
            ]);
        } else {
            $sheet->mergeCells("B{$row}:F{$row}");
            $sheet->setCellValue("B{$row}", $baris);
            $isBold = str_contains($baris, 'Rumus:');
            $clr = str_contains($baris, 'Konflik') || str_contains($baris, 'Normalisasi') ? '94A3B8' : '1E3A5F';
            $sheet->getStyle("B{$row}")->applyFromArray([
                'font'      => ['name' => 'Courier New', 'size' => 9, 'bold' => $isBold, 'color' => ['rgb' => $clr]],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bodyBg]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'indent' => 2],
            ]);
        }
        $row++;
    }

    // --- Hasil Langkah ---
    $sheet->setCellValue("B{$row}", 'Hasil m({H})');
    $sheet->setCellValue("C{$row}", $lk['m_H']);
    $sheet->setCellValue("D{$row}", 'Hasil m({Θ})');
    $sheet->setCellValue("E{$row}", $lk['m_Theta']);
    if ($isLast) {
        $sheet->setCellValueExplicit("F{$row}", "= {$persentase}% ({$level})", \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
    }
    $sheet->getStyle("B{$row}:F{$row}")->applyFromArray([
        'font'      => ['bold' => true, 'size' => 10, 'color' => ['rgb' => $isLast ? 'FFFFFF' : '1E3A5F']],
        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $isLast ? $CLR_BRAND_BG : $CLR_STEP_BG]],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    ]);
    applyThinBorder($sheet, "B{$row}:F{$row}", 'FFFFFF');
    $row += 2;
}

// ══════════════════════════════════════════════════════════════════════════════
// BAGIAN 5 — HASIL AKHIR & SOLUSI
// ══════════════════════════════════════════════════════════════════════════════
$sheet->mergeCells("A{$row}:F{$row}");
$sheet->setCellValue("A{$row}", 'HASIL AKHIR & INTERPRETASI');
$sheet->getStyle("A{$row}")->applyFromArray([
    'font'      => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $CLR_BRAND_BG]],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
]);
$sheet->getRowDimension($row)->setRowHeight(22);
$row++;

$hasilAkhir = [
    ['Diagnosis Masalah',   "[{$detail['kode_masalah']}] {$detail['nama_masalah']}"],
    ['Nilai m_final({H})',  round($persentase / 100, 6)],
    ['Persentase Keyakinan', "{$persentase}%"],
    ['Tingkat Keparahan',    $level],
];
foreach ($hasilAkhir as $ha) {
    $sheet->setCellValue("B{$row}", $ha[0]);
    $sheet->setCellValue("C{$row}", $ha[1]);
    $sheet->getStyle("B{$row}")->applyFromArray([
        'font' => ['bold' => true, 'size' => 10],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $CLR_LAST_LIGHT]],
    ]);
    $sheet->getStyle("C{$row}")->applyFromArray([
        'font' => ['size' => 10, 'bold' => true, 'color' => ['rgb' => '15803D']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $CLR_LAST_LIGHT]],
    ]);
    applyThinBorder($sheet, "B{$row}:C{$row}");
    $row++;
}
$row++;

// Solusi
$sheet->mergeCells("A{$row}:F{$row}");
$sheet->setCellValue("A{$row}", 'SOLUSI / PENANGANAN YANG DISARANKAN');
$sheet->getStyle("A{$row}")->applyFromArray([
    'font'      => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'B45309']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
]);
$row++;

$sheet->mergeCells("A{$row}:F{$row}");
$sheet->setCellValue("A{$row}", $detail['solusi']);
$sheet->getStyle("A{$row}")->applyFromArray([
    'font'      => ['size' => 10],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFBEB']],
    'alignment' => ['wrapText' => true, 'vertical' => Alignment::VERTICAL_TOP],
]);
$sheet->getRowDimension($row)->setRowHeight(60);
$row += 2;

// Footer
$sheet->mergeCells("A{$row}:F{$row}");
$sheet->setCellValue("A{$row}", 'Dokumen ini digenerate otomatis oleh Sistem Pakar BK pada ' . date('d F Y H:i') . ' WIB');
$sheet->getStyle("A{$row}")->applyFromArray([
    'font'      => ['italic' => true, 'size' => 8, 'color' => ['rgb' => '94A3B8']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
]);

// ─── Kirim File ke Browser ────────────────────────────────────────────────────
$namaFile = 'Perhitungan_DS_' . $detail['nis'] . '_' . date('Ymd_His') . '.xlsx';

// Simpan ke file temporary terlebih dahulu
// Cara ini paling andal karena tidak ada risiko byte output bocor ke stream
$tmpFile = tempnam(sys_get_temp_dir(), 'excel_ds_');
$writer = new Xlsx($spreadsheet);
$writer->save($tmpFile);

// Buang semua buffer yang mungkin terkumpul
while (ob_get_level()) {
    ob_end_clean();
}

// Stream file ke browser
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $namaFile . '"');
header('Content-Length: ' . filesize($tmpFile));
header('Cache-Control: max-age=0, must-revalidate');
header('Pragma: public');
header('Expires: 0');

readfile($tmpFile);

// Hapus file temporary setelah dikirim
@unlink($tmpFile);
exit;
