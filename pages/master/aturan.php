<?php
csrf_wajib();

$kepribadianModel = new Kepribadian($db);
$aturanModel      = new Aturan($db);
$gejalaModel      = new Gejala($db);

// Inisialisasi Flash Message helper
if (!function_exists('setFlash')) {
    function setFlash($type, $msg) {
        $_SESSION['flash'] = ['type' => $type, 'message' => $msg];
    }
}

// Tangani aksi CRUD
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['tambah'])) {
        $id_kepribadian = $_POST['id_kepribadian'];
        $id_gejala      = $_POST['id_gejala'];
        $nilai_cf       = (float) $_POST['nilai_cf'];

        if ($aturanModel->cekRelasiAda($id_kepribadian, $id_gejala)) {
            setFlash('error', 'Gagal: Indikator tersebut sudah direlasikan ke tipe ini. Satu indikator hanya boleh memiliki satu nilai per tipe.');
        } elseif ($nilai_cf < 0 || $nilai_cf > 1) {
            setFlash('error', 'Gagal: Nilai CF harus berada pada rentang 0 sampai 1.');
        } else {
            $aturanModel->tambahAturan($id_kepribadian, $id_gejala, $nilai_cf);
            setFlash('success', 'Relasi aturan baru berhasil ditambahkan.');
        }
        header("Location: index.php?page=aturan"); exit;

    } elseif (isset($_POST['ubah'])) {
        $id_aturan = (int) $_POST['id_aturan'];
        $nilai_cf  = (float) $_POST['nilai_cf'];

        if ($nilai_cf < 0 || $nilai_cf > 1) {
            setFlash('error', 'Gagal: Nilai CF harus berada pada rentang 0 sampai 1.');
        } else {
            $aturanModel->ubahNilaiCf($id_aturan, $nilai_cf);
            setFlash('success', 'Nilai keyakinan pakar berhasil diperbarui.');
        }
        header("Location: index.php?page=aturan"); exit;
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus'])) {
    $aturanModel->hapusAturan((int) $_POST['hapus']);
    setFlash('success', 'Relasi aturan berhasil dihapus.');
    header("Location: index.php?page=aturan"); exit;
}

$dataKepribadian = $kepribadianModel->getAllKepribadian();
$dataGejala      = $gejalaModel->getAllGejala();

$warnaTipe = [
    'D' => 'bg-red-100 text-red-700 border-red-200',
    'I' => 'bg-amber-100 text-amber-700 border-amber-200',
    'S' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
    'C' => 'bg-blue-100 text-blue-700 border-blue-200',
];
?>

<div class="mb-6 flex flex-col md:flex-row md:justify-between md:items-end gap-4">
    <div>
        <h2 class="text-2xl font-bold text-slate-800">Basis Pengetahuan (Aturan Pakar)</h2>
        <p class="text-slate-500 mt-1 text-sm">Relasi antara tipe kepribadian dan indikator pendukungnya, beserta nilai keyakinan pakar (CF).</p>
    </div>
    <div class="flex">
        <button onclick="document.getElementById('modalTambah').classList.remove('hidden')" class="bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 rounded-xl font-medium flex items-center transition-colors shadow-sm">
            <i data-lucide="plus" class="w-4 h-4 mr-2"></i> Tambah Aturan
        </button>
    </div>
</div>

<!-- SweetAlert2 Flash Message -->
<?php if(isset($_SESSION['flash'])): ?>
    <?php
        $flash = $_SESSION['flash'];
        $icon  = $flash['type'] == 'success' ? 'success' : 'error';
        $title = $flash['type'] == 'success' ? 'Berhasil!' : 'Gagal!';
    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: '<?= $icon ?>',
                title: '<?= $title ?>',
                text: <?= json_encode($flash['message']) ?>,
                confirmButtonColor: '#16a34a'
            });
        });
    </script>
    <?php unset($_SESSION['flash']); ?>
<?php endif; ?>

<div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6 flex items-start gap-3">
    <i data-lucide="info" class="w-5 h-5 text-blue-500 shrink-0 mt-0.5"></i>
    <div class="text-sm">
        <p class="font-bold text-blue-800">Nilai dapat diubah tanpa mengubah aplikasi</p>
        <p class="text-blue-700 mt-0.5">Bobot yang tersimpan saat ini mengikuti tabel bobot pakar pada dokumen penelitian. Bila hasil validasi pakar berbeda, ubah langsung di halaman ini &mdash; perhitungan akan mengikuti nilai terbaru.</p>
    </div>
</div>

