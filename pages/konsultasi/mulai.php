<?php
$siswaModel = new Siswa($db);
$masalahModel = new Masalah($db);
$aturanModel = new Aturan($db);

$dataSiswa = $siswaModel->getAllSiswa();
$dataMasalah = $masalahModel->getAllMasalah();

// Hitung jumlah gejala per masalah untuk ditampilkan di dropdown
$gejalaCount = [];
foreach ($dataMasalah as $m) {
    $gejalaCount[$m['id_masalah']] = count($aturanModel->getAturanByMasalah($m['id_masalah']));
}
?>

<!-- Tom-Select Styles & Scripts -->
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>

<style>
    /* Custom Styling Tom-Select agar serasi dengan Tailwind dan UI kita */
    .ts-wrapper .ts-control {
        border-radius: 0.75rem !important;
        padding: 0.625rem 1rem !important;
        border-color: #cbd5e1 !important;
        font-family: inherit !important;
        font-size: 0.875rem !important;
    }
    .ts-wrapper.focus .ts-control {
        border-color: #16a34a !important;
        box-shadow: 0 0 0 2px #dcfce7 !important;
    }
    .ts-dropdown {
        border-radius: 0.75rem !important;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1) !important;
        border-color: #e2e8f0 !important;
        margin-top: 5px !important;
        font-size: 0.875rem !important;
        padding: 4px !important;
    }
    .ts-dropdown .option {
        padding: 8px 12px !important;
        border-radius: 0.5rem !important;
        cursor: pointer;
    }
    .ts-dropdown .active {
        background-color: #16a34a !important;
        color: white !important;
    }
</style>

<div class="mb-6">
    <h2 class="text-2xl font-bold text-slate-800">Mulai Konsultasi (Analisis)</h2>
    <p class="text-slate-500 mt-1 text-sm">Persiapkan inisialisasi pelacakan masalah perilaku siswa.</p>
</div>

