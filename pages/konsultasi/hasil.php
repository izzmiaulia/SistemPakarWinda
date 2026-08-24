<?php
/**
 * Halaman hasil konsultasi.
 *
 * Halaman ini hanya membaca sesi konsultasi yang sudah tersimpan berdasarkan
 * id. Penyimpanan dilakukan di proses.php, sehingga memuat ulang halaman ini
 * tidak pernah menghasilkan data ganda dan tautannya dapat dibuka kembali
 * kapan saja.
 *
 * Rincian langkah dihitung ulang dari nilai yang tersimpan (cf_pakar dan
 * cf_user pada saat konsultasi), bukan dari bobot yang berlaku sekarang,
 * sehingga hasil lama tetap konsisten meski bobot pakar diubah.
 */

$konsultasiModel = new Konsultasi($db);

$id_konsultasi = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$detail        = $id_konsultasi > 0 ? $konsultasiModel->getDetailKonsultasi($id_konsultasi) : null;

if (!$detail) {
    header('Location: index.php?page=konsultasi');
    exit;
}

// ─── Kelompokkan jawaban menurut hipotesis ──────────────────────────────────
$perHipotesis = [];
foreach ($detail['jawaban'] as $j) {
    if (empty($j['id_kepribadian'])) {
        continue; // indikator yang aturannya sudah dihapus
    }
    $perHipotesis[$j['id_kepribadian']][] = [
        'kode_gejala' => $j['kode_gejala'],
        'nama_gejala' => $j['nama_gejala'],
        'nilai_cf'    => (float) $j['cf_pakar'],
        'cf_user'     => (float) $j['cf_user'],
    ];
}

// ─── Hitung ulang rincian langkah tiap hipotesis ────────────────────────────
$rincian = [];
foreach ($detail['peringkat'] as $p) {
    $gejalaData = $perHipotesis[$p['id_kepribadian']] ?? [];
    $rincian[$p['id_kepribadian']] = MesinInferensiCF::hitungDenganDetail($gejalaData);
}

$pemenang   = $detail['peringkat'][0] ?? null;
$labelSkala = array_flip(array_map('strval', MesinInferensiCF::SKALA));

$warnaTipe = [
    'D' => ['cls' => 'bg-red-100 text-red-700 border-red-200',         'bar' => 'bg-red-500',     'teks' => 'text-red-700'],
    'I' => ['cls' => 'bg-amber-100 text-amber-700 border-amber-200',   'bar' => 'bg-amber-500',   'teks' => 'text-amber-700'],
    'S' => ['cls' => 'bg-emerald-100 text-emerald-700 border-emerald-200', 'bar' => 'bg-emerald-500', 'teks' => 'text-emerald-700'],
    'C' => ['cls' => 'bg-blue-100 text-blue-700 border-blue-200',      'bar' => 'bg-blue-500',    'teks' => 'text-blue-700'],
];

$rekomendasiKosong = empty($detail['rekomendasi'])
    || str_starts_with(trim($detail['rekomendasi']), '[BELUM DIISI]');
?>

