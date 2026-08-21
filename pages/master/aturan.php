<?php
$masalahModel = new Masalah($db);
$aturanModel = new Aturan($db);
$gejalaModel = new Gejala($db);

// Inisialisasi Flash Message helper
if (!function_exists('setFlash')) {
    function setFlash($type, $msg) {
        $_SESSION['flash'] = ['type' => $type, 'message' => $msg];
    }
}

// Tangani aksi CRUD
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['tambah'])) {
        $id_masalah = $_POST['id_masalah'];
        $id_gejala = $_POST['id_gejala'];
        $belief = $_POST['belief'];
        
        // Pengecekan Relasi Ganda
        if ($aturanModel->cekRelasiAda($id_masalah, $id_gejala)) {
            setFlash('error', 'Gagal: Gejala tersebut sudah direlasikan ke masalah ini sebelumnya. Sistem menolak duplikasi untuk menjaga validitas Dempster-Shafer.');
        } else {
            $aturanModel->tambahAturan($id_masalah, $id_gejala, $belief);
            setFlash('success', 'Relasi aturan baru berhasil ditambahkan.');
        }
        header("Location: index.php?page=aturan"); exit;
    }
}
if (isset($_GET['hapus'])) {
    $aturanModel->hapusAturan($_GET['hapus']);
    setFlash('success', 'Relasi aturan berhasil dihapus.');
    header("Location: index.php?page=aturan"); exit;
}

$dataMasalah = $masalahModel->getAllMasalah();
$dataGejala = $gejalaModel->getAllGejala();
?>

<div class="mb-6 flex flex-col md:flex-row md:justify-between md:items-end gap-4">
    <div>
        <h2 class="text-2xl font-bold text-slate-800">Basis Pengetahuan (Aturan)</h2>
        <p class="text-slate-500 mt-1 text-sm">Relasi antara masalah perilaku dengan gejala beserta nilai kepastian pakar (Belief).</p>
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
        $icon = $flash['type'] == 'success' ? 'success' : 'error';
        $title = $flash['type'] == 'success' ? 'Berhasil!' : 'Gagal!';
    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: '<?= $icon ?>',
                title: '<?= $title ?>',
                text: '<?= addslashes($flash['message']) ?>',
                confirmButtonColor: '#16a34a'
            });
        });
    </script>
    <?php unset($_SESSION['flash']); ?>
<?php endif; ?>

<!-- Daftar Accordion Masalah -->
<div class="space-y-4 mb-10">
    <?php foreach($dataMasalah as $index => $m): 
        $id_masalah = $m['id_masalah'];
        $dataAturan = $aturanModel->getAturanByMasalah($id_masalah);
        // Buka accordion pertama secara default agar terlihat interaksinya
        $isOpen = ($index === 0) ? 'true' : 'false';
    ?>
    <div class="card overflow-hidden" data-accordion>
        <button type="button" class="w-full text-left bg-white hover:bg-slate-50 px-6 py-4 flex justify-between items-center focus:outline-none transition-colors" onclick="toggleAccordion('acc-<?= $id_masalah ?>', this)">
            <h3 class="font-bold text-slate-800 flex items-center">
                <span class="bg-orange-100 text-orange-700 px-2.5 py-1 rounded-md text-xs mr-3 border border-orange-200"><?= $m['kode_masalah'] ?></span>
                <?= htmlspecialchars($m['nama_masalah']) ?>
                <span class="ml-3 px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-500"><?= count($dataAturan) ?> Gejala</span>
            </h3>
            <div class="text-slate-400 transform transition-transform duration-200 <?= $isOpen === 'true' ? 'rotate-180' : '' ?>">
                <i data-lucide="chevron-down" class="w-5 h-5"></i>
            </div>
        </button>
        
        <div id="acc-<?= $id_masalah ?>" class="transition-all duration-300 ease-in-out border-t border-slate-100 <?= $isOpen === 'true' ? '' : 'hidden' ?>">
            <div class="p-0">
                <?php if(count($dataAturan) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-100">
                                <th class="px-6 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider w-24">Kode Gejala</th>
                                <th class="px-6 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Nama Gejala</th>
                                <th class="px-6 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider w-32 text-center">Nilai Pakar</th>
                                <th class="px-6 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider text-right w-24">Aksi</th>
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
                                        <?= $a['nilai_belief'] ?>
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-sm text-right">
                                    <button onclick="confirmHapus(<?= $a['id_aturan'] ?>)" class="text-red-500 hover:text-red-700 p-1.5 rounded-md hover:bg-red-50 transition-colors inline-block" title="Hapus Relasi">
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
                    <p class="text-slate-500 text-sm font-medium">Belum ada gejala yang direlasikan ke masalah ini.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Modal Tambah Aturan -->
<div id="modalTambah" class="fixed inset-0 bg-slate-900/50 hidden flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl w-full max-w-md p-6 shadow-xl">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-slate-800">Tambah Relasi Aturan</h3>
            <button onclick="document.getElementById('modalTambah').classList.add('hidden')" class="text-slate-400 hover:text-slate-600"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>
        <form method="POST">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Pilih Masalah / Hipotesis</label>
                    <select name="id_masalah" required class="w-full border border-slate-300 rounded-xl px-3 py-2 outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 bg-white">
                        <option value="">-- Pilih Masalah --</option>
                        <?php foreach($dataMasalah as $m): ?>
                            <option value="<?= $m['id_masalah'] ?>">[<?= $m['kode_masalah'] ?>] <?= htmlspecialchars($m['nama_masalah']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Pilih Gejala</label>
                    <select name="id_gejala" required class="w-full border border-slate-300 rounded-xl px-3 py-2 outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 bg-white">
                        <option value="">-- Pilih Gejala --</option>
                        <?php foreach($dataGejala as $g): ?>
                            <option value="<?= $g['id_gejala'] ?>">[<?= $g['kode_gejala'] ?>] <?= htmlspecialchars($g['nama_gejala']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Nilai Kepastian (Belief: 0.1 - 1.0)</label>
                    <input type="number" step="0.1" min="0.1" max="1.0" name="belief" required class="w-full border border-slate-300 rounded-xl px-4 py-2 outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500" placeholder="0.8">
                    <p class="text-xs text-slate-500 mt-1">Nilai bobot pakar (0.1 = Sangat Rendah, 1.0 = Sangat Yakin).</p>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('modalTambah').classList.add('hidden')" class="bg-white border border-slate-200 text-slate-700 px-4 py-2 rounded-xl font-medium hover:bg-slate-50">Batal</button>
                <button type="submit" name="tambah" class="bg-brand-600 text-white px-4 py-2 rounded-xl font-medium hover:bg-brand-700 shadow-sm">Simpan Relasi</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.getElementById('page-title').innerText = 'Basis Pengetahuan';

    // Script untuk Accordion
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

    // Script untuk Konfirmasi Hapus dengan SweetAlert2
    function confirmHapus(id) {
        Swal.fire({
            title: 'Hapus relasi ini?',
            text: "Gejala ini tidak akan lagi menjadi indikator untuk masalah tersebut.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'index.php?page=aturan&hapus=' + id;
            }
        });
    }
</script>
