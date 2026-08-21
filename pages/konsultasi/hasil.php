<?php
if (!isset($_POST['id_siswa']) || !isset($_POST['id_masalah_hipotesis']) || !isset($_POST['gejala'])) {
    header("Location: index.php?page=konsultasi");
    exit;
}

$id_siswa         = $_POST['id_siswa'];
$id_masalah       = $_POST['id_masalah_hipotesis'];
$gejala_terpilih  = $_POST['gejala']; // Array of id_gejala

$siswaModel       = new Siswa($db);
$masalahModel     = new Masalah($db);
$aturanModel      = new Aturan($db);
$konsultasiModel  = new Konsultasi($db);

$siswa   = $siswaModel->getSiswaById($id_siswa);
$masalah = $masalahModel->getMasalahById($id_masalah);

// 1. Ambil seluruh gejala yang terkait dengan masalah (Goal) ini untuk visualisasi Backward Chaining
$semua_gejala_masalah = $aturanModel->getAturanByMasalah($id_masalah);

// 2. Kumpulkan data gejala terpilih lengkap dengan belief pakar (Fakta Terpenuhi)
$gejala_data   = [];
$gejala_detail = [];

foreach ($gejala_terpilih as $id_g) {
    $row = $aturanModel->getAturanByMasalahAndGejala($id_masalah, $id_g);
    if ($row) {
        $gejala_data[]   = $row;   // untuk perhitungan & tampilan
        $gejala_detail[] = $row;   // untuk panel kiri
    }
}

// PERHITUNGAN DEMPSTER-SHAFER dengan detail langkah
$hasil   = MesinInferensi::hitungDenganDetail($gejala_data);
$persentase = $hasil['persentase'];
$langkah    = $hasil['langkah'];

// Simpan ke riwayat dengan log Backward Chaining
$log_proses = isset($_SESSION['konsultasi']['logs']) ? implode("\n", $_SESSION['konsultasi']['logs']) : null;
$id_konsultasi = $konsultasiModel->simpanKonsultasi($id_siswa, $id_masalah, $gejala_terpilih, $persentase, $log_proses);

// Bersihkan sesi konsultasi setelah berhasil disimpan
unset($_SESSION['konsultasi']);

// Tentukan level keparahan
if ($persentase >= 80) {
    $level = 'Tinggi'; $levelText = 'text-red-700'; $levelBg = 'bg-red-50 border-red-200';
    $barColor = 'bg-red-500'; $badgeCls = 'bg-red-100 text-red-700 border-red-200'; $icon = 'alert-octagon';
} elseif ($persentase >= 50) {
    $level = 'Sedang'; $levelText = 'text-orange-700'; $levelBg = 'bg-orange-50 border-orange-200';
    $barColor = 'bg-orange-500'; $badgeCls = 'bg-orange-100 text-orange-700 border-orange-200'; $icon = 'alert-triangle';
} else {
    $level = 'Rendah'; $levelText = 'text-emerald-700'; $levelBg = 'bg-emerald-50 border-emerald-200';
    $barColor = 'bg-emerald-500'; $badgeCls = 'bg-emerald-100 text-emerald-700 border-emerald-200'; $icon = 'check-circle-2';
}
?>

