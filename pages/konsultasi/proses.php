<?php
/**
 * Alur tanya-jawab konsultasi.
 *
 * Penelusuran mengikuti Backward Chaining: hipotesis diuji berurutan
 * (H01..H04), dan untuk tiap hipotesis sistem menelusuri aturannya lalu
 * memeriksa indikator pendukung satu per satu.
 *
 * Setelah seluruh indikator terjawab, perhitungan Certainty Factor dijalankan,
 * hasilnya disimpan, lalu pengguna dialihkan ke halaman hasil. Penyimpanan
 * terjadi tepat satu kali sehingga muat ulang tidak menghasilkan data ganda.
 */

csrf_wajib();

$siswaModel      = new Siswa($db);
$aturanModel     = new Aturan($db);
$konsultasiModel = new Konsultasi($db);

// ─── Rencana penelusuran: hipotesis beserta indikator pendukungnya ───────────
$rencana = $aturanModel->getSemuaAturanPerKepribadian();

if (count($rencana) === 0) {
    header('Location: index.php?page=konsultasi');
    exit;
}

// Urutan pertanyaan mengikuti urutan penelusuran hipotesis
$antrean = [];
foreach ($rencana as $h) {
    foreach ($h['gejala'] as $g) {
        $antrean[] = [
            'id_gejala'   => (int) $g['id_gejala'],
            'kode_gejala' => $g['kode_gejala'],
            'nama_gejala' => $g['nama_gejala'],
            'nilai_cf'    => (float) $g['nilai_cf'],
            'kepribadian' => [
                'kode'      => $h['kode'],
                'kode_rule' => $h['kode_rule'],
                'tipe'      => $h['tipe'],
                'nama'      => $h['nama'],
            ],
        ];
    }
}
$totalPertanyaan = count($antrean);

// ─── Mulai sesi baru dari halaman pemilihan siswa ───────────────────────────
if (isset($_POST['id_siswa']) && !isset($_POST['id_gejala'])) {
    $siswa = $siswaModel->getSiswaById((int) $_POST['id_siswa']);

    if ($siswa) {
        $_SESSION['konsultasi'] = [
            'id_siswa' => (int) $siswa['id_siswa'],
            'jawaban'  => [],   // [id_gejala => cf_user]
        ];
        header('Location: index.php?page=proses');
        exit;
    }

    header('Location: index.php?page=konsultasi');
    exit;
}

// ─── Tanpa sesi aktif, kembalikan ke halaman awal ───────────────────────────
if (!isset($_SESSION['konsultasi'])) {
    header('Location: index.php?page=konsultasi');
    exit;
}

$state = &$_SESSION['konsultasi'];
$siswa = $siswaModel->getSiswaById($state['id_siswa']);

if (!$siswa) {
    unset($_SESSION['konsultasi']);
    header('Location: index.php?page=konsultasi');
    exit;
}

// ─── Terima satu jawaban, lalu alihkan (pola PRG) ───────────────────────────
if (isset($_POST['id_gejala'], $_POST['cf_user'])) {
    $id_gejala = (int) $_POST['id_gejala'];
    $cf_user   = (float) $_POST['cf_user'];

    // Terima hanya indikator yang memang ada pada rencana dan nilai dari skala resmi
    $indikatorSah = in_array($id_gejala, array_column($antrean, 'id_gejala'), true);
    $nilaiSah     = in_array($cf_user, array_values(MesinInferensiCF::SKALA), true);

    if ($indikatorSah && $nilaiSah) {
        $state['jawaban'][$id_gejala] = $cf_user;
    }

    header('Location: index.php?page=proses');
    exit;
}

// ─── Batalkan sesi ──────────────────────────────────────────────────────────
if (isset($_POST['batal'])) {
    unset($_SESSION['konsultasi']);
    header('Location: index.php?page=konsultasi');
    exit;
}

// ─── Mundur satu pertanyaan ─────────────────────────────────────────────────
if (isset($_POST['mundur'])) {
    if (count($state['jawaban']) > 0) {
        array_pop($state['jawaban']);
    }
    header('Location: index.php?page=proses');
    exit;
}

// ─── Cari pertanyaan berikutnya ─────────────────────────────────────────────
$berikutnya = null;
$nomor      = 0;

foreach ($antrean as $i => $q) {
    if (!array_key_exists($q['id_gejala'], $state['jawaban'])) {
        $berikutnya = $q;
        $nomor      = $i + 1;
        break;
    }
}