<?php if(count($dataSiswa) == 0 || count($dataMasalah) == 0): ?>
<div class="bg-orange-50 border border-orange-200 rounded-xl p-5 mb-6 flex items-start gap-3">
    <i data-lucide="alert-triangle" class="w-6 h-6 text-orange-500 shrink-0 mt-0.5"></i>
    <div>
        <h4 class="font-bold text-orange-800">Data Belum Siap</h4>
        <p class="text-sm text-orange-700 mt-1">Konsultasi tidak dapat dimulai karena:
            <?php if(count($dataSiswa) == 0): ?><br>• Data Siswa belum ada. <a href="index.php?page=siswa" class="underline font-semibold">Tambah Siswa</a>.<?php endif; ?>
            <?php if(count($dataMasalah) == 0): ?><br>• Data Masalah belum ada. <a href="index.php?page=masalah" class="underline font-semibold">Tambah Masalah</a>.<?php endif; ?>
        </p>
    </div>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <!-- Kolom Kiri: Form Setup (2 Kolom) -->
    <div class="col-span-1 md:col-span-2 space-y-6">
        <div class="card overflow-hidden">
            <div class="bg-brand-600 text-white p-6">
                <h3 class="font-bold text-lg flex items-center gap-2">
                    <i data-lucide="play-circle" class="w-5 h-5"></i> Inisialisasi Analisis Diagnostik
                </h3>
                <p class="text-brand-100 text-xs mt-1">Langkah awal untuk mencocokkan gejala klinis dengan dugaan masalah siswa.</p>
            </div>
            
            <form action="index.php?page=proses" method="POST" class="p-6 space-y-5">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2 flex items-center gap-1.5">
                        <i data-lucide="user" class="w-4 h-4 text-slate-400"></i> Pilih Siswa
                    </label>
                    <select id="select-siswa" name="id_siswa" required class="w-full">
                        <option value="">Cari nama siswa atau NIS...</option>
                        <?php foreach($dataSiswa as $s): ?>
                            <option value="<?= $s['id_siswa'] ?>"><?= $s['nis'] ?> - <?= htmlspecialchars($s['nama_siswa']) ?> (<?= $s['kelas'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2 flex items-center gap-1.5">
                        <i data-lucide="brain" class="w-4 h-4 text-slate-400"></i> Hipotesis Masalah (Dugaan Awal)
                    </label>
                    <select id="select-masalah" name="id_masalah" required class="w-full">
                        <option value="">Cari dugaan masalah perilaku...</option>
                        <?php foreach($dataMasalah as $m): 
                            $jml = $gejalaCount[$m['id_masalah']] ?? 0;
                            $siap = $jml > 0 ? "($jml gejala)" : "(belum ada gejala)";
                        ?>
                            <option value="<?= $m['id_masalah'] ?>" <?= $jml == 0 ? 'disabled' : '' ?>>
                                <?= $m['kode_masalah'] ?> - <?= htmlspecialchars($m['nama_masalah']) ?> <?= $siap ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="text-[10px] text-slate-400 mt-1.5">Metode Backward Chaining bekerja dengan baik jika hipotesis awal memiliki setidaknya satu gejala pendukung yang terdaftar.</p>
                </div>

                <div class="flex justify-end pt-4 border-t border-slate-100">
                    <button type="submit" <?= (count($dataSiswa) == 0 || count($dataMasalah) == 0) ? 'disabled class="bg-slate-300 cursor-not-allowed text-white px-6 py-2.5 rounded-xl font-bold flex items-center transition-colors"' : 'class="bg-brand-600 hover:bg-brand-700 text-white px-6 py-2.5 rounded-xl font-bold flex items-center transition-colors shadow-md shadow-brand-500/20"' ?>>
                        Mulai Analisis <i data-lucide="arrow-right" class="w-4 h-4 ml-2"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Kolom Kanan: Edu-Panel Penjelasan Teori Sistem Pakar (1 Kolom) -->
    <div class="col-span-1 space-y-5">
        <div class="card p-5 border-l-4 border-l-brand-600 bg-brand-50/20">
            <h4 class="font-bold text-slate-800 flex items-center gap-1.5 text-sm mb-3">
                <i data-lucide="git-branch" class="w-4.5 h-4.5 text-brand-600"></i> Backward Chaining
            </h4>
            <p class="text-xs text-slate-600 leading-relaxed">
                Metode pelacakan berbasis <strong>Goal-Driven</strong> (digerakkan oleh tujuan). 
                Sistem tidak langsung memeriksa semua gejala secara acak, melainkan fokus menanyakan gejala-gejala yang valid dan terikat dengan <strong>Hipotesis Masalah</strong> terpilih. 
                Jika pembuktian rute tersebut menemui kegagalan, sistem secara pintar melakukan <em>backtracking</em> (mundur dan mencari rute masalah lain).
            </p>
        </div>

        <div class="card p-5 border-l-4 border-l-orange-500 bg-orange-50/20">
            <h4 class="font-bold text-slate-800 flex items-center gap-1.5 text-sm mb-3">
                <i data-lucide="calculator" class="w-4.5 h-4.5 text-orange-600"></i> Dempster-Shafer
            </h4>
            <p class="text-xs text-slate-600 leading-relaxed">
                Teori matematika pembuktian berbasis <strong>derajat kepercayaan</strong> (<em>belief</em>). 
                Setiap gejala yang terverifikasi YA memiliki nilai kepercayaan ($m({H})$) dan ketidakpastian ($m({Θ})$) dari pakar. 
                Sistem akan menghitung kombinasi densitas kepercayaan dari bukti-bukti tersebut untuk menghasilkan persentase kepastian diagnosis akhir.
            </p>
        </div>
    </div>
</div>

<script>
    document.getElementById('page-title').innerText = 'Mulai Konsultasi';
    
    // Inisialisasi Tom-Select
    new TomSelect('#select-siswa', {
        create: false,
        sortField: {
            field: "text",
            direction: "asc"
        }
    });

    new TomSelect('#select-masalah', {
        create: false,
        sortField: {
            field: "text",
            direction: "asc"
        }
    });
</script>
