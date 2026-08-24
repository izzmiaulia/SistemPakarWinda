<?php
role_wajib('pakar');
csrf_wajib();

$kepribadianModel = new Kepribadian($db); // $db berasal dari index.php
$aturanModel      = new Aturan($db);

// Inisialisasi Flash Message helper
if (!function_exists('setFlash')) {
    function setFlash($type, $msg) {
        $_SESSION['flash'] = ['type' => $type, 'message' => $msg];
    }
}

// Tangani aksi CRUD
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['tambah'])) {
        if ($kepribadianModel->cekKodeAda($_POST['kode'])) {
            setFlash('error', 'Gagal: Kode hipotesis sudah digunakan.');
        } elseif ($kepribadianModel->cekTipeAda($_POST['tipe'])) {
            setFlash('error', 'Gagal: Tipe tersebut sudah terdaftar. Setiap tipe hanya boleh ada satu.');
        } else {
            $kepribadianModel->tambahKepribadian(
                $_POST['kode'], $_POST['kode_rule'], $_POST['tipe'],
                $_POST['nama'], $_POST['deskripsi'], $_POST['rekomendasi']
            );
            setFlash('success', 'Tipe kepribadian berhasil ditambahkan.');
        }
        header("Location: index.php?page=kepribadian"); exit;
    } elseif (isset($_POST['ubah'])) {
        $id = $_POST['id_kepribadian'];
        if ($kepribadianModel->cekKodeAda($_POST['kode'], $id)) {
            setFlash('error', 'Gagal: Kode hipotesis sudah digunakan data lain.');
        } elseif ($kepribadianModel->cekTipeAda($_POST['tipe'], $id)) {
            setFlash('error', 'Gagal: Tipe tersebut sudah dipakai data lain.');
        } else {
            $kepribadianModel->ubahKepribadian(
                $id, $_POST['kode'], $_POST['kode_rule'], $_POST['tipe'],
                $_POST['nama'], $_POST['deskripsi'], $_POST['rekomendasi']
            );
            setFlash('success', 'Tipe kepribadian berhasil diubah.');
        }
        header("Location: index.php?page=kepribadian"); exit;
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus'])) {
    $kepribadianModel->hapusKepribadian((int) $_POST['hapus']);
    setFlash('success', 'Tipe kepribadian berhasil dihapus.');
    header("Location: index.php?page=kepribadian"); exit;
}

// Pencarian & paginasi
$keyword  = isset($_GET['q']) ? trim($_GET['q']) : '';
$page_num = isset($_GET['p']) ? (int) $_GET['p'] : 1;
if ($page_num < 1) $page_num = 1;
$limit  = 10;
$offset = ($page_num - 1) * $limit;

$totalData   = $kepribadianModel->countKepribadian($keyword);
$totalPages  = ceil($totalData / $limit);
$dataKepribadian = $kepribadianModel->getKepribadianPaginated($keyword, $limit, $offset);

// Penanda rekomendasi yang belum diisi guru BK
$belumDiisi = function ($teks) {
    return $teks === null || trim($teks) === '' || str_starts_with(trim($teks), '[BELUM DIISI]');
};

$warnaTipe = [
    'D' => 'bg-red-100 text-red-700 border-red-200',
    'I' => 'bg-amber-100 text-amber-700 border-amber-200',
    'S' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
    'C' => 'bg-blue-100 text-blue-700 border-blue-200',
];
?>

<div class="mb-6 flex flex-col md:flex-row md:justify-between md:items-end gap-4">
    <div>
        <h2 class="text-2xl font-bold text-slate-800">Tipe Kepribadian (Hipotesis)</h2>
        <p class="text-slate-500 mt-1 text-sm">Empat tipe kepribadian DISC beserta deskripsi dan rekomendasi layanan BK-nya.</p>
    </div>
    <div class="flex">
        <button onclick="document.getElementById('modalTambah').classList.remove('hidden')" class="bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 rounded-xl font-medium flex items-center transition-colors shadow-sm">
            <i data-lucide="plus" class="w-4 h-4 mr-2"></i> Tambah Tipe
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

<?php
// Peringatan bila masih ada rekomendasi yang belum diisi
$jumlahBelum = 0;
foreach ($kepribadianModel->getAllKepribadian() as $k) {
    if ($belumDiisi($k['rekomendasi'])) $jumlahBelum++;
}
?>
<?php if($jumlahBelum > 0): ?>
<div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-6 flex items-start gap-3">
    <i data-lucide="alert-circle" class="w-5 h-5 text-amber-500 shrink-0 mt-0.5"></i>
    <div class="text-sm">
        <p class="font-bold text-amber-800"><?= $jumlahBelum ?> tipe belum memiliki rekomendasi layanan BK</p>
        <p class="text-amber-700 mt-0.5">Rekomendasi inilah yang ditampilkan sebagai tindak lanjut pada halaman hasil konsultasi. Isi melalui tombol ubah pada masing-masing tipe.</p>
    </div>
</div>
<?php endif; ?>