// ─── Seluruh indikator terjawab: hitung, simpan, alihkan ke hasil ───────────
if ($berikutnya === null) {
    $hasilPerHipotesis = [];

    foreach ($rencana as $h) {
        $gejalaData = [];
        foreach ($h['gejala'] as $g) {
            $gejalaData[] = [
                'kode_gejala' => $g['kode_gejala'],
                'nama_gejala' => $g['nama_gejala'],
                'nilai_cf'    => (float) $g['nilai_cf'],
                'cf_user'     => (float) ($state['jawaban'][$g['id_gejala']] ?? 0.0),
            ];
        }

        $hitung = MesinInferensiCF::hitungDenganDetail($gejalaData);

        $hasilPerHipotesis[] = [
            'id_kepribadian' => $h['id_kepribadian'],
            'cf_akhir'       => $hitung['cf_akhir'],
            'persentase'     => $hitung['persentase'],
        ];
    }

    $hasilPerHipotesis = MesinInferensiCF::peringkatHipotesis($hasilPerHipotesis);

    // Simpan jawaban lengkap dengan bobot pakar saat ini, agar dapat direproduksi
    $jawabanTersimpan = [];
    foreach ($antrean as $q) {
        $jawabanTersimpan[$q['id_gejala']] = [
            'cf_user'  => (float) ($state['jawaban'][$q['id_gejala']] ?? 0.0),
            'cf_pakar' => $q['nilai_cf'],
        ];
    }

    $log = implode("\n", MesinInferensiCF::logBackwardChaining($rencana, $state['jawaban']));

    try {
        $id_konsultasi = $konsultasiModel->simpanKonsultasi(
            $state['id_siswa'],
            $jawabanTersimpan,
            $hasilPerHipotesis,
            $log
        );
    } catch (Exception $e) {
        unset($_SESSION['konsultasi']);
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Gagal menyimpan hasil konsultasi: ' . $e->getMessage()];
        header('Location: index.php?page=konsultasi');
        exit;
    }

    // Sesi dibersihkan sebelum pengalihan agar hasil tidak tersimpan dua kali
    unset($_SESSION['konsultasi']);
    header('Location: index.php?page=hasil&id=' . $id_konsultasi);
    exit;
}

// ─── Data untuk tampilan ────────────────────────────────────────────────────
$jumlahTerjawab = count($state['jawaban']);
$persenProgres  = $totalPertanyaan > 0 ? round($jumlahTerjawab / $totalPertanyaan * 100) : 0;
$logPenelusuran = MesinInferensiCF::logBackwardChaining($rencana, $state['jawaban']);

$warnaTipe = [
    'D' => 'bg-red-100 text-red-700 border-red-200',
    'I' => 'bg-amber-100 text-amber-700 border-amber-200',
    'S' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
    'C' => 'bg-blue-100 text-blue-700 border-blue-200',
];
$clsAktif = $warnaTipe[$berikutnya['kepribadian']['tipe']] ?? 'bg-slate-100 text-slate-700 border-slate-200';
?>

