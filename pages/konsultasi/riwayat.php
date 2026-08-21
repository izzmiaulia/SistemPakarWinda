<?php
$konsultasiModel = new Konsultasi($db);

// Handle hapus riwayat
if (!function_exists('setFlash')) {
    function setFlash($type, $msg) {
        $_SESSION['flash'] = ['type' => $type, 'message' => $msg];
    }
}
if (isset($_GET['hapus'])) {
    $konsultasiModel->hapusKonsultasi($_GET['hapus']);
    setFlash('success', 'Riwayat konsultasi berhasil dihapus.');
    header("Location: index.php?page=riwayat"); exit;
}

// Handle bulk delete
if (isset($_POST['action']) && $_POST['action'] === 'bulk_delete' && isset($_POST['ids']) && is_array($_POST['ids'])) {
    $ids = array_map('intval', $_POST['ids']);
    $konsultasiModel->hapusMassalKonsultasi($ids);
    setFlash('success', count($ids) . ' data riwayat konsultasi berhasil dihapus.');
    header("Location: index.php?page=riwayat"); exit;
}

// Handle detail (via AJAX / GET)
$detailData = null;
if (isset($_GET['detail'])) {
    $detailData = $konsultasiModel->getDetailKonsultasi((int)$_GET['detail']);
}

// Pagination & Search
$keyword = isset($_GET['q']) ? trim($_GET['q']) : '';
$page_num = isset($_GET['p']) ? (int)$_GET['p'] : 1;
if ($page_num < 1) $page_num = 1;
$limit = 10;
$offset = ($page_num - 1) * $limit;

$totalData = $konsultasiModel->countRiwayat($keyword);
$totalPages = ceil($totalData / $limit);
$dataRiwayat = $konsultasiModel->getRiwayatPaginated($keyword, $limit, $offset);
?>

<div class="mb-6 flex flex-col md:flex-row md:justify-between md:items-end gap-4">
    <div>
        <h2 class="text-2xl font-bold text-slate-800">Riwayat Analisis Siswa</h2>
        <p class="text-slate-500 mt-1 text-sm">Rekam jejak hasil diagnosis masalah perilaku siswa.</p>
    </div>
    <a href="index.php?page=konsultasi" class="bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 rounded-xl font-medium flex items-center transition-colors shadow-sm w-fit">
        <i data-lucide="plus" class="w-4 h-4 mr-2"></i> Konsultasi Baru
    </a>
</div>

<!-- SweetAlert2 Flash -->
<?php if(isset($_SESSION['flash'])): ?>
    <?php $flash = $_SESSION['flash']; $icon = $flash['type'] == 'success' ? 'success' : 'error'; ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({ icon: '<?= $icon ?>', title: '<?= $flash['type'] == 'success' ? 'Berhasil!' : 'Gagal!' ?>', text: '<?= addslashes($flash['message']) ?>', confirmButtonColor: '#16a34a' });
        });
    </script>
    <?php unset($_SESSION['flash']); ?>
<?php endif; ?>

<!-- Legend -->
<div class="flex flex-wrap gap-2 mb-6">
    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-red-50 text-red-700 border border-red-100 shadow-sm">
        <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span> Tinggi (≥ 80%)
    </span>
    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-orange-50 text-orange-700 border border-orange-100 shadow-sm">
        <span class="w-2 h-2 rounded-full bg-orange-500"></span> Sedang (50–79%)
    </span>
    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-100 shadow-sm">
        <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Rendah (< 50%)
    </span>
</div>