<div class="mb-6 print:mb-4">
    <h2 class="text-2xl font-bold text-slate-800">Hasil Analisis Diagnostik</h2>
    <p class="text-slate-500 mt-1 text-sm">Transparansi alur penelusuran metode Backward Chaining dan kalkulasi probabilitas Dempster-Shafer.</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <!-- ====== Kolom Kiri: Info Siswa ====== -->
    <div class="col-span-1 space-y-5">
        <div class="card p-5 border-l-4 border-l-blue-500">
            <h3 class="font-bold text-slate-800 border-b border-slate-100 pb-2 mb-3 text-xs uppercase tracking-wider">Data Siswa</h3>
            <p class="font-semibold text-slate-700 text-base"><?= htmlspecialchars($siswa['nama_siswa']) ?></p>
            <p class="text-sm text-slate-500 mt-0.5">NIS: <?= $siswa['nis'] ?> | Kelas: <?= $siswa['kelas'] ?></p>
        </div>

        <div class="card p-5">
            <h3 class="font-bold text-slate-800 border-b border-slate-100 pb-2 mb-3 text-xs uppercase tracking-wider">Diagnosis Masalah (Goal)</h3>
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-sm font-bold bg-orange-100 text-orange-700 border border-orange-200">
                <?= $masalah['kode_masalah'] ?> – <?= htmlspecialchars($masalah['nama_masalah']) ?>
            </span>
        </div>

        <!-- Hasil Akhir ringkas -->
        <div class="card p-5 text-center border-2 <?= $levelBg ?>">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Nilai Keyakinan Akhir</p>
            <p class="text-5xl font-extrabold <?= $levelText ?> mb-2"><?= $persentase ?>%</p>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border <?= $badgeCls ?>">
                <i data-lucide="<?= $icon ?>" class="w-3.5 h-3.5"></i> Tingkat <?= $level ?>
            </span>
            <!-- Progress Bar -->
            <div class="mt-4 w-full bg-white rounded-full h-3 border border-slate-200 overflow-hidden">
                <div id="progressBar" class="h-3 rounded-full <?= $barColor ?> transition-all duration-1000 ease-out" style="width:0%"></div>
            </div>
            <div class="flex justify-between text-[10px] text-slate-400 mt-1 px-0.5">
                <span>0%</span><span>50%</span><span>100%</span>
            </div>
        </div>
    </div>

    <!-- ====== Kolom Kanan: Runtunan Metode BC dan DS ====== -->
    <div class="col-span-1 md:col-span-2 space-y-5">
        
        <!-- TAHAP 1: BACKWARD CHAINING SECTION -->
        <div class="card overflow-hidden border border-brand-200">
            <div class="bg-brand-600 text-white px-5 py-4">
                <h3 class="font-bold text-base flex items-center gap-2">
                    <i data-lucide="git-branch" class="w-5 h-5"></i>
                    Tahap I: Penelusuran Mundur (Backward Chaining)
                </h3>
                <p class="text-brand-100 text-xs mt-1">
                    Metode pelacakan dimulai dari menetapkan <strong>Goal (Masalah)</strong>, lalu mundur mencari <strong>Premis (Daftar Gejala)</strong> untuk dicocokkan dengan fakta di lapangan.
                </p>
            </div>
            
            <div class="p-5 space-y-4">
                <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 text-xs space-y-2 leading-relaxed">
                    <p class="font-semibold text-slate-700 flex items-center gap-1.5">
                        <i data-lucide="info" class="w-4 h-4 text-brand-600"></i> Alur Kerja Sistem:
                    </p>
                    <ol class="list-decimal list-inside space-y-1 text-slate-600">
                        <li>Sistem menduga awal siswa mengalami <strong><?= htmlspecialchars($masalah['nama_masalah']) ?></strong> (Goal).</li>
                        <li>Sistem menelusuri mundur ke basis aturan mencari semua gejala pendukung masalah tersebut.</li>
                        <li>Guru mencentang gejala yang benar-benar tampak (Fakta Terpenuhi).</li>
                        <li>Hanya gejala yang terpenuhi yang diteruskan untuk dihitung keyakinannya oleh Dempster-Shafer.</li>
                    </ol>
                </div>

                <!-- Log Pelacakan Backward Chaining -->
                <?php if (isset($_SESSION['konsultasi']['logs'])): ?>
                <div class="bg-slate-900 rounded-xl p-4 text-xs font-mono text-slate-200 space-y-2 border border-slate-800 leading-relaxed max-h-[220px] overflow-y-auto">
                    <p class="text-brand-400 font-bold mb-2">// Log Proses Pelacakan & Backtracking</p>
                    <?php foreach ($_SESSION['konsultasi']['logs'] as $log): ?>
                        <p class="border-l-2 border-brand-500 pl-2 py-0.5"><?= $log ?></p>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead>
                            <tr class="border-b border-slate-100 bg-slate-50/50">
                                <th class="px-4 py-2 text-xs font-bold text-slate-500 uppercase w-20">Kode</th>
                                <th class="px-4 py-2 text-xs font-bold text-slate-500 uppercase">Gejala yang Dicari Sistem</th>
                                <th class="px-4 py-2 text-xs font-bold text-slate-500 uppercase text-center w-36">Status Fakta</th>
                                <th class="px-4 py-2 text-xs font-bold text-slate-500 uppercase text-center w-20">Belief</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($semua_gejala_masalah as $g): 
                                $isTerpenuhi = in_array($g['id_gejala'], $gejala_terpilih);
                                $statusCls = $isTerpenuhi ? 'bg-emerald-100 text-emerald-800 border-emerald-200' : 'bg-slate-100 text-slate-400 border-slate-200';
                                $statusText = $isTerpenuhi ? '✓ Terpenuhi' : '✗ Tidak';
                            ?>
                            <tr class="hover:bg-slate-50/40 <?= !$isTerpenuhi ? 'opacity-60' : '' ?>">
                                <td class="px-4 py-2.5">
                                    <span class="bg-blue-50 text-blue-700 text-xs font-bold px-2 py-0.5 rounded border border-blue-100"><?= $g['kode_gejala'] ?></span>
                                </td>
                                <td class="px-4 py-2.5 text-slate-700 font-medium text-xs"><?= htmlspecialchars($g['nama_gejala']) ?></td>
                                <td class="px-4 py-2.5 text-center">
                                    <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold border <?= $statusCls ?>">
                                        <?= $statusText ?>
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 text-center font-bold text-slate-600 text-xs">
                                    <?= $isTerpenuhi ? $g['nilai_belief'] : '—' ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAHAP 2: DEMPSTER SHAFER SECTION -->
        <div class="card overflow-hidden border border-slate-200">
            <div class="bg-slate-800 text-white px-5 py-4">
                <h3 class="font-bold text-base flex items-center gap-2">
                    <i data-lucide="calculator" class="w-5 h-5 text-brand-400"></i>
                    Tahap II: Kombinasi Probabilitas (Dempster-Shafer)
                </h3>
                <p class="text-slate-400 text-xs mt-1">
                    Hanya gejala dengan status <strong>Terpenuhi (Valid)</strong> yang akan digabungkan nilainya menggunakan aturan kombinasi Densitas Kepercayaan.
                </p>
            </div>

            <div class="p-5 space-y-4">
                <div class="bg-slate-50 rounded-xl p-4 font-mono text-[11px] text-slate-700 border border-slate-200 leading-relaxed overflow-x-auto">
                    <p class="text-brand-700 font-bold mb-1">// Rumus Matematika Dempster-Shafer</p>
                    <p>m_baru({H}) = [ m₁({H}) × m₂({H}) ] + [ m₁({H}) × m₂({Θ}) ] + [ m₁({Θ}) × m₂({H}) ]</p>
                    <p>m_baru({Θ}) = m₁({Θ}) × m₂({Θ})</p>
                </div>

                <div class="space-y-4">
                    <?php foreach ($langkah as $idx => $lk): ?>
                    <div class="border border-slate-200 bg-white rounded-xl p-4 text-xs">
                        <h5 class="font-bold text-slate-800 text-sm"><?= $lk['judul'] ?></h5>
                        <p class="text-slate-500 text-xs mt-0.5"><?= $lk['uraian'] ?></p>

                        <!-- Kotak kode rumus -->
                        <div class="bg-slate-900 rounded-xl p-4 font-mono text-[11px] leading-relaxed overflow-x-auto my-3">
                            <?php foreach ($lk['rumus'] as $baris): ?>
                                <?php if ($baris === ''): ?>
                                    <br>
                                <?php elseif (str_starts_with($baris, '───')): ?>
                                    <p class="text-slate-500"><?= $baris ?></p>
                                <?php elseif (str_contains($baris, 'Rumus:')): ?>
                                    <p class="text-yellow-400"><?= htmlspecialchars($baris) ?></p>
                                <?php elseif (str_contains($baris, 'Konflik') || str_contains($baris, 'Normalisasi')): ?>
                                    <p class="text-slate-400 italic"><?= htmlspecialchars($baris) ?></p>
                                <?php else: ?>
                                    <p class="text-emerald-300"><?= htmlspecialchars($baris) ?></p>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>

                        <!-- Hasil Langkah -->
                        <div class="flex gap-4">
                            <div class="flex-1 bg-brand-50 border border-brand-100 rounded-xl p-3 text-center">
                                <p class="text-[10px] text-slate-500 mb-0.5">m({H}) — Keyakinan</p>
                                <p class="text-xl font-extrabold text-brand-700"><?= $lk['m_H'] ?></p>
                            </div>
                            <div class="flex-1 bg-slate-50 border border-slate-200 rounded-xl p-3 text-center">
                                <p class="text-[10px] text-slate-500 mb-0.5">m({Θ}) — Ketidakpastian</p>
                                <p class="text-xl font-extrabold text-slate-500"><?= $lk['m_Theta'] ?></p>
                            </div>
                            <?php if ($idx === count($langkah) - 1): ?>
                            <div class="flex-1 bg-emerald-50 border border-emerald-200 rounded-xl p-3 text-center animate-pulse">
                                <p class="text-[10px] text-slate-500 mb-0.5">Hasil Akhir</p>
                                <p class="text-xl font-extrabold <?= $levelText ?>"><?= $persentase ?>%</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Interpretasi -->
        <?php if ($persentase < 50): ?>
        <div class="bg-orange-50 border border-orange-200 rounded-xl p-5">
            <div class="flex items-start">
                <i data-lucide="alert-triangle" class="w-6 h-6 text-orange-500 mr-3 mt-0.5 shrink-0"></i>
                <div>
                    <h4 class="font-bold text-orange-800 text-sm">Keyakinan Rendah</h4>
                    <p class="text-sm text-orange-700 mt-1">Gejala yang teramati kurang mendukung hipotesis ini. Disarankan mengevaluasi ulang dengan hipotesis dugaan masalah yang berbeda.</p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Solusi -->
        <div class="card p-5">
            <h4 class="font-bold text-slate-800 flex items-center mb-3">
                <i data-lucide="lightbulb" class="w-5 h-5 text-orange-500 mr-2"></i>
                Solusi / Penanganan yang Disarankan
            </h4>
            <p class="text-slate-600 text-sm leading-relaxed">
                <?= nl2br(htmlspecialchars($masalah['solusi'])) ?>
            </p>
        </div>

        <!-- Tombol Aksi -->
        <div class="flex flex-wrap gap-3 print:hidden">
            <a href="index.php?page=konsultasi" class="px-5 py-2.5 bg-white border border-slate-300 text-slate-700 font-medium rounded-xl hover:bg-slate-50 transition-colors flex items-center gap-2">
                <i data-lucide="refresh-cw" class="w-4 h-4"></i> Analisis Ulang
            </a>
            <a href="index.php?page=riwayat" class="px-5 py-2.5 bg-brand-600 text-white font-medium rounded-xl hover:bg-brand-700 transition-colors shadow-sm flex items-center gap-2">
                <i data-lucide="history" class="w-4 h-4"></i> Lihat Riwayat
            </a>
            <a href="download_excel.php?id=<?= $id_konsultasi ?>" target="_blank" class="px-5 py-2.5 bg-emerald-600 text-white font-medium rounded-xl hover:bg-emerald-700 transition-colors shadow-sm flex items-center gap-2">
                <i data-lucide="file-spreadsheet" class="w-4 h-4"></i> Unduh Excel
            </a>
            <button onclick="window.print()" class="px-5 py-2.5 bg-slate-800 text-white font-medium rounded-xl hover:bg-slate-900 transition-colors shadow-sm flex items-center gap-2">
                <i data-lucide="printer" class="w-4 h-4"></i> Cetak
            </button>
        </div>
    </div>
