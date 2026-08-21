<?php
$masalahModel = new Masalah($db); // $db comes from index.php

// Inisialisasi Flash Message helper
if (!function_exists('setFlash')) {
    function setFlash($type, $msg) {
        $_SESSION['flash'] = ['type' => $type, 'message' => $msg];
    }
}

// Tangani aksi CRUD
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['tambah'])) {
        if ($masalahModel->cekKodeAda($_POST['kode'])) {
            setFlash('error', 'Gagal: Kode masalah sudah digunakan.');
        } else {
            $masalahModel->tambahMasalah($_POST['kode'], $_POST['nama'], $_POST['solusi']);
            setFlash('success', 'Data masalah berhasil ditambahkan.');
        }
        header("Location: index.php?page=masalah"); exit;
    } elseif (isset($_POST['ubah'])) {
        if ($masalahModel->cekKodeAda($_POST['kode'], $_POST['id_masalah'])) {
            setFlash('error', 'Gagal: Kode masalah sudah digunakan oleh data lain.');
        } else {
            $masalahModel->ubahMasalah($_POST['id_masalah'], $_POST['kode'], $_POST['nama'], $_POST['solusi']);
            setFlash('success', 'Data masalah berhasil diubah.');
        }
        header("Location: index.php?page=masalah"); exit;
    }
}
if (isset($_GET['hapus'])) {
    $masalahModel->hapusMasalah($_GET['hapus']);
    setFlash('success', 'Data masalah berhasil dihapus.');
    header("Location: index.php?page=masalah"); exit;
}

// Persiapkan Pencarian & Pagination
$keyword = isset($_GET['q']) ? trim($_GET['q']) : '';
$page_num = isset($_GET['p']) ? (int)$_GET['p'] : 1;
if ($page_num < 1) $page_num = 1;
$limit = 10; // Jumlah data per halaman
$offset = ($page_num - 1) * $limit;

$totalData = $masalahModel->countMasalah($keyword);
$totalPages = ceil($totalData / $limit);
$dataMasalah = $masalahModel->getMasalahPaginated($keyword, $limit, $offset);
?>

<div class="mb-6 flex flex-col md:flex-row md:justify-between md:items-end gap-4">
    <div>
        <h2 class="text-2xl font-bold text-slate-800">Data Masalah / Diagnosis</h2>
        <p class="text-slate-500 mt-1 text-sm">Kelola daftar masalah psikologis/perilaku beserta solusinya.</p>
    </div>
    <div class="flex">
        <button onclick="document.getElementById('modalTambah').classList.remove('hidden')" class="bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 rounded-xl font-medium flex items-center transition-colors shadow-sm">
            <i data-lucide="plus" class="w-4 h-4 mr-2"></i> Tambah Masalah
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

<!-- Filter & Search -->
<div class="card p-4 mb-6">
    <form method="GET" action="index.php" class="flex flex-col md:flex-row gap-4">
        <input type="hidden" name="page" value="masalah">
        <div class="flex-1">
            <div class="relative">
                <i data-lucide="search" class="w-5 h-5 absolute left-3 top-2.5 text-slate-400"></i>
                <input type="text" name="q" value="<?= htmlspecialchars($keyword) ?>" placeholder="Cari nama masalah atau kode..." class="w-full pl-10 pr-4 py-2 border border-slate-200 rounded-xl focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none transition-all">
            </div>
        </div>
        <button type="submit" class="bg-slate-800 text-white px-6 py-2 rounded-xl font-medium hover:bg-slate-900 transition-colors shadow-sm">
            Cari
        </button>
        <?php if(!empty($keyword)): ?>
        <a href="index.php?page=masalah" class="px-6 py-2 text-slate-500 font-medium hover:text-slate-800 transition-colors flex items-center">
            Reset
        </a>
        <?php endif; ?>
    </form>
</div>

<div class="card overflow-hidden mb-6">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200">
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider w-16">Kode</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider w-1/4">Nama Masalah</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Solusi / Penanganan</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right w-24">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach($dataMasalah as $row): ?>
                <tr class="hover:bg-slate-50/50 transition-colors">
                    <td class="px-6 py-4 text-sm align-top">
                        <span class="bg-orange-100 text-orange-700 px-2 py-1 rounded-md text-xs font-bold border border-orange-200"><?= $row['kode_masalah'] ?></span>
                    </td>
                    <td class="px-6 py-4 text-sm font-semibold text-slate-800 align-top"><?= htmlspecialchars($row['nama_masalah']) ?></td>
                    <td class="px-6 py-4 text-sm text-slate-600 leading-relaxed align-top">
                        <!-- Menggunakan nl2br agar enter pada teks tertampil rapi -->
                        <?= nl2br(htmlspecialchars($row['solusi'])) ?>
                    </td>
                    <td class="px-6 py-4 text-sm text-right align-top">
                        <button onclick="editMasalah(<?= $row['id_masalah'] ?>, '<?= $row['kode_masalah'] ?>', <?= htmlspecialchars(json_encode($row['nama_masalah']), ENT_QUOTES) ?>, <?= htmlspecialchars(json_encode($row['solusi']), ENT_QUOTES) ?>)" class="text-blue-600 hover:text-blue-800 p-1.5 rounded-md hover:bg-blue-50 transition-colors" title="Ubah Data">
                            <i data-lucide="edit-3" class="w-4 h-4"></i>
                        </button>
                        <button onclick="confirmHapus(<?= $row['id_masalah'] ?>)" class="text-red-600 hover:text-red-800 p-1.5 rounded-md hover:bg-red-50 transition-colors ml-1 inline-block" title="Hapus Data">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                
                <?php if(count($dataMasalah) == 0): ?>
                <tr>
                    <td colspan="4" class="px-6 py-12 text-center">
                        <div class="flex flex-col items-center justify-center text-slate-400">
                            <i data-lucide="alert-triangle" class="w-12 h-12 mb-3 opacity-20"></i>
                            <p class="text-slate-500 font-medium">Tidak ada data masalah ditemukan.</p>
                        </div>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination Controls -->