<!-- Search & Bulk Delete Actions -->
<div class="card p-4 mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <form method="GET" action="index.php" class="flex flex-1 flex-col md:flex-row gap-4">
        <input type="hidden" name="page" value="riwayat">
        <div class="flex-1">
            <div class="relative">
                <i data-lucide="search" class="w-5 h-5 absolute left-3 top-2.5 text-slate-400"></i>
                <input type="text" name="q" value="<?= htmlspecialchars($keyword) ?>" placeholder="Cari nama siswa, kelas, NIS, atau nama masalah..." class="w-full pl-10 pr-4 py-2 border border-slate-200 rounded-xl focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none transition-all">
            </div>
        </div>
        <button type="submit" class="bg-slate-800 text-white px-6 py-2 rounded-xl font-medium hover:bg-slate-900 transition-colors shadow-sm">Cari</button>
        <?php if(!empty($keyword)): ?>
        <a href="index.php?page=riwayat" class="px-6 py-2 text-slate-500 font-medium hover:text-slate-800 transition-colors flex items-center">Reset</a>
        <?php endif; ?>
    </form>

    <div class="shrink-0 flex items-center">
        <button type="button" id="btnBulkDelete" disabled onclick="confirmBulkDelete()" class="bg-red-50 text-red-400 border border-red-200 cursor-not-allowed px-4 py-2 rounded-xl font-semibold text-sm transition-all duration-200 flex items-center gap-2">
            <i data-lucide="trash-2" class="w-4 h-4"></i> Hapus Terpilih (<span id="checkedCount">0</span>)
        </button>
    </div>
</div>

<form id="bulkDeleteForm" method="POST" action="index.php?page=riwayat">
    <input type="hidden" name="action" value="bulk_delete">
    <div class="card overflow-hidden mb-6">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider w-12 text-center">
                            <input type="checkbox" id="selectAll" class="w-4 h-4 rounded text-brand-600 focus:ring-brand-500 border-slate-300">
                        </th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Tanggal</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Siswa</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Diagnosis Masalah</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">Keyakinan</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach($dataRiwayat as $row): ?>
                    <?php
                        if($row['nilai_persentase'] >= 80) { $badge = 'bg-red-100 text-red-700 border-red-200'; }
                        elseif($row['nilai_persentase'] >= 50) { $badge = 'bg-orange-100 text-orange-700 border-orange-200'; }
                        else { $badge = 'bg-emerald-100 text-emerald-700 border-emerald-200'; }
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
                            <div class="text-xs text-slate-500"><?= $row['nis'] ?> · <?= $row['kelas'] ?></div>
                        </td>
                        <td class="px-6 py-4 text-sm font-medium text-slate-700">
                            <span class="text-orange-600 font-bold mr-1"><?= $row['kode_masalah'] ?></span> <?= htmlspecialchars($row['nama_masalah']) ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-center">
                            <span class="inline-block px-3 py-1 rounded-full text-xs font-bold border <?= $badge ?>">
                                <?= $row['nilai_persentase'] ?>%
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-end gap-1">
                                <button type="button" onclick="lihatDetail(<?= $row['id_konsultasi'] ?>)" class="text-slate-400 hover:text-brand-600 p-1.5 rounded-md hover:bg-brand-50 transition-colors flex items-center justify-center" title="Lihat Detail">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </button>
                                <a href="download_excel.php?id=<?= $row['id_konsultasi'] ?>" target="_blank" class="text-slate-400 hover:text-emerald-600 p-1.5 rounded-md hover:bg-emerald-50 transition-colors flex items-center justify-center" title="Unduh Excel Perhitungan">
                                    <i data-lucide="file-spreadsheet" class="w-4 h-4"></i>
                                </a>
                                <button type="button" onclick="confirmHapus(<?= $row['id_konsultasi'] ?>)" class="text-slate-400 hover:text-red-600 p-1.5 rounded-md hover:bg-red-50 transition-colors flex items-center justify-center" title="Hapus Riwayat">
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
                                <p class="text-slate-500 font-medium">Belum ada riwayat analisis ditemukan.</p>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</form>
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
<?php if($totalPages > 1): ?>
<div class="flex justify-between items-center mb-8">
    <p class="text-sm text-slate-500 font-medium">Menampilkan <?= count($dataRiwayat) ?> dari total <?= $totalData ?> data.</p>
    <div class="flex space-x-1">
        <?php for($i=1; $i<=$totalPages; $i++): ?>
            <?php $active = $i == $page_num ? 'bg-brand-600 text-white border-brand-600 shadow-sm' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'; ?>
            <a href="index.php?page=riwayat&p=<?= $i ?>&q=<?= urlencode($keyword) ?>" class="w-10 h-10 flex items-center justify-center rounded-xl border font-semibold text-sm transition-colors <?= $active ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