</div>

<!-- CSS Khusus Cetak PDF -->
<style>
    @media print {
        /* ── Sembunyikan sidebar, topbar, dan tombol navigasi ── */
        aside#sidebar,
        header,
        .print\:hidden {
            display: none !important;
            visibility: hidden !important;
        }

        /* ── Reset layout body & wrapper ── */
        body {
            background: white !important;
            overflow: visible !important;
            height: auto !important;
            display: block !important;
        }

        /* ── <main> jadi full width karena sidebar sudah hilang ── */
        main {
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
            height: auto !important;
            overflow: visible !important;
            display: block !important;
            flex: unset !important;
        }

        /* ── Area konten halaman ── */
        main > div.p-8 {
            padding: 10mm !important;
        }

        /* ── Grid menjadi block (kolom ditumpuk vertikal) ── */
        .grid {
            display: block !important;
        }
        .col-span-1,
        [class*="md:col-span-2"] {
            width: 100% !important;
            display: block !important;
            margin-bottom: 8px !important;
        }

        /* ── Kartu bersih tanpa shadow ── */
        .card {
            box-shadow: none !important;
            border: 1px solid #cbd5e1 !important;
            break-inside: avoid;
            margin-bottom: 10px !important;
        }

        /* ── Pertahankan warna semua elemen saat cetak ── */
        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            color-adjust: exact !important;
        }

        /* ── Ukuran halaman A4 landscape agar tabel perhitungan muat ── */
        @page {
            size: A4 landscape;
            margin: 15mm;
        }
    }
</style>

<script>
    document.getElementById('page-title').innerText = 'Hasil Analisis';
    window.addEventListener('load', function () {
        setTimeout(() => {
            document.getElementById('progressBar').style.width = '<?= $persentase ?>%';
        }, 400);
    });
</script>
