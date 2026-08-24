<?php
csrf_wajib();

$konsultasiModel = new Konsultasi($db);

if (!function_exists('setFlash')) {
    function setFlash($type, $msg) {
        $_SESSION['flash'] = ['type' => $type, 'message' => $msg];
    }
}

// ─── Aksi penghapusan (selalu lewat POST + token) ───────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['hapus'])) {
        $konsultasiModel->hapusKonsultasi((int) $_POST['hapus']);
        setFlash('success', 'Riwayat konsultasi berhasil dihapus.');
        header('Location: index.php?page=riwayat'); exit;
    }

    if (isset($_POST['action'], $_POST['ids']) && $_POST['action'] === 'bulk_delete' && is_array($_POST['ids'])) {
        $ids = array_map('intval', $_POST['ids']);
        $konsultasiModel->hapusMassalKonsultasi($ids);
        setFlash('success', count($ids) . ' riwayat konsultasi berhasil dihapus.');
        header('Location: index.php?page=riwayat'); exit;
    }
}

// ─── Pencarian & paginasi ───────────────────────────────────────────────────
$keyword  = isset($_GET['q']) ? trim($_GET['q']) : '';
$page_num = isset($_GET['p']) ? (int) $_GET['p'] : 1;
if ($page_num < 1) $page_num = 1;
$limit  = 10;
$offset = ($page_num - 1) * $limit;

$totalData   = $konsultasiModel->countRiwayat($keyword);
$totalPages  = (int) ceil($totalData / $limit);
$dataRiwayat = $konsultasiModel->getRiwayatPaginated($keyword, $limit, $offset);

$warnaTipe = [
    'D' => 'bg-red-100 text-red-700 border-red-200',
    'I' => 'bg-amber-100 text-amber-700 border-amber-200',
    'S' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
    'C' => 'bg-blue-100 text-blue-700 border-blue-200',
];
?>

<div class="mb-6 flex flex-col md:flex-row md:justify-between md:items-end gap-4">
    <div>
        <h2 class="text-2xl font-bold text-slate-800">Riwayat Konsultasi</h2>
        <p class="text-slate-500 mt-1 text-sm">Rekam jejak hasil identifikasi tipe kepribadian siswa.</p>
    </div>
    <a href="index.php?page=konsultasi" class="bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 rounded-xl font-medium flex items-center transition-colors shadow-sm w-fit">
        <i data-lucide="plus" class="w-4 h-4 mr-2"></i> Konsultasi Baru
    </a>
</div>

<!-- Flash -->
<?php if(isset($_SESSION['flash'])): ?>
    <?php $flash = $_SESSION['flash']; $icon = $flash['type'] == 'success' ? 'success' : 'error'; ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: '<?= $icon ?>',
                title: '<?= $flash['type'] == 'success' ? 'Berhasil!' : 'Gagal!' ?>',
                text: <?= json_encode($flash['message']) ?>,
                confirmButtonColor: '#16a34a'
            });
        });
    </script>
    <?php unset($_SESSION['flash']); ?>
<?php endif; ?>

<!-- Legenda tipe -->
<div class="flex flex-wrap gap-2 mb-6">
    <?php foreach(['D' => 'Dominance', 'I' => 'Influence', 'S' => 'Steadiness', 'C' => 'Compliance'] as $t => $n): ?>
    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold border shadow-sm <?= $warnaTipe[$t] ?>">
        <span class="font-extrabold"><?= $t ?></span> <?= $n ?>
    </span>
    <?php endforeach; ?>
</div>

<!-- Pencarian -->
<div class="card p-4 mb-6">
    <form method="GET" action="index.php" class="flex flex-col md:flex-row gap-4">
        <input type="hidden" name="page" value="riwayat">
        <div class="flex-1">
            <div class="relative">
                <i data-lucide="search" class="w-5 h-5 absolute left-3 top-2.5 text-slate-400"></i>
                <input type="text" name="q" value="<?= htmlspecialchars($keyword) ?>" placeholder="Cari nama siswa, kelas, NIS, atau tipe kepribadian..." class="w-full pl-10 pr-4 py-2 border border-slate-200 rounded-xl focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none transition-all">
            </div>
        </div>
        <button type="submit" class="bg-slate-800 text-white px-6 py-2 rounded-xl font-medium hover:bg-slate-900 transition-colors shadow-sm">Cari</button>
        <?php if(!empty($keyword)): ?>
        <a href="index.php?page=riwayat" class="px-6 py-2 text-slate-500 font-medium hover:text-slate-800 transition-colors flex items-center">Reset</a>
        <?php endif; ?>
    </form>
</div>

<!-- Aksi massal -->
<div id="barBulk" class="hidden bg-red-50 border border-red-200 rounded-xl p-3 mb-4 flex items-center justify-between">
    <p class="text-sm text-red-800 font-semibold"><span id="checkedCount">0</span> riwayat terpilih</p>
    <button type="button" onclick="confirmBulkDelete()" class="bg-red-600 hover:bg-red-700 text-white px-4 py-1.5 rounded-lg text-xs font-bold flex items-center gap-1.5">
        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Hapus Terpilih
    </button>
</div>