<div class="mb-6 print:mb-4">
    <h2 class="text-2xl font-bold text-slate-800">Hasil Identifikasi Kepribadian</h2>
    <p class="text-slate-500 mt-1 text-sm">Penelusuran Backward Chaining dan perhitungan Certainty Factor untuk keempat tipe.</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">

    <!-- ══ Kolom kiri ══ -->
    <div class="col-span-1 space-y-5">
        <div class="card p-5 border-l-4 border-l-blue-500">
            <h3 class="font-bold text-slate-800 border-b border-slate-100 pb-2 mb-3 text-xs uppercase tracking-wider">Data Siswa</h3>
            <p class="font-semibold text-slate-700 text-base"><?= htmlspecialchars($detail['nama_siswa']) ?></p>
            <p class="text-sm text-slate-500 mt-0.5">NIS <?= htmlspecialchars($detail['nis']) ?> &middot; <?= htmlspecialchars($detail['kelas']) ?></p>
            <p class="text-xs text-slate-400 mt-2"><?= date('d M Y, H:i', strtotime($detail['tanggal'])) ?> WIB</p>
        </div>

        <?php if($pemenang):
            $w = $warnaTipe[$pemenang['tipe']] ?? ['cls'=>'bg-slate-100 text-slate-700 border-slate-200','bar'=>'bg-slate-500','teks'=>'text-slate-700'];
        ?>
        <div class="card p-5 text-center border-2 <?= $w['cls'] ?>">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Kecenderungan Utama</p>
            <p class="text-4xl font-extrabold <?= $w['teks'] ?>"><?= htmlspecialchars($pemenang['tipe']) ?></p>
            <p class="text-sm font-bold text-slate-700 mt-1"><?= htmlspecialchars($pemenang['nama']) ?></p>
            <p class="text-3xl font-extrabold text-slate-800 mt-3"><?= $pemenang['persentase'] ?>%</p>
            <p class="text-[11px] text-slate-500 mt-1">CF akhir <?= rtrim(rtrim($pemenang['cf_akhir'], '0'), '.') ?></p>
        </div>
        <?php endif; ?>

        <div class="card p-5">
            <h3 class="font-bold text-slate-800 border-b border-slate-100 pb-2 mb-3 text-xs uppercase tracking-wider">Deskripsi Tipe</h3>
            <p class="text-sm text-slate-600 leading-relaxed"><?= nl2br(htmlspecialchars($detail['deskripsi'] ?? '')) ?></p>
        </div>
    </div>

    <!-- ══ Kolom kanan ══ -->
    <div class="col-span-1 md:col-span-2 space-y-5">

        <!-- Tahap I: Backward Chaining -->
        <div class="card overflow-hidden border border-brand-200">
            <div class="bg-brand-600 text-white px-5 py-4">
                <h3 class="font-bold text-base flex items-center gap-2">
                    <i data-lucide="git-branch" class="w-5 h-5"></i> Tahap I: Penelusuran Mundur (Backward Chaining)
                </h3>
                <p class="text-brand-100 text-xs mt-1">
                    Sistem berangkat dari hipotesis, menelusuri aturan yang menghasilkannya, lalu memeriksa indikator pendukungnya.
                </p>
            </div>

            <?php if(!empty($detail['log_proses'])): ?>
            <div class="p-5">
                <div class="bg-slate-900 rounded-xl p-4 text-[11px] font-mono text-slate-200 leading-relaxed max-h-80 overflow-y-auto">
                    <?php foreach(explode("\n", $detail['log_proses']) as $baris): ?>
                        <?php if(trim($baris) === ''): ?>
                            <div class="h-2"></div>
                        <?php else: ?>
                            <p class="<?= str_starts_with($baris, '  ') ? 'pl-3 text-slate-400' : 'text-brand-300 font-bold' ?>"><?= htmlspecialchars(trim($baris)) ?></p>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Tahap II: Peringkat Certainty Factor -->
        <div class="card overflow-hidden border border-slate-200">
            <div class="bg-slate-800 text-white px-5 py-4">
                <h3 class="font-bold text-base flex items-center gap-2">
                    <i data-lucide="calculator" class="w-5 h-5 text-brand-400"></i> Tahap II: Perhitungan Certainty Factor
                </h3>
                <p class="text-slate-400 text-xs mt-1">
                    Keempat hipotesis dihitung, lalu diperingkat. Yang menentukan kesimpulan adalah urutannya, bukan besar angkanya.
                </p>
            </div>

            <div class="p-5 space-y-4">
                <div class="bg-slate-50 rounded-xl p-4 font-mono text-[11px] text-slate-700 border border-slate-200 leading-relaxed overflow-x-auto">
                    <p class="text-brand-700 font-bold mb-1">// Rumus Certainty Factor</p>
                    <p>CF Evidence = CF Pakar &times; CF Pengguna</p>
                    <p>CF Kombinasi = CF&#8321; + CF&#8322; &times; (1 &minus; CF&#8321;)</p>
                </div>

                <!-- Peringkat -->
                <div class="space-y-3">
                    <?php foreach($detail['peringkat'] as $p):
                        $w   = $warnaTipe[$p['tipe']] ?? ['cls'=>'bg-slate-100 text-slate-700 border-slate-200','bar'=>'bg-slate-400','teks'=>'text-slate-700'];
                        $utama = ((int) $p['peringkat'] === 1);
                    ?>
                    <div class="border <?= $utama ? 'border-brand-300 bg-brand-50/40' : 'border-slate-200' ?> rounded-xl p-4">
                        <div class="flex items-center justify-between gap-3 mb-2">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <span class="w-7 h-7 shrink-0 rounded-lg bg-slate-800 text-white text-xs font-extrabold flex items-center justify-center"><?= $p['peringkat'] ?></span>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold border <?= $w['cls'] ?>">
                                    <span class="font-extrabold"><?= htmlspecialchars($p['tipe']) ?></span> <?= htmlspecialchars($p['nama']) ?>
                                </span>
                                <span class="text-[10px] text-slate-400 font-medium hidden sm:inline"><?= htmlspecialchars($p['kode']) ?> &middot; <?= htmlspecialchars($p['kode_rule']) ?></span>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="text-lg font-extrabold text-slate-800"><?= $p['persentase'] ?>%</p>
                                <p class="text-[10px] text-slate-400 font-mono"><?= rtrim(rtrim($p['cf_akhir'], '0'), '.') ?></p>
                            </div>
                        </div>

                        <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                            <div class="h-2 rounded-full <?= $w['bar'] ?>" style="width: <?= min(100, (float) $p['persentase']) ?>%"></div>
                        </div>

                        <?php $det = $rincian[$p['id_kepribadian']] ?? null; ?>
                        <?php if($det && count($det['langkah']) > 0): ?>
                        <details class="mt-3 group">
                            <summary class="cursor-pointer text-xs font-semibold text-slate-500 hover:text-brand-700 select-none flex items-center gap-1">
                                <i data-lucide="chevron-right" class="w-3.5 h-3.5 transition-transform group-open:rotate-90"></i>
                                Rincian perhitungan <?= htmlspecialchars($p['kode_rule']) ?>
                            </summary>

                            <div class="mt-3 space-y-3">
                                <?php foreach($det['langkah'] as $lk): ?>
                                <div class="border border-slate-200 bg-white rounded-xl p-3">
                                    <p class="font-bold text-slate-800 text-xs"><?= htmlspecialchars($lk['judul']) ?></p>
                                    <p class="text-[11px] text-slate-500 mt-0.5"><?= htmlspecialchars($lk['uraian']) ?></p>
                                    <div class="bg-slate-900 rounded-lg p-3 font-mono text-[10.5px] leading-relaxed overflow-x-auto mt-2">
                                        <?php foreach($lk['rumus'] as $baris): ?>
                                            <?php if($baris === ''): ?>
                                                <div class="h-2"></div>
                                            <?php elseif(str_starts_with($baris, 'Rumus:')): ?>
                                                <p class="text-yellow-400"><?= htmlspecialchars($baris) ?></p>
                                            <?php else: ?>
                                                <p class="text-emerald-300"><?= htmlspecialchars($baris) ?></p>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </details>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Jawaban indikator -->
        <div class="card overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100">
                <h3 class="font-bold text-slate-800 text-sm flex items-center gap-2">
                    <i data-lucide="list-checks" class="w-4 h-4 text-brand-600"></i>
                    Jawaban Indikator (<?= count($detail['jawaban']) ?>)
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-100">
                            <th class="px-5 py-2.5 font-bold text-slate-500 uppercase w-16">Kode</th>
                            <th class="px-5 py-2.5 font-bold text-slate-500 uppercase">Pernyataan</th>
                            <th class="px-5 py-2.5 font-bold text-slate-500 uppercase text-center w-16">Tipe</th>
                            <th class="px-5 py-2.5 font-bold text-slate-500 uppercase text-center w-32">Jawaban</th>
                            <th class="px-5 py-2.5 font-bold text-slate-500 uppercase text-center w-24">CF Evidence</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php foreach($detail['jawaban'] as $j):
                            $cfUser = (float) $j['cf_user'];
                            $cfEv   = MesinInferensiCF::cfEvidence((float) $j['cf_pakar'], $cfUser);
                            $nama   = $labelSkala[(string) $cfUser] ?? $cfUser;
                        ?>
                        <tr class="<?= $cfUser == 0.0 ? 'opacity-50' : '' ?>">
                            <td class="px-5 py-2.5">
                                <span class="bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded text-[10px] font-bold border border-blue-100"><?= htmlspecialchars($j['kode_gejala']) ?></span>
                            </td>
                            <td class="px-5 py-2.5 text-slate-600"><?= htmlspecialchars($j['nama_gejala']) ?></td>
                            <td class="px-5 py-2.5 text-center font-extrabold text-slate-500"><?= htmlspecialchars($j['tipe'] ?? '-') ?></td>
                            <td class="px-5 py-2.5 text-center">
                                <span class="text-slate-700 font-semibold"><?= htmlspecialchars($nama) ?></span>
                                <span class="text-slate-400 font-mono ml-1">(<?= number_format($cfUser, 1) ?>)</span>
                            </td>
                            <td class="px-5 py-2.5 text-center font-mono font-bold text-slate-700"><?= rtrim(rtrim(number_format($cfEv, 4, '.', ''), '0'), '.') ?: '0' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Rekomendasi -->
        <div class="card p-5">
            <h4 class="font-bold text-slate-800 flex items-center mb-3 text-sm">
                <i data-lucide="lightbulb" class="w-4 h-4 text-orange-500 mr-2"></i>
                Rekomendasi Layanan Bimbingan &amp; Konseling
            </h4>
            <?php if($rekomendasiKosong): ?>
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 flex items-start gap-3">
                <i data-lucide="alert-circle" class="w-5 h-5 text-amber-500 shrink-0 mt-0.5"></i>
                <div class="text-sm">
                    <p class="font-bold text-amber-800">Rekomendasi belum diisi</p>
                    <p class="text-amber-700 mt-0.5 text-xs">
                        Tindak lanjut untuk tipe <?= htmlspecialchars($detail['nama_kepribadian']) ?> belum dirumuskan.
                        Isi melalui menu <a href="index.php?page=kepribadian" class="underline font-semibold">Tipe Kepribadian</a>.
                    </p>
                </div>
            </div>
            <?php else: ?>
            <p class="text-slate-600 text-sm leading-relaxed"><?= nl2br(htmlspecialchars($detail['rekomendasi'])) ?></p>
            <?php endif; ?>
        </div>

        <!-- Tombol aksi -->
        <div class="flex flex-wrap gap-3 print:hidden">
            <a href="index.php?page=konsultasi" class="px-5 py-2.5 bg-white border border-slate-300 text-slate-700 font-medium rounded-xl hover:bg-slate-50 transition-colors flex items-center gap-2">
                <i data-lucide="refresh-cw" class="w-4 h-4"></i> Konsultasi Baru
            </a>
            <a href="index.php?page=riwayat" class="px-5 py-2.5 bg-brand-600 text-white font-medium rounded-xl hover:bg-brand-700 transition-colors shadow-sm flex items-center gap-2">
                <i data-lucide="history" class="w-4 h-4"></i> Lihat Riwayat
            </a>
            <a href="download_perhitungan.php?id=<?= $detail['id_konsultasi'] ?>" target="_blank" class="px-5 py-2.5 bg-emerald-600 text-white font-medium rounded-xl hover:bg-emerald-700 transition-colors shadow-sm flex items-center gap-2">
                <i data-lucide="file-spreadsheet" class="w-4 h-4"></i> Unduh Perhitungan
            </a>
            <button onclick="window.print()" class="px-5 py-2.5 bg-slate-800 text-white font-medium rounded-xl hover:bg-slate-900 transition-colors shadow-sm flex items-center gap-2">
                <i data-lucide="printer" class="w-4 h-4"></i> Cetak
            </button>
        </div>
    </div>
</div>

<style>
    @media print {
        aside#sidebar, header, .print\:hidden { display: none !important; }
        body { background: white !important; overflow: visible !important; height: auto !important; display: block !important; }
        main { margin: 0 !important; padding: 0 !important; width: 100% !important; height: auto !important; overflow: visible !important; display: block !important; flex: unset !important; }
        main > div.p-8 { padding: 10mm !important; }
        .grid { display: block !important; }
        .col-span-1, [class*="md:col-span-2"] { width: 100% !important; display: block !important; margin-bottom: 8px !important; }
        .card { box-shadow: none !important; border: 1px solid #cbd5e1 !important; break-inside: avoid; margin-bottom: 10px !important; }
        details { display: block !important; }
        details > div { display: block !important; }
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        @page { size: A4 portrait; margin: 15mm; }
    }
</style>

<script>
    document.getElementById('page-title').innerText = 'Hasil Konsultasi';
    // Buka seluruh rincian saat dicetak
    window.addEventListener('beforeprint', function () {
        document.querySelectorAll('details').forEach(function (d) { d.open = true; });
    });
</script>