</div>
<?php endif; ?>

<!-- Modal Detail Konsultasi -->
<div id="modalDetail" class="fixed inset-0 bg-slate-900/60 hidden flex items-center justify-center z-50 transition-opacity duration-300 opacity-0">
    <div class="bg-white rounded-2xl w-full max-w-lg p-6 shadow-2xl mx-4 max-h-[90vh] overflow-y-auto transform scale-95 transition-all duration-300">
        <div class="flex justify-between items-center mb-5 border-b pb-4">
            <h3 class="text-lg font-bold text-slate-800">Detail Hasil Konsultasi</h3>
            <button onclick="tutupModalDetail()" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <div id="modalDetailContent">
            <div class="text-center py-8"><i data-lucide="loader" class="w-8 h-8 text-brand-500 mx-auto animate-spin"></i></div>
        </div>
    </div>
</div>

<script>
    document.getElementById('page-title').innerText = 'Riwayat Analisis';

    // Bulk Delete Logic & Row Highlight
    document.addEventListener('DOMContentLoaded', function() {
        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('.row-checkbox');
        const btnBulkDelete = document.getElementById('btnBulkDelete');
        const checkedCount = document.getElementById('checkedCount');

        function updateBulkDeleteState() {
            const checkedBoxes = document.querySelectorAll('.row-checkbox:checked');
            const count = checkedBoxes.length;
            
            if (checkedCount) {
                checkedCount.innerText = count;
            }

            // Toggle background highlight pada baris yang dicentang
            checkboxes.forEach(cb => {
                const tr = cb.closest('tr');
                if (cb.checked) {
                    tr.classList.add('bg-brand-50/25');
                } else {
                    tr.classList.remove('bg-brand-50/25');
                }
            });
            
            if (btnBulkDelete) {
                if (count > 0) {
                    btnBulkDelete.disabled = false;
                    btnBulkDelete.classList.remove('bg-red-50', 'text-red-400', 'border-red-200', 'cursor-not-allowed');
                    btnBulkDelete.classList.add('bg-red-600', 'hover:bg-red-700', 'text-white', 'shadow-md', 'shadow-red-500/10');
                } else {
                    btnBulkDelete.disabled = true;
                    btnBulkDelete.classList.remove('bg-red-600', 'hover:bg-red-700', 'text-white', 'shadow-md', 'shadow-red-500/10');
                    btnBulkDelete.classList.add('bg-red-50', 'text-red-400', 'border-red-200', 'cursor-not-allowed');
                }
            }
        }

        if (selectAll) {
            selectAll.addEventListener('change', function() {
                checkboxes.forEach(cb => {
                    cb.checked = selectAll.checked;
                });
                updateBulkDeleteState();
            });
        }

        checkboxes.forEach(cb => {
            cb.addEventListener('change', function() {
                const allChecked = Array.from(checkboxes).every(c => c.checked);
                if (selectAll) {
                    selectAll.checked = allChecked;
                }
                updateBulkDeleteState();
            });
        });
        
        // Panggil saat awal load untuk menjaga konsistensi state
        updateBulkDeleteState();
    });

    function confirmBulkDelete() {
        const count = document.querySelectorAll('.row-checkbox:checked').length;
        
        Swal.fire({
            title: 'Hapus riwayat terpilih?',
            text: "Anda akan menghapus " + count + " data riwayat secara permanen.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, hapus semua!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('bulkDeleteForm').submit();
            }
        });
    }

    function confirmHapus(id) {
        Swal.fire({
            title: 'Hapus riwayat ini?',
            text: "Data konsultasi ini akan dihapus permanen.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'index.php?page=riwayat&hapus=' + id;
            }
        });
    }

    function lihatDetail(id) {
        document.getElementById('modalDetail').classList.remove('hidden');
        document.getElementById('modalDetailContent').innerHTML = '<div class="text-center py-8"><div class="inline-block w-8 h-8 border-4 border-brand-500 border-t-transparent rounded-full animate-spin"></div></div>';

        fetch('index.php?page=riwayat&detail=' + id)
            .then(r => r.text())
            .then(html => {
                // Parse the JSON dari PHP
                fetch('index.php?page=api_detail&id=' + id)
                    .catch(() => null);
            });

        // Kita pakai pendekatan sederhana: buka URL dengan parameter detail
        window.location.href = 'index.php?page=riwayat&detail=' + id + '&q=<?= urlencode($keyword) ?>&p=<?= $page_num ?>';
    }

    function tutupModalDetail() {
        const modal = document.getElementById('modalDetail');
        modal.classList.remove('opacity-100');
        modal.querySelector('.transform').classList.remove('scale-100');
        modal.querySelector('.transform').classList.add('scale-95');
        setTimeout(() => {
            modal.classList.add('hidden');
            // Bersihkan parameter detail dari URL agar tidak terulang
            const url = new URL(window.location.href);
            url.searchParams.delete('detail');
            window.history.replaceState({}, '', url);
        }, 300);
    }
