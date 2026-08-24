<?php
csrf_wajib();

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

$siswaModel = new Siswa($db); // $db comes from index.php

// Inisialisasi Flash Message helper
function setFlash($type, $msg) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $msg];
}

// 1. Export Excel
if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    $allSiswa = $siswaModel->getAllSiswa(); // seluruh data, tanpa batas semu
    
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setCellValue('A1', 'No');
    $sheet->setCellValue('B1', 'NIS');
    $sheet->setCellValue('C1', 'Nama Siswa');
    $sheet->setCellValue('D1', 'Kelas');
    $sheet->setCellValue('E1', 'Jenis Kelamin');
    
    // Header Style
    $sheet->getStyle('A1:E1')->getFont()->setBold(true);

    $rowNum = 2;
    $no = 1;
    foreach ($allSiswa as $row) {
        $sheet->setCellValue('A' . $rowNum, $no++);
        $sheet->setCellValueExplicit('B' . $rowNum, $row['nis'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValue('C' . $rowNum, $row['nama_siswa']);
        $sheet->setCellValue('D' . $rowNum, $row['kelas']);
        $sheet->setCellValue('E' . $rowNum, $row['jenis_kelamin']);
        $rowNum++;
    }

    foreach (range('A', 'E') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="Data_Siswa_' . date('Y-m-d') . '.xlsx"');
    header('Cache-Control: max-age=0');
    
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

// 2. Download Template Excel
if (isset($_GET['download']) && $_GET['download'] == 'template') {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    $sheet->setCellValue('A1', 'NIS');
    $sheet->setCellValue('B1', 'Nama Lengkap');
    $sheet->setCellValue('C1', 'Kelas');
    $sheet->setCellValue('D1', 'Jenis Kelamin (L/P)');
    
    $sheet->getStyle('A1:D1')->getFont()->setBold(true);
    $sheet->getStyle('A1:D1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFD3D3D3');

    // Sample data
    $sheet->setCellValueExplicit('A2', '10101', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
    $sheet->setCellValue('B2', 'Budi Santoso');
    $sheet->setCellValue('C2', 'X-A');
    $sheet->setCellValue('D2', 'L');

    foreach (range('A', 'D') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="Template_Import_Siswa.xlsx"');
    header('Cache-Control: max-age=0');
    
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

// Tangani aksi CRUD & Import
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['tambah'])) {
        if ($siswaModel->cekNisAda($_POST['nis'])) {
            setFlash('error', 'Gagal: NIS sudah terdaftar untuk siswa lain.');
        } else {
            $siswaModel->tambahSiswa($_POST['nis'], $_POST['nama'], $_POST['kelas'], $_POST['jk']);
            setFlash('success', 'Data siswa berhasil ditambahkan.');
        }
        header("Location: index.php?page=siswa"); exit;
    } elseif (isset($_POST['ubah'])) {
        if ($siswaModel->cekNisAda($_POST['nis'], $_POST['id_siswa'])) {
            setFlash('error', 'Gagal: NIS sudah terdaftar untuk siswa lain.');
        } else {
            $siswaModel->ubahSiswa($_POST['id_siswa'], $_POST['nis'], $_POST['nama'], $_POST['kelas'], $_POST['jk'], $_SESSION['id_admin'] ?? null);
            setFlash('success', 'Data siswa berhasil diubah.');
        }
        header("Location: index.php?page=siswa"); exit;
    } elseif (isset($_POST['import'])) {
        if (isset($_FILES['excel_file'])) {
            $hasilValidasi = berkas_valid_xlsx($_FILES['excel_file']);
            if (!$hasilValidasi['ok']) {
                setFlash('error', $hasilValidasi['error']);
                header("Location: index.php?page=siswa"); exit;
            }

            try {
                $file = $_FILES['excel_file']['tmp_name'];
                $spreadsheet = IOFactory::load($file);
                $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

                $sukses = 0;
                $gagal = 0;

                $first = true;
                foreach ($sheetData as $row) {
                    if ($first) { $first = false; continue; }

                    // Format: A = NIS, B = Nama, C = Kelas, D = JK
                    $nis = trim((string)$row['A']);
                    $nama = trim((string)$row['B']);
                    $kelas = trim((string)$row['C']);
                    $jk = strtoupper(trim((string)$row['D'])) == 'P' ? 'P' : 'L';

                    if (!empty($nis) && !empty($nama)) {
                        if (!$siswaModel->cekNisAda($nis)) {
                            $siswaModel->tambahSiswa($nis, $nama, $kelas, $jk, $_SESSION['id_admin'] ?? null);
                            $sukses++;
                        } else {
                            $gagal++;
                        }
                    }
                }
                setFlash('success', "Import selesai: $sukses data berhasil ditambahkan, $gagal data dilewati (duplikat/tidak valid).");
            } catch (Exception $e) {
                setFlash('error', 'Gagal membaca file Excel. Pastikan format file benar (.xlsx).');
            }
        } else {
            setFlash('error', 'Gagal mengunggah file.');
        }
        header("Location: index.php?page=siswa"); exit;
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus'])) {
    $siswaModel->hapusSiswa($_POST['hapus']);
    setFlash('success', 'Data siswa berhasil dihapus.');
    header("Location: index.php?page=siswa"); exit;
}

// Persiapkan Pencarian & Pagination
$keyword = isset($_GET['q']) ? trim($_GET['q']) : '';
$kelas_filter = isset($_GET['kelas']) ? $_GET['kelas'] : '';
$page_num = isset($_GET['p']) ? (int)$_GET['p'] : 1;
if ($page_num < 1) $page_num = 1;
$limit = 10; // Jumlah data per halaman
$offset = ($page_num - 1) * $limit;

$totalData = $siswaModel->countSiswa($keyword, $kelas_filter);
$totalPages = ceil($totalData / $limit);
$dataSiswa = $siswaModel->getSiswaPaginated($keyword, $kelas_filter, $limit, $offset);

$daftarKelas = $siswaModel->getDaftarKelas();
?>

<div class="mb-6 flex flex-col md:flex-row md:justify-between md:items-end gap-4">
    <div>
        <h2 class="text-2xl font-bold text-slate-800">Data Siswa</h2>
        <p class="text-slate-500 mt-1 text-sm">Kelola data siswa yang akan mengikuti bimbingan dan konseling.</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <button onclick="document.getElementById('modalImport').classList.remove('hidden')" class="bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 px-4 py-2 rounded-xl font-medium flex items-center transition-colors shadow-sm">
            <i data-lucide="upload" class="w-4 h-4 mr-2"></i> Import Excel
        </button>
        <a href="index.php?page=siswa&export=excel" class="bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 px-4 py-2 rounded-xl font-medium flex items-center transition-colors shadow-sm">
            <i data-lucide="download" class="w-4 h-4 mr-2"></i> Export Excel
        </a>
        <button onclick="document.getElementById('modalTambah').classList.remove('hidden')" class="bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 rounded-xl font-medium flex items-center transition-colors shadow-sm">
            <i data-lucide="plus" class="w-4 h-4 mr-2"></i> Tambah Siswa
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
                text: <?= json_encode($flash['message']) ?>,
                confirmButtonColor: '#16a34a'
            });
        });
    </script>
    <?php unset($_SESSION['flash']); ?>