<!-- Daftar Accordion per Tipe Kepribadian -->
<div class="space-y-4 mb-10">
    <?php foreach($dataKepribadian as $index => $k):
        $id_kepribadian = $k['id_kepribadian'];
        $dataAturan     = $aturanModel->getAturanByKepribadian($id_kepribadian);
        $isOpen         = ($index === 0) ? 'true' : 'false';
        $cls            = $warnaTipe[$k['tipe']] ?? 'bg-slate-100 text-slate-700 border-slate-200';
    ?>
    <div class="card overflow-hidden" data-accordion>
        <button type="button" class="w-full text-left bg-white hover:bg-slate-50 px-6 py-4 flex justify-between items-center focus:outline-none transition-colors" onclick="toggleAccordion('acc-<?= $id_kepribadian ?>', this)">
            <h3 class="font-bold text-slate-800 flex items-center flex-wrap gap-2">
                <span class="bg-orange-100 text-orange-700 px-2.5 py-1 rounded-md text-xs border border-orange-200"><?= htmlspecialchars($k['kode']) ?></span>
                <span class="bg-slate-100 text-slate-600 px-2.5 py-1 rounded-md text-xs border border-slate-200"><?= htmlspecialchars($k['kode_rule']) ?></span>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold border <?= $cls ?>">
                    <span class="font-extrabold"><?= htmlspecialchars($k['tipe']) ?></span> <?= htmlspecialchars($k['nama']) ?>
                </span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-500"><?= count($dataAturan) ?> Indikator</span>
            </h3>
            <div class="text-slate-400 transform transition-transform duration-200 <?= $isOpen === 'true' ? 'rotate-180' : '' ?>">
                <i data-lucide="chevron-down" class="w-5 h-5"></i>
            </div>
        </button>

        <div id="acc-<?= $id_kepribadian ?>" class="transition-all duration-300 ease-in-out border-t border-slate-100 <?= $isOpen === 'true' ? '' : 'hidden' ?>">
            <div class="p-0">
                <?php if(count($dataAturan) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-100">
                                <th class="px-6 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider w-24">Kode</th>
                                <th class="px-6 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Indikator</th>
                                <th class="px-6 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider w-32 text-center">Nilai CF Pakar</th>
                                <th class="px-6 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider text-right w-28">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <?php foreach($dataAturan as $a): ?>
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="px-6 py-3 text-sm">
                                    <span class="bg-blue-100 text-blue-700 px-2 py-1 rounded-md text-xs font-bold border border-blue-200"><?= $a['kode_gejala'] ?></span>
                                </td>
                                <td class="px-6 py-3 text-sm text-slate-700 font-medium"><?= htmlspecialchars($a['nama_gejala']) ?></td>
                                <td class="px-6 py-3 text-center">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                        <?= $a['nilai_cf'] ?>
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-sm text-right">
                                    <button onclick='editAturan(<?= json_encode([
                                        "id"    => $a["id_aturan"],
                                        "kode"  => $a["kode_gejala"],
                                        "nama"  => $a["nama_gejala"],
                                        "tipe"  => $k["nama"],
                                        "nilai" => (float) $a["nilai_cf"],
                                    ], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)' class="text-blue-600 hover:text-blue-800 p-1.5 rounded-md hover:bg-blue-50 transition-colors inline-block" title="Ubah Nilai CF">
                                        <i data-lucide="edit-3" class="w-4 h-4"></i>
                                    </button>
                                    <button onclick="confirmHapus(<?= $a['id_aturan'] ?>)" class="text-red-500 hover:text-red-700 p-1.5 rounded-md hover:bg-red-50 transition-colors ml-1 inline-block" title="Hapus Relasi">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="px-6 py-10 text-center flex flex-col items-center justify-center">
                    <i data-lucide="link-2-off" class="w-10 h-10 text-slate-300 mb-2"></i>
                    <p class="text-slate-500 text-sm font-medium">Belum ada indikator yang direlasikan ke tipe ini.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Modal Tambah Aturan -->