<?php if($totalPages > 1): ?>
<div class="flex justify-between items-center mb-8">
    <p class="text-sm text-slate-500 font-medium">Menampilkan <?= count($dataMasalah) ?> dari total <?= $totalData ?> data.</p>
    <div class="flex space-x-1">
        <?php for($i=1; $i<=$totalPages; $i++): ?>
            <?php 
                $active = $i == $page_num ? 'bg-brand-600 text-white border-brand-600 shadow-sm' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50';
                $url = "index.php?page=masalah&p=$i&q=".urlencode($keyword);
            ?>
            <a href="<?= $url ?>" class="w-10 h-10 flex items-center justify-center rounded-xl border font-semibold text-sm transition-colors <?= $active ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>
    </div>
</div>
<?php endif; ?>

<!-- Modal Tambah -->
<div id="modalTambah" class="fixed inset-0 bg-slate-900/50 hidden flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl w-full max-w-md p-6 shadow-xl">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-slate-800">Tambah Data Masalah</h3>
            <button type="button" onclick="document.getElementById('modalTambah').classList.add('hidden')" class="text-slate-400 hover:text-slate-600"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>
        <form method="POST">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Kode Masalah (Otomatis)</label>
                    <input type="text" name="kode" value="<?= $masalahModel->getNextKode() ?>" readonly class="w-full border border-slate-300 rounded-xl px-4 py-2 outline-none bg-slate-100 text-slate-500 cursor-not-allowed">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Nama Masalah</label>
                    <input type="text" name="nama" required class="w-full border border-slate-300 rounded-xl px-4 py-2 outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Solusi / Penanganan</label>
                    <textarea name="solusi" rows="4" required class="w-full border border-slate-300 rounded-xl px-4 py-2 outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500"></textarea>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('modalTambah').classList.add('hidden')" class="bg-white border border-slate-200 text-slate-700 px-4 py-2 rounded-xl font-medium hover:bg-slate-50">Batal</button>
                <button type="submit" name="tambah" class="bg-brand-600 text-white px-4 py-2 rounded-xl font-medium hover:bg-brand-700 shadow-sm">Simpan Data</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit -->
<div id="modalEdit" class="fixed inset-0 bg-slate-900/50 hidden flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl w-full max-w-md p-6 shadow-xl">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-slate-800">Ubah Data Masalah</h3>
            <button type="button" onclick="document.getElementById('modalEdit').classList.add('hidden')" class="text-slate-400 hover:text-slate-600"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>
        <form method="POST">
            <input type="hidden" name="id_masalah" id="edit_id">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Kode Masalah</label>
                    <input type="text" name="kode" id="edit_kode" readonly class="w-full border border-slate-300 rounded-xl px-4 py-2 outline-none bg-slate-100 text-slate-500 cursor-not-allowed">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Nama Masalah</label>
                    <input type="text" name="nama" id="edit_nama" required class="w-full border border-slate-300 rounded-xl px-4 py-2 outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Solusi / Penanganan</label>
                    <textarea name="solusi" id="edit_solusi" rows="4" required class="w-full border border-slate-300 rounded-xl px-4 py-2 outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500"></textarea>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('modalEdit').classList.add('hidden')" class="bg-white border border-slate-200 text-slate-700 px-4 py-2 rounded-xl font-medium hover:bg-slate-50">Batal</button>
                <button type="submit" name="ubah" class="bg-brand-600 text-white px-4 py-2 rounded-xl font-medium hover:bg-brand-700 shadow-sm">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.getElementById('page-title').innerText = 'Data Masalah';
    
    function editMasalah(id, kode, nama, solusi) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_kode').value = kode;
        document.getElementById('edit_nama').value = nama;
        document.getElementById('edit_solusi').value = solusi;
        document.getElementById('modalEdit').classList.remove('hidden');
    }

    function confirmHapus(id) {
        Swal.fire({
            title: 'Yakin hapus data ini?',
            text: "Data masalah yang dihapus tidak bisa dikembalikan!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'index.php?page=masalah&hapus=' + id;
            }
        });
    }
</script>