<form id="bulkDeleteForm" method="POST" action="index.php?page=riwayat">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="bulk_delete">
    <div class="card overflow-hidden mb-6">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="px-6 py-4 w-12 text-center">
                            <input type="checkbox" id="selectAll" class="w-4 h-4 rounded text-brand-600 focus:ring-brand-500 border-slate-300">
                        </th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Tanggal</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Siswa</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Kecenderungan Tipe</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">Keyakinan</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach($dataRiwayat as $row):
                        $cls = $warnaTipe[$row['tipe']] ?? 'bg-slate-100 text-slate-700 border-slate-200';
                    ?>
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-4 text-center">
                            <input type="checkbox" name="ids[]" value="<?= $row['id_konsultasi'] ?>" class="row-checkbox w-4 h-4 rounded text-brand-600 focus:ring-brand-500 border-slate-300">
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-500">
                            <div class="flex items-center">
                                <i data-lucide="calendar" class="w-4 h-4 mr-2 text-slate-400"></i>
                                <?= date('d M Y, H:i', strtotime($row['tanggal'])) ?>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-semibold text-slate-800"><?= htmlspecialchars($row['nama_siswa']) ?></div>
                            <div class="text-xs text-slate-500"><?= htmlspecialchars($row['nis']) ?> &middot; <?= htmlspecialchars($row['kelas']) ?></div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold border <?= $cls ?>">
                                <span class="font-extrabold"><?= htmlspecialchars($row['tipe']) ?></span> <?= htmlspecialchars($row['nama_kepribadian']) ?>
                            </span>
                            <p class="text-[10px] text-slate-400 mt-1"><?= htmlspecialchars($row['kode']) ?></p>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="text-sm font-extrabold text-slate-700"><?= $row['nilai_persentase'] ?>%</span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-end gap-1">
                                <a href="index.php?page=hasil&id=<?= $row['id_konsultasi'] ?>" class="text-slate-400 hover:text-brand-600 p-1.5 rounded-md hover:bg-brand-50 transition-colors" title="Lihat Hasil Lengkap">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </a>
                                <a href="download_perhitungan.php?id=<?= $row['id_konsultasi'] ?>" target="_blank" class="text-slate-400 hover:text-emerald-600 p-1.5 rounded-md hover:bg-emerald-50 transition-colors" title="Unduh Perhitungan">
                                    <i data-lucide="file-spreadsheet" class="w-4 h-4"></i>
                                </a>
                                <button type="button" onclick="confirmHapus(<?= $row['id_konsultasi'] ?>)" class="text-slate-400 hover:text-red-600 p-1.5 rounded-md hover:bg-red-50 transition-colors" title="Hapus Riwayat">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>

                    <?php if(count($dataRiwayat) == 0): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center justify-center text-slate-400">
                                <i data-lucide="history" class="w-12 h-12 mb-3 opacity-20"></i>
                                <p class="text-slate-500 font-medium">Belum ada riwayat konsultasi.</p>
                                <a href="index.php?page=konsultasi" class="text-brand-600 hover:text-brand-700 text-sm font-semibold mt-2">Mulai konsultasi pertama</a>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</form>

<!-- Form hapus satuan -->
<form id="formHapus" method="POST" action="index.php?page=riwayat" class="hidden">
    <?= csrf_field() ?>
    <input type="hidden" name="hapus" id="idHapus">
</form>

<!-- Paginasi -->
<?php if($totalPages > 1): ?>
<div class="flex justify-between items-center mb-8">
    <p class="text-sm text-slate-500 font-medium">Menampilkan <?= count($dataRiwayat) ?> dari total <?= $totalData ?> data.</p>
    <div class="flex space-x-1">
        <?php for($i=1; $i<=$totalPages; $i++):
            $active = $i == $page_num ? 'bg-brand-600 text-white border-brand-600 shadow-sm' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50';
        ?>
            <a href="index.php?page=riwayat&p=<?= $i ?>&q=<?= urlencode($keyword) ?>" class="w-10 h-10 flex items-center justify-center rounded-xl border font-semibold text-sm transition-colors <?= $active ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
</div>
<?php endif; ?>

<script>
    document.getElementById('page-title').innerText = 'Riwayat Konsultasi';

    document.addEventListener('DOMContentLoaded', function() {
        const selectAll  = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('.row-checkbox');
        const barBulk    = document.getElementById('barBulk');
        const jumlah     = document.getElementById('checkedCount');

        function perbarui() {
            const terpilih = document.querySelectorAll('.row-checkbox:checked').length;
            jumlah.innerText = terpilih;
            barBulk.classList.toggle('hidden', terpilih === 0);
            if (selectAll) {
                selectAll.checked = terpilih > 0 && terpilih === checkboxes.length;
                selectAll.indeterminate = terpilih > 0 && terpilih < checkboxes.length;
            }
        }

        if (selectAll) {
            selectAll.addEventListener('change', function() {
                checkboxes.forEach(cb => cb.checked = selectAll.checked);
                perbarui();
            });
        }
        checkboxes.forEach(cb => cb.addEventListener('change', perbarui));
        perbarui();
    });

    function confirmHapus(id) {
        Swal.fire({
            title: 'Hapus riwayat ini?',
            text: 'Data konsultasi beserta rincian perhitungannya akan hilang permanen.',
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

    function confirmBulkDelete() {
        const terpilih = document.querySelectorAll('.row-checkbox:checked').length;
        Swal.fire({
            title: `Hapus ${terpilih} riwayat?`,
            text: 'Seluruh data terpilih beserta rincian perhitungannya akan hilang permanen.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, hapus semua!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) document.getElementById('bulkDeleteForm').submit();
        });
    }
</script>