<?php endif; ?>

<!-- Filter & Search -->
<div class="card p-4 mb-6">
    <form method="GET" action="index.php" class="flex flex-col md:flex-row gap-4">
        <input type="hidden" name="page" value="siswa">
        <div class="flex-1">
            <div class="relative">
                <i data-lucide="search" class="w-5 h-5 absolute left-3 top-2.5 text-slate-400"></i>
                <input type="text" name="q" value="<?= htmlspecialchars($keyword) ?>" placeholder="Cari nama atau NIS..." class="w-full pl-10 pr-4 py-2 border border-slate-200 rounded-xl focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none transition-all">
            </div>
        </div>
        <div class="md:w-48 relative">
            <select name="kelas" class="w-full px-4 py-2 border border-slate-200 rounded-xl focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none appearance-none bg-white transition-all">
                <option value="">Semua Kelas</option>
                <?php foreach($daftarKelas as $kls): ?>
                    <option value="<?= htmlspecialchars($kls) ?>" <?= $kelas_filter == $kls ? 'selected' : '' ?>><?= htmlspecialchars($kls) ?></option>
                <?php endforeach; ?>
            </select>
            <i data-lucide="chevron-down" class="w-4 h-4 absolute right-3 top-3 text-slate-400 pointer-events-none"></i>
        </div>
        <button type="submit" class="bg-slate-800 text-white px-6 py-2 rounded-xl font-medium hover:bg-slate-900 transition-colors shadow-sm">
            Terapkan
        </button>
        <?php if(!empty($keyword) || !empty($kelas_filter)): ?>
        <a href="index.php?page=siswa" class="px-6 py-2 text-slate-500 font-medium hover:text-slate-800 transition-colors flex items-center">
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
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">No</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">NIS</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Nama Siswa</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Kelas</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">L/P</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php 
                $no = $offset + 1; 
                foreach($dataSiswa as $row): 
                ?>
                <tr class="hover:bg-slate-50/50 transition-colors">
                    <td class="px-6 py-4 text-sm text-slate-500"><?= $no++ ?></td>
                    <td class="px-6 py-4 text-sm font-semibold text-slate-800"><?= htmlspecialchars($row['nis']) ?></td>
                    <td class="px-6 py-4 text-sm text-slate-700"><?= htmlspecialchars($row['nama_siswa']) ?></td>
                    <td class="px-6 py-4 text-sm text-slate-600">
                        <span class="bg-slate-100 text-slate-700 px-2 py-1 rounded-md text-xs font-medium border border-slate-200"><?= htmlspecialchars($row['kelas']) ?></span>
                    </td>
                    <td class="px-6 py-4 text-sm text-slate-600"><?= $row['jenis_kelamin'] ?></td>
                    <td class="px-6 py-4 text-sm text-right">
                        <button onclick="editSiswa(<?= $row['id_siswa'] ?>, '<?= $row['nis'] ?>', <?= htmlspecialchars(json_encode($row['nama_siswa']), ENT_QUOTES) ?>, '<?= $row['kelas'] ?>', '<?= $row['jenis_kelamin'] ?>')" class="text-blue-600 hover:text-blue-800 p-1.5 rounded-md hover:bg-blue-50 transition-colors" title="Ubah Data">
                            <i data-lucide="edit-3" class="w-4 h-4"></i>
                        </button>
                        <button onclick="confirmHapus(<?= $row['id_siswa'] ?>)" class="text-red-600 hover:text-red-800 p-1.5 rounded-md hover:bg-red-50 transition-colors ml-1 inline-block" title="Hapus Data">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                
                <?php if(count($dataSiswa) == 0): ?>
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center">
                        <div class="flex flex-col items-center justify-center text-slate-400">
                            <i data-lucide="users" class="w-12 h-12 mb-3 opacity-20"></i>
                            <p class="text-slate-500 font-medium">Tidak ada data siswa ditemukan.</p>
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
    <p class="text-sm text-slate-500 font-medium">Menampilkan <?= count($dataSiswa) ?> dari total <?= $totalData ?> data.</p>
    <div class="flex space-x-1">
        <?php for($i=1; $i<=$totalPages; $i++): ?>
            <?php 
                $active = $i == $page_num ? 'bg-brand-600 text-white border-brand-600 shadow-sm' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50';
                $url = "index.php?page=siswa&p=$i&q=".urlencode($keyword)."&kelas=".urlencode($kelas_filter);
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
            <h3 class="text-lg font-bold text-slate-800">Tambah Data Siswa</h3>
            <button type="button" onclick="document.getElementById('modalTambah').classList.add('hidden')" class="text-slate-400 hover:text-slate-600"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>
        <form method="POST">
            <?= csrf_field() ?>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">NIS</label>
                    <input type="text" name="nis" required class="w-full border border-slate-300 rounded-xl px-4 py-2 outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Nama Siswa</label>
                    <input type="text" name="nama" required class="w-full border border-slate-300 rounded-xl px-4 py-2 outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Kelas</label>
                    <input type="text" name="kelas" required class="w-full border border-slate-300 rounded-xl px-4 py-2 outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Jenis Kelamin</label>
                    <div class="relative">
                        <select name="jk" required class="w-full border border-slate-300 rounded-xl px-4 py-2 outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 appearance-none bg-white">
                            <option value="L">Laki-laki</option>
                            <option value="P">Perempuan</option>
                        </select>
                        <i data-lucide="chevron-down" class="w-4 h-4 absolute right-3 top-3 text-slate-400 pointer-events-none"></i>
                    </div>
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
            <h3 class="text-lg font-bold text-slate-800">Ubah Data Siswa</h3>
            <button type="button" onclick="document.getElementById('modalEdit').classList.add('hidden')" class="text-slate-400 hover:text-slate-600"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>
        <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="id_siswa" id="edit_id">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">NIS</label>
                    <input type="text" name="nis" id="edit_nis" required class="w-full border border-slate-300 rounded-xl px-4 py-2 outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Nama Siswa</label>
                    <input type="text" name="nama" id="edit_nama" required class="w-full border border-slate-300 rounded-xl px-4 py-2 outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Kelas</label>
                    <input type="text" name="kelas" id="edit_kelas" required class="w-full border border-slate-300 rounded-xl px-4 py-2 outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Jenis Kelamin</label>
                    <div class="relative">
                        <select name="jk" id="edit_jk" required class="w-full border border-slate-300 rounded-xl px-4 py-2 outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 appearance-none bg-white">
                            <option value="L">Laki-laki</option>
                            <option value="P">Perempuan</option>
                        </select>
                        <i data-lucide="chevron-down" class="w-4 h-4 absolute right-3 top-3 text-slate-400 pointer-events-none"></i>
                    </div>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('modalEdit').classList.add('hidden')" class="bg-white border border-slate-200 text-slate-700 px-4 py-2 rounded-xl font-medium hover:bg-slate-50">Batal</button>
                <button type="submit" name="ubah" class="bg-brand-600 text-white px-4 py-2 rounded-xl font-medium hover:bg-brand-700 shadow-sm">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Import -->