</script>

<?php if($detailData): ?>
<!-- Auto-buka modal jika ada parameter detail -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php
    $persen = $detailData['nilai_persentase'];
    if ($persen >= 80) { $dBadge = 'bg-red-100 text-red-700'; $dLevel = 'Tinggi'; }
    elseif ($persen >= 50) { $dBadge = 'bg-orange-100 text-orange-700'; $dLevel = 'Sedang'; }
    else { $dBadge = 'bg-emerald-100 text-emerald-700'; $dLevel = 'Rendah'; }

    // Hitung rincian DS manual
    $hasilDS = MesinInferensi::hitungDenganDetail($detailData['gejala']);
    $langkahDS = $hasilDS['langkah'];

    // Ambil seluruh gejala yang terkait dengan masalah (Goal) ini untuk visualisasi Backward Chaining
    $aturanModel = new Aturan($db);
    $semuaGejalaMasalah = $aturanModel->getAturanByMasalah($detailData['id_masalah']);
    $gejalaTerpilihKodes = array_column($detailData['gejala'], 'kode_gejala');
    ?>
    const content = `
        <div class="space-y-4">
            <div class="flex items-center justify-between bg-slate-50 rounded-xl p-4">
                <div>
                    <p class="text-xs font-semibold text-slate-400 uppercase">Siswa</p>
                    <p class="font-semibold text-slate-800"><?= htmlspecialchars($detailData['nama_siswa']) ?></p>
                    <p class="text-xs text-slate-500">NIS: <?= $detailData['nis'] ?> · Kelas: <?= $detailData['kelas'] ?></p>
                </div>
                <div class="text-right">
                    <p class="text-xs font-semibold text-slate-400 uppercase">Tanggal</p>
                    <p class="text-sm text-slate-600"><?= date('d M Y, H:i', strtotime($detailData['tanggal'])) ?></p>
                </div>
            </div>
            <div class="bg-slate-50 rounded-xl p-4">
                <p class="text-xs font-semibold text-slate-400 uppercase mb-1">Diagnosis</p>
                <p class="font-bold text-slate-800"><?= htmlspecialchars($detailData['nama_masalah']) ?></p>
                <div class="flex items-center gap-3 mt-2">
                    <span class="text-2xl font-extrabold <?= $dLevel === 'Tinggi' ? 'text-red-600' : ($dLevel === 'Sedang' ? 'text-orange-600' : 'text-emerald-600') ?>"><?= $persen ?>%</span>
                    <span class="px-2 py-0.5 rounded-full text-xs font-bold <?= $dBadge ?>">Tingkat <?= $dLevel ?></span>
                </div>
            </div>

            <!-- TAHAP 1: Collapsible Backward Chaining -->
            <div class="border border-slate-200 rounded-xl overflow-hidden">
                <button type="button" onclick="document.getElementById('detailBC').classList.toggle('hidden'); this.querySelector('.arrow-icon-bc').classList.toggle('rotate-180')" class="w-full bg-slate-50 hover:bg-slate-100 px-4 py-3 flex justify-between items-center transition-colors focus:outline-none">
                    <span class="text-sm font-semibold text-slate-700 flex items-center gap-2">
                        <i data-lucide="git-branch" class="w-4 h-4 text-slate-500"></i> Tahap I: Backward Chaining (Pelacakan)
                    </span>
                    <i data-lucide="chevron-down" class="w-4 h-4 text-slate-500 transform transition-transform duration-200 arrow-icon-bc"></i>
                </button>
                <div id="detailBC" class="hidden p-4 space-y-3 border-t border-slate-200 bg-slate-50/50 max-h-[250px] overflow-y-auto">
                    <div class="bg-white border border-slate-200 rounded-lg p-3 text-[10px] text-slate-500 leading-relaxed">
                        Sistem menetapkan masalah <strong><?= htmlspecialchars($detailData['nama_masalah']) ?></strong> sebagai Goal, lalu melacak mundur untuk mencocokkan gejala (Premis) berikut:
                    </div>
                    
                    <?php if (!empty($detailData['log_proses'])): ?>
                    <div class="bg-slate-900 rounded-lg p-2.5 font-mono text-[10px] text-slate-200 space-y-1.5 overflow-x-auto leading-relaxed border border-slate-800">
                        <p class="text-brand-400 font-bold mb-1">// Log Proses Pelacakan & Backtracking</p>
                        <?php 
                        $logLines = explode("\n", $detailData['log_proses']);
                        foreach ($logLines as $line): 
                        ?>
                            <p class="border-l-2 border-brand-500 pl-2 py-0.5"><?= $line ?></p>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <div class="space-y-2">
                        <?php foreach ($semuaGejalaMasalah as $g): 
                            $isTerpenuhi = in_array($g['kode_gejala'], $gejalaTerpilihKodes);
                            $statusCls = $isTerpenuhi ? 'bg-emerald-100 text-emerald-800 border-emerald-200' : 'bg-slate-100 text-slate-400 border-slate-200';
                            $statusText = $isTerpenuhi ? '✓ Terpenuhi' : '✗ Tidak';
                        ?>
                        <div class="flex items-start justify-between bg-white p-2.5 rounded-lg border border-slate-150 text-[11px] <?= !$isTerpenuhi ? 'opacity-60' : '' ?>">
                            <div class="flex-1 pr-2">
                                <span class="font-bold text-blue-600 mr-1"><?= $g['kode_gejala'] ?></span>
                                <span class="text-slate-700 font-medium"><?= htmlspecialchars($g['nama_gejala']) ?></span>
                            </div>
                            <div class="text-right shrink-0">
                                <span class="inline-block px-2 py-0.5 rounded-full text-[9px] font-bold border <?= $statusCls ?>">
                                    <?= $statusText ?>
                                </span>
                                <?php if($isTerpenuhi): ?>
                                <span class="block text-[9px] text-slate-400 mt-0.5">m({H}) = <?= $g['nilai_belief'] ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- TAHAP 2: Collapsible Dempster-Shafer -->
            <div class="border border-slate-200 rounded-xl overflow-hidden">
                <button type="button" onclick="document.getElementById('detailDS').classList.toggle('hidden'); this.querySelector('.arrow-icon-ds').classList.toggle('rotate-180')" class="w-full bg-slate-50 hover:bg-slate-100 px-4 py-3 flex justify-between items-center transition-colors focus:outline-none">
                    <span class="text-sm font-semibold text-slate-700 flex items-center gap-2">
                        <i data-lucide="calculator" class="w-4 h-4 text-slate-500"></i> Tahap II: Dempster-Shafer (Kalkulasi)
                    </span>
                    <i data-lucide="chevron-down" class="w-4 h-4 text-slate-500 transform transition-transform duration-200 arrow-icon-ds"></i>
                </button>
                <div id="detailDS" class="hidden p-4 space-y-3 border-t border-slate-200 bg-slate-50/50 max-h-[300px] overflow-y-auto">
                    <?php foreach ($langkahDS as $idx => $lk): ?>
                    <div class="border border-slate-200 bg-white rounded-lg p-3 text-[11px] leading-relaxed">
                        <p class="font-bold text-slate-800 text-xs"><?= htmlspecialchars($lk['judul']) ?></p>
                        <p class="text-slate-500 text-[10px] mt-0.5"><?= htmlspecialchars($lk['uraian']) ?></p>
                        
                        <div class="bg-slate-900 rounded-lg p-2.5 font-mono text-[10px] text-slate-200 mt-2 overflow-x-auto">
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
                        
                        <div class="flex gap-2 mt-2">
                            <div class="flex-1 bg-slate-50 p-1.5 rounded text-center border border-slate-100">
                                <span class="text-[9px] text-slate-400 block uppercase font-semibold">m({H})</span>
                                <span class="font-bold text-slate-700"><?= $lk['m_H'] ?></span>
                            </div>
                            <div class="flex-1 bg-slate-50 p-1.5 rounded text-center border border-slate-100">
                                <span class="text-[9px] text-slate-400 block uppercase font-semibold">m({Θ})</span>
                                <span class="font-bold text-slate-500"><?= $lk['m_Theta'] ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="bg-amber-50 border border-amber-100 rounded-xl p-4">
                <p class="text-xs font-semibold text-amber-700 uppercase mb-1">💡 Solusi Penanganan</p>
                <p class="text-sm text-slate-600 leading-relaxed"><?= nl2br(htmlspecialchars($detailData['solusi'])) ?></p>
            </div>

            <!-- Tombol Cetak & Unduh Excel di Dalam Modal -->
            <div class="flex gap-3 mt-4 print:hidden">
                <button type="button" onclick="window.print()" class="px-5 py-2.5 bg-slate-800 text-white font-medium rounded-xl hover:bg-slate-900 transition-colors shadow-sm flex items-center gap-2 text-xs">
                    <i data-lucide="printer" class="w-4 h-4"></i> Cetak Laporan
                </button>
                <a href="download_excel.php?id=<?= $detailData['id_konsultasi'] ?>" target="_blank" class="px-5 py-2.5 bg-emerald-600 text-white font-medium rounded-xl hover:bg-emerald-700 transition-colors shadow-sm flex items-center gap-2 text-xs">
                    <i data-lucide="file-spreadsheet" class="w-4 h-4"></i> Unduh Excel
                </a>
            </div>
        </div>
    `;
    document.getElementById('modalDetailContent').innerHTML = content;
    const modal = document.getElementById('modalDetail');
    modal.classList.remove('hidden');
    setTimeout(() => {
        modal.classList.add('opacity-100');
        modal.querySelector('.transform').classList.add('scale-100');
        modal.querySelector('.transform').classList.remove('scale-95');
    }, 50);
    lucide.createIcons();
});
</script>
<?php endif; ?>