<!-- Filter & Search -->
<div class="card p-4 mb-6">
    <form method="GET" action="index.php" class="flex flex-col md:flex-row gap-4">
        <input type="hidden" name="page" value="kepribadian">
        <div class="flex-1">
            <div class="relative">
                <i data-lucide="search" class="w-5 h-5 absolute left-3 top-2.5 text-slate-400"></i>
                <input type="text" name="q" value="<?= htmlspecialchars($keyword) ?>" placeholder="Cari nama tipe, kode, atau huruf tipe..." class="w-full pl-10 pr-4 py-2 border border-slate-200 rounded-xl focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none transition-all">
            </div>
        </div>
        <button type="submit" class="bg-slate-800 text-white px-6 py-2 rounded-xl font-medium hover:bg-slate-900 transition-colors shadow-sm">
            Cari
        </button>
        <?php if(!empty($keyword)): ?>
        <a href="index.php?page=kepribadian" class="px-6 py-2 text-slate-500 font-medium hover:text-slate-800 transition-colors flex items-center">
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
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider w-20">Kode</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider w-20">Rule</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider w-56">Tipe</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Deskripsi &amp; Rekomendasi</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right w-24">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach($dataKepribadian as $row):
                    $jmlIndikator = count($aturanModel->getAturanByKepribadian($row['id_kepribadian']));
                    $cls = $warnaTipe[$row['tipe']] ?? 'bg-slate-100 text-slate-700 border-slate-200';
                ?>
                <tr class="hover:bg-slate-50/50 transition-colors">
                    <td class="px-6 py-4 text-sm align-top">
                        <span class="bg-orange-100 text-orange-700 px-2 py-1 rounded-md text-xs font-bold border border-orange-200"><?= htmlspecialchars($row['kode']) ?></span>
                    </td>
                    <td class="px-6 py-4 text-sm align-top">
                        <span class="bg-slate-100 text-slate-600 px-2 py-1 rounded-md text-xs font-bold border border-slate-200"><?= htmlspecialchars($row['kode_rule']) ?></span>
                    </td>
                    <td class="px-6 py-4 align-top">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold border <?= $cls ?>">
                            <span class="font-extrabold"><?= htmlspecialchars($row['tipe']) ?></span>
                            <?= htmlspecialchars($row['nama']) ?>
                        </span>
                        <p class="text-[11px] text-slate-400 mt-1.5"><?= $jmlIndikator ?> indikator pendukung</p>
                    </td>
                    <td class="px-6 py-4 text-sm text-slate-600 leading-relaxed align-top">
                        <p><?= nl2br(htmlspecialchars($row['deskripsi'])) ?></p>
                        <div class="mt-2 pt-2 border-t border-slate-100">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Rekomendasi Layanan BK</p>
                            <?php if($belumDiisi($row['rekomendasi'])): ?>
                                <span class="inline-flex items-center gap-1 text-xs font-semibold text-amber-700 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded">
                                    <i data-lucide="alert-circle" class="w-3 h-3"></i> Belum diisi
                                </span>
                            <?php else: ?>
                                <p class="text-xs text-slate-600"><?= nl2br(htmlspecialchars($row['rekomendasi'])) ?></p>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-right align-top">
                        <button onclick='editKepribadian(<?= json_encode([
                            "id"          => $row["id_kepribadian"],
                            "kode"        => $row["kode"],
                            "kode_rule"   => $row["kode_rule"],
                            "tipe"        => $row["tipe"],
                            "nama"        => $row["nama"],
                            "deskripsi"   => $row["deskripsi"],
                            "rekomendasi" => $belumDiisi($row["rekomendasi"]) ? "" : $row["rekomendasi"],
                        ], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)' class="text-blue-600 hover:text-blue-800 p-1.5 rounded-md hover:bg-blue-50 transition-colors" title="Ubah Data">
                            <i data-lucide="edit-3" class="w-4 h-4"></i>
                        </button>
                        <button onclick="confirmHapus(<?= $row['id_kepribadian'] ?>)" class="text-red-600 hover:text-red-800 p-1.5 rounded-md hover:bg-red-50 transition-colors ml-1 inline-block" title="Hapus Data">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>

                <?php if(count($dataKepribadian) == 0): ?>
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center">
                        <div class="flex flex-col items-center justify-center text-slate-400">
                            <i data-lucide="users" class="w-12 h-12 mb-3 opacity-20"></i>
                            <p class="text-slate-500 font-medium">Tidak ada tipe kepribadian ditemukan.</p>
                        </div>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
<?php if($totalPages > 1): ?>
<div class="flex justify-between items-center mb-8">
    <p class="text-sm text-slate-500 font-medium">Menampilkan <?= count($dataKepribadian) ?> dari total <?= $totalData ?> data.</p>
    <div class="flex space-x-1">
        <?php for($i=1; $i<=$totalPages; $i++):
            $active = $i == $page_num ? 'bg-brand-600 text-white border-brand-600 shadow-sm' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50';
            $url = "index.php?page=kepribadian&p=$i&q=".urlencode($keyword);
        ?>
            <a href="<?= $url ?>" class="w-10 h-10 flex items-center justify-center rounded-xl border font-semibold text-sm transition-colors <?= $active ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
