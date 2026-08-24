<?php
$siswaModel       = new Siswa($db);
$kepribadianModel = new Kepribadian($db);
$aturanModel      = new Aturan($db);

$dataSiswa = $siswaModel->getAllSiswa();
$rencana   = $aturanModel->getSemuaAturanPerKepribadian();

$totalIndikator = 0;
foreach ($rencana as $h) {
    $totalIndikator += count($h['gejala']);
}

$siapDipakai = count($dataSiswa) > 0 && count($rencana) > 0 && $totalIndikator > 0;

$warnaTipe = [
    'D' => 'bg-red-100 text-red-700 border-red-200',
    'I' => 'bg-amber-100 text-amber-700 border-amber-200',
    'S' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
    'C' => 'bg-blue-100 text-blue-700 border-blue-200',
];
?>

<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>

<style>
    .ts-wrapper .ts-control {
        border-radius: 0.75rem !important;
        padding: 0.625rem 1rem !important;
        border-color: #cbd5e1 !important;
        font-family: inherit !important;
        font-size: 0.875rem !important;
    }
    .ts-wrapper.focus .ts-control { border-color: #16a34a !important; box-shadow: 0 0 0 2px #dcfce7 !important; }
    .ts-dropdown { border-radius: 0.75rem !important; box-shadow: 0 10px 15px -3px rgba(0,0,0,.1) !important; border-color: #e2e8f0 !important; margin-top: 5px !important; font-size: 0.875rem !important; padding: 4px !important; }
    .ts-dropdown .option { padding: 8px 12px !important; border-radius: 0.5rem !important; cursor: pointer; }
    .ts-dropdown .active { background-color: #16a34a !important; color: white !important; }
</style>

<div class="mb-6">
    <h2 class="text-2xl font-bold text-slate-800">Mulai Konsultasi</h2>
    <p class="text-slate-500 mt-1 text-sm">Identifikasi kecenderungan tipe kepribadian siswa melalui <?= $totalIndikator ?> pernyataan indikator.</p>
</div>

<?php if(!$siapDipakai): ?>
<div class="bg-orange-50 border border-orange-200 rounded-xl p-5 mb-6 flex items-start gap-3">
    <i data-lucide="alert-triangle" class="w-6 h-6 text-orange-500 shrink-0 mt-0.5"></i>
    <div>
        <h4 class="font-bold text-orange-800">Data Belum Siap</h4>
        <p class="text-sm text-orange-700 mt-1">Konsultasi tidak dapat dimulai karena:
            <?php if(count($dataSiswa) == 0): ?><br>&bull; Data siswa belum ada. <a href="index.php?page=siswa" class="underline font-semibold">Tambah Siswa</a>.<?php endif; ?>
            <?php if(count($rencana) == 0 || $totalIndikator == 0): ?><br>&bull; Basis pengetahuan belum lengkap. <a href="index.php?page=aturan" class="underline font-semibold">Atur Aturan Pakar</a>.<?php endif; ?>
        </p>
    </div>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">

    <!-- Form pemilihan siswa -->
    <div class="col-span-1 md:col-span-2 space-y-6">
        <div class="card overflow-hidden">
            <div class="bg-brand-600 text-white p-6">
                <h3 class="font-bold text-lg flex items-center gap-2">
                    <i data-lucide="play-circle" class="w-5 h-5"></i> Pilih Siswa
                </h3>
                <p class="text-brand-100 text-xs mt-1">Seluruh tipe kepribadian akan diuji, sehingga tidak perlu menebak dugaan awal.</p>
            </div>

            <form action="index.php?page=proses" method="POST" class="p-6 space-y-5">
                <?= csrf_field() ?>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2 flex items-center gap-1.5">
                        <i data-lucide="user" class="w-4 h-4 text-slate-400"></i> Siswa yang dikonsultasikan
                    </label>
                    <select id="select-siswa" name="id_siswa" required class="w-full">
                        <option value="">Cari nama siswa atau NIS...</option>
                        <?php foreach($dataSiswa as $s): ?>
                            <option value="<?= $s['id_siswa'] ?>"><?= htmlspecialchars($s['nis']) ?> - <?= htmlspecialchars($s['nama_siswa']) ?> (<?= htmlspecialchars($s['kelas']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <p class="text-[11px] text-slate-400 mt-1.5">Jawaban diisi oleh siswa yang bersangkutan, didampingi guru BK.</p>
                </div>

                <div class="bg-slate-50 border border-slate-200 rounded-xl p-4">
                    <p class="text-xs font-bold text-slate-600 mb-2">Cara pengisian</p>
                    <ol class="list-decimal list-inside space-y-1 text-xs text-slate-600">
                        <li>Sistem menampilkan pernyataan satu per satu.</li>
                        <li>Pilih tingkat keyakinan yang paling sesuai, dari <em>Tidak</em> sampai <em>Sangat Yakin</em>.</li>
                        <li>Setelah seluruh pernyataan terjawab, hasilnya muncul dalam bentuk peringkat empat tipe.</li>
                    </ol>
                </div>

                <div class="flex justify-end pt-4 border-t border-slate-100">
                    <button type="submit" <?= !$siapDipakai ? 'disabled' : '' ?> class="<?= $siapDipakai ? 'bg-brand-600 hover:bg-brand-700 shadow-md shadow-brand-500/20' : 'bg-slate-300 cursor-not-allowed' ?> text-white px-6 py-2.5 rounded-xl font-bold flex items-center transition-colors">
                        Mulai Konsultasi <i data-lucide="arrow-right" class="w-4 h-4 ml-2"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Panel penjelasan -->
    <div class="col-span-1 space-y-5">
        <div class="card p-5 border-l-4 border-l-brand-600 bg-brand-50/20">
            <h4 class="font-bold text-slate-800 flex items-center gap-1.5 text-sm mb-3">
                <i data-lucide="git-branch" class="w-4 h-4 text-brand-600"></i> Backward Chaining
            </h4>
            <p class="text-xs text-slate-600 leading-relaxed">
                Penalaran berangkat dari <strong>hipotesis</strong>, bukan dari fakta. Sistem menetapkan
                satu tipe sebagai tujuan, menelusuri mundur ke aturan yang menghasilkannya, lalu
                memeriksa indikator pendukungnya. Proses ini diulang untuk keempat tipe.
            </p>
        </div>

        <div class="card p-5 border-l-4 border-l-orange-500 bg-orange-50/20">
            <h4 class="font-bold text-slate-800 flex items-center gap-1.5 text-sm mb-3">
                <i data-lucide="calculator" class="w-4 h-4 text-orange-600"></i> Certainty Factor
            </h4>
            <p class="text-xs text-slate-600 leading-relaxed">
                Keyakinan pakar dikalikan dengan keyakinan penjawab, lalu digabungkan bertahap
                menggunakan rumus <span class="font-mono">CF&#8321; + CF&#8322; &times; (1 &minus; CF&#8321;)</span>
                hingga diperoleh nilai akhir tiap tipe.
            </p>
        </div>

        <div class="card p-5">
            <h4 class="font-bold text-slate-800 text-sm mb-3 flex items-center gap-1.5">
                <i data-lucide="list-checks" class="w-4 h-4 text-brand-600"></i> Yang akan diuji
            </h4>
            <div class="space-y-2">
                <?php foreach($rencana as $h):
                    $cls = $warnaTipe[$h['tipe']] ?? 'bg-slate-100 text-slate-700 border-slate-200';
                ?>
                <div class="flex justify-between items-center text-xs">
                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded font-bold border <?= $cls ?>">
                        <span class="font-extrabold"><?= htmlspecialchars($h['tipe']) ?></span> <?= htmlspecialchars($h['nama']) ?>
                    </span>
                    <span class="text-slate-400 font-medium"><?= htmlspecialchars($h['kode_rule']) ?> &middot; <?= count($h['gejala']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('page-title').innerText = 'Mulai Konsultasi';

    new TomSelect('#select-siswa', {
        create: false,
        sortField: { field: "text", direction: "asc" }
    });
</script>