<div id="modalTambah" class="fixed inset-0 bg-slate-900/50 hidden flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl w-full max-w-md p-6 shadow-xl">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-slate-800">Tambah Relasi Aturan</h3>
            <button onclick="document.getElementById('modalTambah').classList.add('hidden')" class="text-slate-400 hover:text-slate-600"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>
        <form method="POST">
            <?= csrf_field() ?>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Tipe Kepribadian (Hipotesis)</label>
                    <select name="id_kepribadian" required class="w-full border border-slate-300 rounded-xl px-3 py-2 outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 bg-white">
                        <option value="">-- Pilih Tipe --</option>
                        <?php foreach($dataKepribadian as $k): ?>
                            <option value="<?= $k['id_kepribadian'] ?>">[<?= $k['kode'] ?>] <?= htmlspecialchars($k['tipe'] . ' - ' . $k['nama']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Indikator</label>
                    <select name="id_gejala" required class="w-full border border-slate-300 rounded-xl px-3 py-2 outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 bg-white">
                        <option value="">-- Pilih Indikator --</option>
                        <?php foreach($dataGejala as $g): ?>
                            <option value="<?= $g['id_gejala'] ?>">[<?= $g['kode_gejala'] ?>] <?= htmlspecialchars($g['nama_gejala']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Nilai CF Pakar (0 &ndash; 1)</label>
                    <input type="number" step="0.01" min="0" max="1" name="nilai_cf" required class="w-full border border-slate-300 rounded-xl px-4 py-2 outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500" placeholder="0.8">
                    <p class="text-xs text-slate-500 mt-1">Tingkat keyakinan pakar bahwa indikator ini menandakan tipe tersebut.</p>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('modalTambah').classList.add('hidden')" class="bg-white border border-slate-200 text-slate-700 px-4 py-2 rounded-xl font-medium hover:bg-slate-50">Batal</button>
                <button type="submit" name="tambah" class="bg-brand-600 text-white px-4 py-2 rounded-xl font-medium hover:bg-brand-700 shadow-sm">Simpan Relasi</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Ubah Nilai CF -->
<div id="modalEdit" class="fixed inset-0 bg-slate-900/50 hidden flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl w-full max-w-md p-6 shadow-xl">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-slate-800">Ubah Nilai CF Pakar</h3>
            <button onclick="document.getElementById('modalEdit').classList.add('hidden')" class="text-slate-400 hover:text-slate-600"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>
        <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="id_aturan" id="edit_id_aturan">
            <div class="space-y-4">
                <div class="bg-slate-50 border border-slate-200 rounded-xl p-3">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Indikator</p>
                    <p class="text-sm font-semibold text-slate-800 mt-0.5"><span id="edit_kode_gejala" class="text-blue-700"></span> &mdash; <span id="edit_nama_gejala"></span></p>
                    <p class="text-xs text-slate-500 mt-1">Tipe: <span id="edit_nama_tipe" class="font-semibold"></span></p>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Nilai CF Pakar (0 &ndash; 1)</label>
                    <input type="number" step="0.01" min="0" max="1" name="nilai_cf" id="edit_nilai_cf" required class="w-full border border-slate-300 rounded-xl px-4 py-2 outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
                    <p class="text-xs text-slate-500 mt-1">Perubahan langsung berlaku pada perhitungan konsultasi berikutnya. Hasil konsultasi yang sudah tersimpan tidak ikut berubah.</p>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('modalEdit').classList.add('hidden')" class="bg-white border border-slate-200 text-slate-700 px-4 py-2 rounded-xl font-medium hover:bg-slate-50">Batal</button>
                <button type="submit" name="ubah" class="bg-brand-600 text-white px-4 py-2 rounded-xl font-medium hover:bg-brand-700 shadow-sm">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<!-- Form penghapusan (POST + token) -->
<form id="formHapus" method="POST" action="index.php?page=aturan" class="hidden">
    <?= csrf_field() ?>
    <input type="hidden" name="hapus" id="idHapus">
</form>

<script>
    document.getElementById('page-title').innerText = 'Basis Pengetahuan';

    function toggleAccordion(contentId, buttonElement) {
        const content = document.getElementById(contentId);
        const icon = buttonElement.querySelector('div.transform');

        if (content.classList.contains('hidden')) {
            content.classList.remove('hidden');
            icon.classList.add('rotate-180');
        } else {
            content.classList.add('hidden');
            icon.classList.remove('rotate-180');
        }
    }

    function editAturan(data) {
        document.getElementById('edit_id_aturan').value    = data.id;
        document.getElementById('edit_kode_gejala').innerText = data.kode;
        document.getElementById('edit_nama_gejala').innerText = data.nama;
        document.getElementById('edit_nama_tipe').innerText   = data.tipe;
        document.getElementById('edit_nilai_cf').value      = data.nilai;
        document.getElementById('modalEdit').classList.remove('hidden');
    }

    function confirmHapus(id) {
        Swal.fire({
            title: 'Hapus relasi ini?',
            text: "Indikator ini tidak akan lagi menjadi pendukung tipe tersebut.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('idHapus').value = id;
                document.getElementById('formHapus').submit();
            }
        });
    }
</script>