</div>
<?php endif; ?>

<!-- Modal Tambah -->
<div id="modalTambah" class="fixed inset-0 bg-slate-900/50 hidden flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl w-full max-w-lg p-6 shadow-xl max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-slate-800">Tambah Tipe Kepribadian</h3>
            <button type="button" onclick="document.getElementById('modalTambah').classList.add('hidden')" class="text-slate-400 hover:text-slate-600"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>
        <form method="POST">
            <?= csrf_field() ?>
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Kode Hipotesis</label>
                        <input type="text" name="kode" value="<?= $kepribadianModel->getNextKode() ?>" readonly class="w-full border border-slate-300 rounded-xl px-4 py-2 outline-none bg-slate-100 text-slate-500 cursor-not-allowed">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Kode Rule</label>
                        <input type="text" name="kode_rule" value="<?= $kepribadianModel->getNextKodeRule() ?>" readonly class="w-full border border-slate-300 rounded-xl px-4 py-2 outline-none bg-slate-100 text-slate-500 cursor-not-allowed">
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Huruf Tipe</label>
                        <select name="tipe" required class="w-full border border-slate-300 rounded-xl px-3 py-2 outline-none focus:border-brand-500 bg-white">
                            <option value="D">D</option>
                            <option value="I">I</option>
                            <option value="S">S</option>
                            <option value="C">C</option>
                        </select>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Nama Tipe</label>
                        <input type="text" name="nama" required placeholder="Dominance" class="w-full border border-slate-300 rounded-xl px-4 py-2 outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Deskripsi</label>
                    <textarea name="deskripsi" rows="3" required class="w-full border border-slate-300 rounded-xl px-4 py-2 outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Rekomendasi Layanan BK</label>
                    <textarea name="rekomendasi" rows="4" class="w-full border border-slate-300 rounded-xl px-4 py-2 outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500" placeholder="Tindak lanjut yang disarankan bagi guru BK..."></textarea>
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
<div id="modalEdit" class="fixed inset-0 bg-slate-900/50 hidden flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl w-full max-w-lg p-6 shadow-xl max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-slate-800">Ubah Tipe Kepribadian</h3>
            <button type="button" onclick="document.getElementById('modalEdit').classList.add('hidden')" class="text-slate-400 hover:text-slate-600"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>
        <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="id_kepribadian" id="edit_id">
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Kode Hipotesis</label>
                        <input type="text" name="kode" id="edit_kode" readonly class="w-full border border-slate-300 rounded-xl px-4 py-2 outline-none bg-slate-100 text-slate-500 cursor-not-allowed">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Kode Rule</label>
                        <input type="text" name="kode_rule" id="edit_kode_rule" readonly class="w-full border border-slate-300 rounded-xl px-4 py-2 outline-none bg-slate-100 text-slate-500 cursor-not-allowed">
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Huruf Tipe</label>
                        <select name="tipe" id="edit_tipe" required class="w-full border border-slate-300 rounded-xl px-3 py-2 outline-none focus:border-brand-500 bg-white">
                            <option value="D">D</option>
                            <option value="I">I</option>
                            <option value="S">S</option>
                            <option value="C">C</option>
                        </select>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Nama Tipe</label>
                        <input type="text" name="nama" id="edit_nama" required class="w-full border border-slate-300 rounded-xl px-4 py-2 outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Deskripsi</label>
                    <textarea name="deskripsi" id="edit_deskripsi" rows="3" required class="w-full border border-slate-300 rounded-xl px-4 py-2 outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Rekomendasi Layanan BK</label>
                    <textarea name="rekomendasi" id="edit_rekomendasi" rows="5" class="w-full border border-slate-300 rounded-xl px-4 py-2 outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500" placeholder="Tindak lanjut yang disarankan bagi guru BK..."></textarea>
                    <p class="text-xs text-slate-500 mt-1">Teks ini muncul sebagai tindak lanjut pada halaman hasil konsultasi.</p>
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
<form id="formHapus" method="POST" action="index.php?page=kepribadian" class="hidden">
    <?= csrf_field() ?>
    <input type="hidden" name="hapus" id="idHapus">
</form>

<script>
    document.getElementById('page-title').innerText = 'Tipe Kepribadian';

    function editKepribadian(data) {
        document.getElementById('edit_id').value          = data.id;
        document.getElementById('edit_kode').value        = data.kode;
        document.getElementById('edit_kode_rule').value   = data.kode_rule;
        document.getElementById('edit_tipe').value        = data.tipe;
        document.getElementById('edit_nama').value        = data.nama;
        document.getElementById('edit_deskripsi').value   = data.deskripsi || '';
        document.getElementById('edit_rekomendasi').value = data.rekomendasi || '';
        document.getElementById('modalEdit').classList.remove('hidden');
    }

    function confirmHapus(id) {
        Swal.fire({
            title: 'Yakin hapus tipe ini?',
            text: "Seluruh aturan yang merujuk ke tipe ini ikut terhapus.",
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