<div class="mb-6">
    <h2 class="text-2xl font-bold text-slate-800">Pengisian Indikator</h2>
    <p class="text-slate-500 mt-1 text-sm">Pilih tingkat keyakinan yang paling menggambarkan diri Anda.</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">

    <!-- Panel kiri: sesi & log -->
    <div class="col-span-1 space-y-5">
        <div class="card p-5">
            <h3 class="font-bold text-slate-800 border-b border-slate-100 pb-3 mb-4 flex items-center gap-1.5 text-sm">
                <i data-lucide="info" class="w-4 h-4 text-brand-600"></i> Informasi Sesi
            </h3>
            <div class="space-y-3">
                <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase">Siswa</p>
                    <p class="font-semibold text-slate-700 text-sm"><?= htmlspecialchars($siswa['nama_siswa']) ?></p>
                    <p class="text-xs text-slate-500">NIS <?= htmlspecialchars($siswa['nis']) ?> &middot; <?= htmlspecialchars($siswa['kelas']) ?></p>
                </div>
                <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase mb-1">Hipotesis yang Sedang Diuji</p>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold border <?= $clsAktif ?>">
                        <span class="font-extrabold"><?= htmlspecialchars($berikutnya['kepribadian']['tipe']) ?></span>
                        <?= htmlspecialchars($berikutnya['kepribadian']['nama']) ?>
                    </span>
                    <p class="text-[11px] text-slate-400 mt-1.5">
                        <?= htmlspecialchars($berikutnya['kepribadian']['kode']) ?> melalui aturan <?= htmlspecialchars($berikutnya['kepribadian']['kode_rule']) ?>
                    </p>
                </div>
                <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase mb-1">Kemajuan</p>
                    <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                        <div class="h-2 rounded-full bg-brand-600 transition-all duration-500" style="width: <?= $persenProgres ?>%"></div>
                    </div>
                    <p class="text-xs text-slate-500 mt-1.5"><?= $jumlahTerjawab ?> dari <?= $totalPertanyaan ?> indikator terjawab</p>
                </div>
            </div>
        </div>

        <div class="card p-5">
            <h3 class="font-bold text-slate-800 border-b border-slate-100 pb-3 mb-3 flex items-center gap-1.5 text-sm">
                <i data-lucide="git-branch" class="w-4 h-4 text-brand-600"></i> Log Penelusuran
            </h3>
            <div class="bg-slate-900 rounded-xl p-3 max-h-72 overflow-y-auto text-[11px] font-mono text-slate-200 leading-relaxed">
                <?php foreach($logPenelusuran as $baris): ?>
                    <?php if($baris === ''): ?>
                        <div class="h-2"></div>
                    <?php else: ?>
                        <p class="<?= str_starts_with($baris, '  ') ? 'pl-3 text-slate-400' : 'text-brand-300 font-bold' ?>"><?= htmlspecialchars(trim($baris)) ?></p>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Panel kanan: pertanyaan -->
    <div class="col-span-1 md:col-span-2">
        <div class="card p-8">
            <div class="flex justify-between items-center mb-6">
                <span class="text-xs font-bold bg-blue-100 text-blue-700 border border-blue-200 px-3 py-1 rounded-full">
                    Pernyataan <?= $nomor ?> dari <?= $totalPertanyaan ?>
                </span>
                <span class="text-xs text-slate-400">Kode: <strong><?= htmlspecialchars($berikutnya['kode_gejala']) ?></strong></span>
            </div>

            <h3 class="text-xl font-bold text-slate-800 leading-snug mb-2">
                &ldquo;<?= htmlspecialchars($berikutnya['nama_gejala']) ?>&rdquo;
            </h3>
            <p class="text-sm text-slate-500 mb-8">Seberapa yakin pernyataan di atas menggambarkan diri Anda?</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                <?php
                $gaya = [
                    'Tidak'        => 'bg-slate-100 hover:bg-slate-200 text-slate-700 border-slate-200',
                    'Kurang Yakin' => 'bg-amber-50 hover:bg-amber-100 text-amber-800 border-amber-200',
                    'Cukup Yakin'  => 'bg-sky-50 hover:bg-sky-100 text-sky-800 border-sky-200',
                    'Yakin'        => 'bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border-emerald-200',
                    'Sangat Yakin' => 'bg-brand-600 hover:bg-brand-700 text-white border-brand-600',
                ];
                foreach (MesinInferensiCF::daftarSkala() as $label => $nilai):
                    $cls = $gaya[$label] ?? 'bg-slate-100 hover:bg-slate-200 text-slate-700 border-slate-200';
                ?>
                <form method="POST" action="index.php?page=proses">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id_gejala" value="<?= $berikutnya['id_gejala'] ?>">
                    <input type="hidden" name="cf_user" value="<?= $nilai ?>">
                    <button type="submit" class="w-full py-4 px-3 rounded-2xl font-bold text-sm border transition-colors <?= $cls ?>">
                        <span class="block"><?= $label ?></span>
                        <span class="block text-[11px] font-semibold opacity-70 mt-0.5"><?= number_format($nilai, 1) ?></span>
                    </button>
                </form>
                <?php endforeach; ?>
            </div>

            <div class="flex justify-between items-center mt-8 pt-5 border-t border-slate-100">
                <form method="POST" action="index.php?page=proses">
                    <?= csrf_field() ?>
                    <button type="submit" name="mundur" value="1" <?= $jumlahTerjawab === 0 ? 'disabled' : '' ?> class="text-sm font-medium flex items-center gap-1 <?= $jumlahTerjawab === 0 ? 'text-slate-300 cursor-not-allowed' : 'text-slate-500 hover:text-slate-700' ?>">
                        <i data-lucide="corner-up-left" class="w-4 h-4"></i> Ulangi jawaban sebelumnya
                    </button>
                </form>

                <form method="POST" action="index.php?page=proses" id="formBatal">
                    <?= csrf_field() ?>
                    <input type="hidden" name="batal" value="1">
                    <button type="button" onclick="batalkanSesi()" class="text-slate-400 hover:text-red-600 text-sm font-medium flex items-center gap-1">
                        <i data-lucide="trash-2" class="w-4 h-4"></i> Batalkan Sesi
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('page-title').innerText = 'Pengisian Indikator';

    function batalkanSesi() {
        Swal.fire({
            title: 'Batalkan sesi ini?',
            text: 'Semua jawaban yang sudah diisi akan hilang.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, batalkan',
            cancelButtonText: 'Lanjutkan'
        }).then((result) => {
            if (result.isConfirmed) document.getElementById('formBatal').submit();
        });
    }
</script>