<div id="modalImport" class="fixed inset-0 bg-slate-900/50 hidden flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl w-full max-w-md p-6 shadow-xl">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-slate-800">Import Data Excel</h3>
            <button type="button" onclick="document.getElementById('modalImport').classList.add('hidden')" class="text-slate-400 hover:text-slate-600"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="space-y-4">
                <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 text-sm text-slate-600 mb-2">
                    <p class="font-semibold text-slate-800 mb-2">Instruksi Import:</p>
                    <ol class="list-decimal ml-4 space-y-1">
                        <li>Gunakan file Excel murni berakhiran <strong>.xlsx</strong></li>
                        <li>Pastikan urutan kolom sesuai standar.</li>
                        <li>Baris pertama akan diabaikan (hanya untuk judul).</li>
                    </ol>
                </div>
                <div class="mb-4">
                    <a href="index.php?page=siswa&download=template" class="inline-flex items-center text-sm font-semibold text-brand-600 hover:text-brand-800 transition-colors">
                        <i data-lucide="download-cloud" class="w-4 h-4 mr-1"></i> Download Template Excel
                    </a>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Pilih File (.xlsx)</label>
                    <input type="file" name="excel_file" accept=".xlsx" required class="w-full border border-slate-300 rounded-xl px-3 py-2 outline-none focus:border-brand-500 bg-white">
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('modalImport').classList.add('hidden')" class="bg-white border border-slate-200 text-slate-700 px-4 py-2 rounded-xl font-medium hover:bg-slate-50">Batal</button>
                <button type="submit" name="import" class="bg-slate-800 text-white px-4 py-2 rounded-xl font-medium hover:bg-slate-900 shadow-sm flex items-center">
                    <i data-lucide="upload" class="w-4 h-4 mr-2"></i> Mulai Import
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Form penghapusan (POST + token) -->
<form id="formHapus" method="POST" action="index.php?page=siswa" class="hidden">
    <?= csrf_field() ?>
    <input type="hidden" name="hapus" id="idHapus">
</form>

<script>
    document.getElementById('page-title').innerText = 'Data Siswa';
    
    function editSiswa(id, nis, nama, kelas, jk) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_nis').value = nis;
        document.getElementById('edit_nama').value = nama;
        document.getElementById('edit_kelas').value = kelas;
        document.getElementById('edit_jk').value = jk;
        document.getElementById('modalEdit').classList.remove('hidden');
    }

    function confirmHapus(id) {
        Swal.fire({
            title: 'Yakin hapus data ini?',
            text: "Data yang dihapus tidak bisa dikembalikan!",
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
