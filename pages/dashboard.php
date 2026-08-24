<?php
$konsultasiModel  = new Konsultasi($db);
$kepribadianModel = new Kepribadian($db);

$stats = $konsultasiModel->getStats();

$jml_siswa       = $stats['siswa'];
$jml_kepribadian = $stats['kepribadian'];
$jml_gejala      = $stats['gejala'];
$jml_konsultasi  = $stats['konsultasi'];

// Rata-rata keyakinan hipotesis pemenang
$stmtAvg = $db->query("SELECT AVG(nilai_persentase) as rata_rata FROM hasil_konsultasi");
$rata_rata_keyakinan = round($stmtAvg->fetch()['rata_rata'] ?? 0, 1);

// Distribusi tipe kepribadian dominan hasil konsultasi
$stmtDist = $db->query("
    SELECT k.id_kepribadian, k.kode, k.tipe, k.nama, COUNT(h.id_hasil) AS jumlah
      FROM kepribadian k
      LEFT JOIN hasil_konsultasi h ON h.id_kepribadian = k.id_kepribadian
     GROUP BY k.id_kepribadian, k.kode, k.tipe, k.nama
     ORDER BY k.kode ASC
");
$distribusi = $stmtDist->fetchAll();

$totalTerdiagnosis = 0;
foreach ($distribusi as $d) {
    $totalTerdiagnosis += (int) $d['jumlah'];
}

// Tipe paling sering muncul
$tipe_dominan = 'Belum ada';
$jumlahTertinggi = 0;
foreach ($distribusi as $d) {
    if ((int) $d['jumlah'] > $jumlahTertinggi) {
        $jumlahTertinggi = (int) $d['jumlah'];
        $tipe_dominan    = $d['tipe'] . ' - ' . $d['nama'];
    }
}
if ($jumlahTertinggi === 0) {
    $tipe_dominan = 'Belum ada';
}

// Distribusi tipe per kelas
$stmtKelas = $db->query("
    SELECT s.kelas, k.tipe, COUNT(*) AS jumlah
      FROM hasil_konsultasi h
      JOIN riwayat_konsultasi r ON h.id_konsultasi  = r.id_konsultasi
      JOIN siswa s              ON r.id_siswa       = s.id_siswa
      JOIN kepribadian k        ON h.id_kepribadian = k.id_kepribadian
     GROUP BY s.kelas, k.tipe
     ORDER BY s.kelas ASC, k.tipe ASC
");
$perKelas = [];
foreach ($stmtKelas->fetchAll() as $row) {
    $perKelas[$row['kelas']][$row['tipe']] = (int) $row['jumlah'];
}

// Lima konsultasi terakhir
$stmtTerakhir = $db->query("
    SELECT r.id_konsultasi, r.tanggal, s.nama_siswa, s.kelas, s.nis,
           h.nilai_persentase, k.kode, k.tipe, k.nama AS nama_kepribadian
      FROM riwayat_konsultasi r
      JOIN siswa s            ON r.id_siswa       = s.id_siswa
      JOIN hasil_konsultasi h ON r.id_konsultasi  = h.id_konsultasi
      JOIN kepribadian k      ON h.id_kepribadian = k.id_kepribadian
     ORDER BY r.tanggal DESC
     LIMIT 5
");
$dataTerakhir = $stmtTerakhir->fetchAll();

// Warna konsisten untuk tiap tipe
$warnaTipe = [
    'D' => ['hex' => '#ef4444', 'cls' => 'bg-red-100 text-red-700 border-red-200'],
    'I' => ['hex' => '#f59e0b', 'cls' => 'bg-amber-100 text-amber-700 border-amber-200'],
    'S' => ['hex' => '#10b981', 'cls' => 'bg-emerald-100 text-emerald-700 border-emerald-200'],
    'C' => ['hex' => '#3b82f6', 'cls' => 'bg-blue-100 text-blue-700 border-blue-200'],
];

$chartLabel = [];
$chartData  = [];
$chartWarna = [];
foreach ($distribusi as $d) {
    $chartLabel[] = $d['tipe'] . ' - ' . $d['nama'];
    $chartData[]  = (int) $d['jumlah'];
    $chartWarna[] = $warnaTipe[$d['tipe']]['hex'] ?? '#94a3b8';
}
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="mb-8">
    <h2 class="text-2xl font-bold text-slate-800">Selamat Datang, <?= htmlspecialchars($GLOBALS['admin_profile']['nama_lengkap'] ?? 'Guru BK') ?>!</h2>
    <p class="text-slate-500 mt-1 text-sm">Ringkasan identifikasi tipe kepribadian siswa di <?= htmlspecialchars($GLOBALS['admin_profile']['nama_sekolah'] ?? 'sekolah') ?>.</p>
</div>

<!-- KARTU STATISTIK -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex items-center">
        <div class="w-12 h-12 rounded-xl bg-brand-50 flex items-center justify-center text-brand-600 mr-4 border border-brand-100">
            <i data-lucide="users" class="w-6 h-6"></i>
        </div>
        <div>
            <p class="text-xs font-semibold text-slate-400 uppercase">Total Siswa</p>
            <h3 class="text-2xl font-extrabold text-slate-800 mt-0.5"><?= $jml_siswa ?></h3>
        </div>
    </div>

    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex items-center">
        <div class="w-12 h-12 rounded-xl bg-amber-50 flex items-center justify-center text-amber-600 mr-4 border border-amber-100">
            <i data-lucide="user-square" class="w-6 h-6"></i>
        </div>
        <div class="overflow-hidden">
            <p class="text-xs font-semibold text-slate-400 uppercase">Tipe Terbanyak</p>
            <h3 class="text-sm font-bold text-slate-800 mt-1 truncate" title="<?= htmlspecialchars($tipe_dominan) ?>"><?= htmlspecialchars($tipe_dominan) ?></h3>
        </div>
    </div>

    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex items-center">
        <div class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600 mr-4 border border-blue-100">
            <i data-lucide="activity" class="w-6 h-6"></i>
        </div>
        <div>
            <p class="text-xs font-semibold text-slate-400 uppercase">Rata-rata Keyakinan</p>
            <h3 class="text-2xl font-extrabold text-slate-800 mt-0.5"><?= $rata_rata_keyakinan ?>%</h3>
        </div>
    </div>

    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex items-center">
        <div class="w-12 h-12 rounded-xl bg-purple-50 flex items-center justify-center text-purple-600 mr-4 border border-purple-100">
            <i data-lucide="history" class="w-6 h-6"></i>
        </div>
        <div>
            <p class="text-xs font-semibold text-slate-400 uppercase">Total Konsultasi</p>
            <h3 class="text-2xl font-extrabold text-slate-800 mt-0.5"><?= $jml_konsultasi ?></h3>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">

    <!-- Distribusi tipe kepribadian -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
        <h3 class="text-sm font-bold text-slate-700 mb-4 flex items-center gap-1.5">
            <i data-lucide="pie-chart" class="w-4 h-4 text-brand-600"></i> Distribusi Tipe Kepribadian
        </h3>

        <?php if($totalTerdiagnosis > 0): ?>
        <div class="relative w-full h-48 flex items-center justify-center">
            <canvas id="tipeChart"></canvas>
        </div>
        <?php else: ?>
        <div class="h-48 flex flex-col items-center justify-center text-center">
            <i data-lucide="pie-chart" class="w-10 h-10 text-slate-200 mb-2"></i>
            <p class="text-xs text-slate-400">Belum ada hasil konsultasi</p>
        </div>
        <?php endif; ?>

        <div class="mt-4 border-t pt-3 space-y-2">
            <?php foreach($distribusi as $d):
                $persen = $totalTerdiagnosis > 0 ? round($d['jumlah'] / $totalTerdiagnosis * 100, 1) : 0;
                $cls    = $warnaTipe[$d['tipe']]['cls'] ?? 'bg-slate-100 text-slate-600 border-slate-200';
            ?>
            <div class="flex justify-between items-center">
                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[11px] font-bold border <?= $cls ?>">
                    <span class="font-extrabold"><?= $d['tipe'] ?></span> <?= htmlspecialchars($d['nama']) ?>
                </span>
                <span class="text-xs font-bold text-slate-600"><?= $d['jumlah'] ?> <span class="text-slate-400 font-medium">(<?= $persen ?>%)</span></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Distribusi per kelas -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm lg:col-span-2">
        <h3 class="text-sm font-bold text-slate-700 mb-4 flex items-center gap-1.5">
            <i data-lucide="bar-chart-3" class="w-4 h-4 text-brand-600"></i> Sebaran Tipe per Kelas
        </h3>

        <?php if(count($perKelas) > 0): ?>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100">
                        <th class="px-4 py-2 text-xs font-bold text-slate-500 uppercase">Kelas</th>
                        <?php foreach(['D','I','S','C'] as $t): ?>
                        <th class="px-4 py-2 text-xs font-bold text-slate-500 uppercase text-center w-16"><?= $t ?></th>
                        <?php endforeach; ?>
                        <th class="px-4 py-2 text-xs font-bold text-slate-500 uppercase text-center w-20">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php foreach($perKelas as $kelas => $baris):
                        $totalKelas = array_sum($baris);
                    ?>
                    <tr class="hover:bg-slate-50/60">
                        <td class="px-4 py-2.5 font-semibold text-slate-700"><?= htmlspecialchars($kelas) ?></td>
                        <?php foreach(['D','I','S','C'] as $t):
                            $n = $baris[$t] ?? 0;
                        ?>
                        <td class="px-4 py-2.5 text-center <?= $n > 0 ? 'font-bold text-slate-700' : 'text-slate-300' ?>"><?= $n ?></td>
                        <?php endforeach; ?>
                        <td class="px-4 py-2.5 text-center font-bold text-brand-700"><?= $totalKelas ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="text-[11px] text-slate-400 mt-3">Tabel ini dapat langsung dipakai sebagai bahan pembahasan sebaran kepribadian per rombongan belajar.</p>
        <?php else: ?>
        <div class="h-48 flex flex-col items-center justify-center text-center">
            <i data-lucide="bar-chart-3" class="w-10 h-10 text-slate-200 mb-2"></i>
            <p class="text-sm text-slate-500 font-medium">Belum ada data sebaran</p>
            <p class="text-xs text-slate-400 mt-1">Sebaran muncul setelah konsultasi dijalankan.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Konsultasi terakhir -->
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-8">
    <div class="px-5 py-4 border-b border-slate-100 flex justify-between items-center">
        <h3 class="text-sm font-bold text-slate-700 flex items-center gap-1.5">
            <i data-lucide="clock" class="w-4 h-4 text-brand-600"></i> Konsultasi Terakhir
        </h3>
        <a href="index.php?page=riwayat" class="text-xs font-semibold text-brand-600 hover:text-brand-700">Lihat semua</a>
    </div>

    <?php if(count($dataTerakhir) > 0): ?>
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-sm">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-100">
                    <th class="px-5 py-2.5 text-xs font-bold text-slate-500 uppercase">Siswa</th>
                    <th class="px-5 py-2.5 text-xs font-bold text-slate-500 uppercase w-28">Kelas</th>
                    <th class="px-5 py-2.5 text-xs font-bold text-slate-500 uppercase">Kecenderungan</th>
                    <th class="px-5 py-2.5 text-xs font-bold text-slate-500 uppercase text-center w-28">Keyakinan</th>
                    <th class="px-5 py-2.5 text-xs font-bold text-slate-500 uppercase w-40">Tanggal</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <?php foreach($dataTerakhir as $row):
                    $cls = $warnaTipe[$row['tipe']]['cls'] ?? 'bg-slate-100 text-slate-600 border-slate-200';
                ?>
                <tr class="hover:bg-slate-50/60">
                    <td class="px-5 py-3">
                        <p class="font-semibold text-slate-800"><?= htmlspecialchars($row['nama_siswa']) ?></p>
                        <p class="text-[11px] text-slate-400">NIS <?= htmlspecialchars($row['nis']) ?></p>
                    </td>
                    <td class="px-5 py-3 text-slate-600"><?= htmlspecialchars($row['kelas']) ?></td>
                    <td class="px-5 py-3">
                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[11px] font-bold border <?= $cls ?>">
                            <span class="font-extrabold"><?= $row['tipe'] ?></span> <?= htmlspecialchars($row['nama_kepribadian']) ?>
                        </span>
                    </td>
                    <td class="px-5 py-3 text-center font-bold text-slate-700"><?= $row['nilai_persentase'] ?>%</td>
                    <td class="px-5 py-3 text-xs text-slate-500"><?= date('d M Y, H:i', strtotime($row['tanggal'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="px-5 py-12 text-center">
        <i data-lucide="inbox" class="w-10 h-10 text-slate-200 mb-2 inline-block"></i>
        <p class="text-sm text-slate-500 font-medium">Belum ada konsultasi tercatat</p>
    </div>
    <?php endif; ?>
</div>

<script>
    document.getElementById('page-title').innerText = 'Dashboard';

    <?php if($totalTerdiagnosis > 0): ?>
    new Chart(document.getElementById('tipeChart'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($chartLabel) ?>,
            datasets: [{
                data: <?= json_encode($chartData) ?>,
                backgroundColor: <?= json_encode($chartWarna) ?>,
                borderWidth: 0,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',
            plugins: { legend: { display: false } }
        }
    });
    <?php endif; ?>
</script>