<!-- CSS Khusus Cetak PDF Detail Riwayat -->
<style>
    @media print {
        /* Sembunyikan sidebar, header, topbar, dan elemen riwayat utama */
        aside#sidebar,
        header,
        main > div.p-8 > *:not(#modalDetail),
        .print\:hidden {
            display: none !important;
            visibility: hidden !important;
        }

        /* Tampilkan modal detail secara penuh */
        #modalDetail {
            position: absolute !important;
            left: 0 !important;
            top: 0 !important;
            width: 100% !important;
            height: auto !important;
            display: block !important;
            background: white !important;
            overflow: visible !important;
            box-shadow: none !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        
        /* Hilangkan modal backdrop shadow dan styling fixed */
        #modalDetail > div {
            box-shadow: none !important;
            max-height: none !important;
            width: 100% !important;
            max-width: none !important;
            margin: 0 !important;
            padding: 0 !important;
            border: none !important;
        }

        /* Paksa laci detail (BC & DS) agar terbuka penuh saat dicetak */
        #detailBC, #detailDS {
            display: block !important;
            max-height: none !important;
            overflow: visible !important;
        }

        /* Reset layout body & wrapper */
        body {
            background: white !important;
            overflow: visible !important;
            height: auto !important;
            display: block !important;
        }

        main {
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
            height: auto !important;
            overflow: visible !important;
            display: block !important;
            flex: unset !important;
        }

        /* Pertahankan warna background terminal/badge saat cetak */
        .bg-slate-900 {
            background: #1e293b !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            color-adjust: exact !important;
        }

        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            color-adjust: exact !important;
        }

        @page {
            size: A4 landscape;
            margin: 15mm;
        }
    }
</style>
