<?php
$konsultasiModel = new Konsultasi($db);
$stats = $konsultasiModel->getStats();

$jml_siswa = $stats['siswa'];
$jml_masalah = $stats['masalah'];
$jml_gejala = $stats['gejala'];
$jml_konsultasi = $stats['konsultasi'];

// 1. Rata-rata persentase keyakinan
$stmtAvg = $db->query("SELECT AVG(nilai_persentase) as rata_rata FROM hasil_konsultasi");
$rata_rata_keyakinan = round($stmtAvg->fetch()['rata_rata'] ?? 0, 1);

// 2. Masalah paling dominan
$stmtDominan = $db->query("
    SELECT m.nama_masalah, COUNT(*) as jumlah 
    FROM hasil_konsultasi h 
    JOIN masalah m ON h.id_masalah = m.id_masalah 
    GROUP BY h.id_masalah 
    ORDER BY jumlah DESC 
    LIMIT 1
");
$dominanRow = $stmtDominan->fetch();
$masalah_dominan = $dominanRow ? $dominanRow['nama_masalah'] : 'Belum Ada';

// 3. Distribusi tingkat keparahan untuk Grafik Donut
$stmtDist = $db->query("
    SELECT 
        SUM(CASE WHEN nilai_persentase >= 80 THEN 1 ELSE 0 END) as tinggi,
        SUM(CASE WHEN nilai_persentase >= 50 AND nilai_persentase < 80 THEN 1 ELSE 0 END) as sedang,
        SUM(CASE WHEN nilai_persentase < 50 THEN 1 ELSE 0 END) as rendah
    FROM hasil_konsultasi
");
$dist = $stmtDist->fetch();
$dist_tinggi = $dist['tinggi'] ?? 0;
$dist_sedang = $dist['sedang'] ?? 0;
$dist_rendah = $dist['rendah'] ?? 0;

// Query rincian masalah per tingkat keparahan
// 1. Kategori Tinggi (>= 80%)
$stmtTinggiList = $db->query("
    SELECT m.kode_masalah, m.nama_masalah, COUNT(*) as jumlah 
    FROM hasil_konsultasi h 
    JOIN masalah m ON h.id_masalah = m.id_masalah 
    WHERE h.nilai_persentase >= 80 
    GROUP BY h.id_masalah
    ORDER BY jumlah DESC
");
$list_tinggi = $stmtTinggiList->fetchAll();

// 2. Kategori Sedang (50 - 79%)
$stmtSedangList = $db->query("
    SELECT m.kode_masalah, m.nama_masalah, COUNT(*) as jumlah 
    FROM hasil_konsultasi h 
    JOIN masalah m ON h.id_masalah = m.id_masalah 
    WHERE h.nilai_persentase >= 50 AND h.nilai_persentase < 80 
    GROUP BY h.id_masalah
    ORDER BY jumlah DESC
");
$list_sedang = $stmtSedangList->fetchAll();

// 3. Kategori Rendah (< 50%)
$stmtRendahList = $db->query("
    SELECT m.kode_masalah, m.nama_masalah, COUNT(*) as jumlah 
    FROM hasil_konsultasi h 
    JOIN masalah m ON h.id_masalah = m.id_masalah 
    WHERE h.nilai_persentase < 50 
    GROUP BY h.id_masalah
    ORDER BY jumlah DESC
");
$list_rendah = $stmtRendahList->fetchAll();

// 4. Tren 5 Masalah Terbanyak untuk Grafik Batang
$stmtBar = $db->query("
    SELECT m.kode_masalah, COUNT(*) as jumlah 
    FROM hasil_konsultasi h 
    JOIN masalah m ON h.id_masalah = m.id_masalah 
    GROUP BY h.id_masalah 
    ORDER BY jumlah DESC 
    LIMIT 5
");
$barData = $stmtBar->fetchAll();
$barLabels = [];
$barCounts = [];
foreach ($barData as $bd) {
    $barLabels[] = $bd['kode_masalah'];
    $barCounts[] = $bd['jumlah'];
}

// 5. 5 Siswa terakhir dengan tingkat keparahan Tinggi (>= 80%)
$stmtUrgent = $db->prepare("
    SELECT r.id_konsultasi, r.tanggal, s.nama_siswa, s.kelas, s.nis,
           h.nilai_persentase, m.nama_masalah, m.kode_masalah
    FROM riwayat_konsultasi r
    JOIN siswa s ON r.id_siswa = s.id_siswa
    JOIN hasil_konsultasi h ON r.id_konsultasi = h.id_konsultasi
    JOIN masalah m ON h.id_masalah = m.id_masalah
    WHERE h.nilai_persentase >= 80
    ORDER BY r.tanggal DESC
    LIMIT 5
");
$stmtUrgent->execute();
$dataUrgent = $stmtUrgent->fetchAll();
?>

<!-- Import Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="mb-8">
    <h2 class="text-2xl font-bold text-slate-800">Selamat Datang, <?= htmlspecialchars($GLOBALS['admin_profile']['nama_lengkap'] ?? 'Guru BK') ?>!</h2>
    <p class="text-slate-500 mt-1 text-sm">Berikut adalah panel ringkasan analisis perilaku siswa di <?= htmlspecialchars($GLOBALS['admin_profile']['nama_sekolah'] ?? 'Sekolah') ?>.</p>
</div>

<!-- STATS CARDS -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Card 1: Total Siswa -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex items-center">
        <div class="w-12 h-12 rounded-xl bg-brand-50 flex items-center justify-center text-brand-600 mr-4 border border-brand-100">
            <i data-lucide="users" class="w-6 h-6"></i>
        </div>
        <div>
            <p class="text-xs font-semibold text-slate-400 uppercase">Total Siswa</p>
            <h3 class="text-2xl font-extrabold text-slate-800 mt-0.5"><?= $jml_siswa ?></h3>
        </div>
    </div>
    
    <!-- Card 2: Kasus Dominan -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex items-center">
        <div class="w-12 h-12 rounded-xl bg-red-50 flex items-center justify-center text-red-600 mr-4 border border-red-100">
            <i data-lucide="alert-triangle" class="w-6 h-6"></i>
        </div>
        <div class="overflow-hidden">
            <p class="text-xs font-semibold text-slate-400 uppercase">Masalah Dominan</p>
            <h3 class="text-sm font-bold text-slate-800 mt-1 truncate" title="<?= htmlspecialchars($masalah_dominan) ?>"><?= htmlspecialchars($masalah_dominan) ?></h3>
        </div>
    </div>

    <!-- Card 3: Rata-rata Keyakinan -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex items-center">
        <div class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600 mr-4 border border-blue-100">
            <i data-lucide="activity" class="w-6 h-6"></i>
        </div>
        <div>
            <p class="text-xs font-semibold text-slate-400 uppercase">Rata-rata Keyakinan</p>
            <h3 class="text-2xl font-extrabold text-slate-800 mt-0.5"><?= $rata_rata_keyakinan ?>%</h3>
        </div>
    </div>

    <!-- Card 4: Total Riwayat -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex items-center">
        <div class="w-12 h-12 rounded-xl bg-purple-50 flex items-center justify-center text-purple-600 mr-4 border border-purple-100">
            <i data-lucide="history" class="w-6 h-6"></i>
        </div>
        <div>
            <p class="text-xs font-semibold text-slate-400 uppercase">Total Riwayat</p>
            <h3 class="text-2xl font-extrabold text-slate-800 mt-0.5"><?= $jml_konsultasi ?></h3>
        </div>
    </div>
</div>

<!-- CHARTS PANEL -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <!-- Donut Chart: Keparahan Kasus (1 Kolom) -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex flex-col justify-between">
        <h3 class="text-sm font-bold text-slate-700 mb-4 flex items-center gap-1.5"><i data-lucide="pie-chart" class="w-4 h-4 text-brand-600"></i> Tingkat Keparahan</h3>
        <div class="relative w-full h-48 flex items-center justify-center">
            <canvas id="severityChart"></canvas>
        </div>
        <div class="grid grid-cols-3 gap-1 text-center mt-4 border-t pt-3 pb-2">
            <div>
                <span class="text-[10px] text-slate-400 block font-semibold">TINGGI</span>
                <span class="text-xs font-bold text-red-600"><?= $dist_tinggi ?> kasus</span>
            </div>
            <div>
                <span class="text-[10px] text-slate-400 block font-semibold">SEDANG</span>
                <span class="text-xs font-bold text-orange-600"><?= $dist_sedang ?> kasus</span>
            </div>
            <div>
                <span class="text-[10px] text-slate-400 block font-semibold">RENDAH</span>
                <span class="text-xs font-bold text-emerald-600"><?= $dist_rendah ?> kasus</span>
            </div>
        </div>
        
        <!-- Rincian Kasus Riil per Kategori -->
        <div class="mt-2 border-t pt-3 space-y-3 text-left">
            <div>
                <span class="text-[9px] font-bold text-red-600 block uppercase tracking-wider mb-1">🔴 Rincian Kasus Tinggi</span>
                <?php if (count($list_tinggi) > 0): ?>
                    <div class="space-y-1 pl-1">
                        <?php foreach ($list_tinggi as $lt): ?>
                            <div class="flex justify-between items-center text-[10px] text-slate-600 font-medium">
                                <span class="truncate pr-2">• <?= htmlspecialchars($lt['nama_masalah']) ?></span>
                                <span class="shrink-0 font-bold text-red-700 bg-red-50 px-1 py-0.5 rounded text-[9px]"><?= $lt['jumlah'] ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-[9px] text-slate-400 italic pl-1">Tidak ada kasus</p>
                <?php endif; ?>
            </div>

            <div>
                <span class="text-[9px] font-bold text-orange-600 block uppercase tracking-wider mb-1">🟠 Rincian Kasus Sedang</span>
                <?php if (count($list_sedang) > 0): ?>
                    <div class="space-y-1 pl-1">
                        <?php foreach ($list_sedang as $ls): ?>
                            <div class="flex justify-between items-center text-[10px] text-slate-600 font-medium">
                                <span class="truncate pr-2">• <?= htmlspecialchars($ls['nama_masalah']) ?></span>
                                <span class="shrink-0 font-bold text-orange-700 bg-orange-50 px-1 py-0.5 rounded text-[9px]"><?= $ls['jumlah'] ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-[9px] text-slate-400 italic pl-1">Tidak ada kasus</p>
                <?php endif; ?>
            </div>

            <div>
                <span class="text-[9px] font-bold text-emerald-600 block uppercase tracking-wider mb-1">🟢 Rincian Kasus Rendah</span>
                <?php if (count($list_rendah) > 0): ?>
                    <div class="space-y-1 pl-1">
                        <?php foreach ($list_rendah as $lr): ?>
                            <div class="flex justify-between items-center text-[10px] text-slate-600 font-medium">
                                <span class="truncate pr-2">• <?= htmlspecialchars($lr['nama_masalah']) ?></span>
                                <span class="shrink-0 font-bold text-emerald-700 bg-emerald-50 px-1 py-0.5 rounded text-[9px]"><?= $lr['jumlah'] ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-[9px] text-slate-400 italic pl-1">Tidak ada kasus</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bar Chart: Top 5 Masalah (2 Kolom) -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm lg:col-span-2 flex flex-col justify-between">
        <h3 class="text-sm font-bold text-slate-700 mb-4 flex items-center gap-1.5"><i data-lucide="bar-chart-3" class="w-4 h-4 text-brand-600"></i> Distribusi Jenis Masalah Perilaku</h3>
        <div class="relative w-full h-56">
            <canvas id="problemsChart"></canvas>
        </div>
    </div>
</div>

<!-- BOTTOM SECTION: URGENT CASES & QUICK ACTIONS -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Urgent Cases (2 Kolom) -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm lg:col-span-2">
        <div class="flex justify-between items-center mb-4 pb-2 border-b">
            <h3 class="text-sm font-bold text-slate-800 flex items-center gap-1.5"><i data-lucide="alert-circle" class="w-4.5 h-4.5 text-red-500 animate-pulse"></i> Siswa Prioritas Penanganan (Tinggi)</h3>
            <span class="text-[10px] bg-red-50 text-red-700 font-bold px-2 py-0.5 rounded border border-red-100 uppercase font-semibold">Urgensi Tinggi</span>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100">
                        <th class="px-4 py-3 font-semibold text-slate-500">Siswa</th>
                        <th class="px-4 py-3 font-semibold text-slate-500">Diagnosis Masalah</th>
                        <th class="px-4 py-3 font-semibold text-slate-500 text-center">Keyakinan</th>
                        <th class="px-4 py-3 font-semibold text-slate-500 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($dataUrgent as $row): ?>
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-4 py-3">
                            <span class="font-bold text-slate-800 block"><?= htmlspecialchars($row['nama_siswa']) ?></span>
                            <span class="text-[10px] text-slate-400"><?= $row['nis'] ?> · <?= $row['kelas'] ?></span>
                        </td>
                        <td class="px-4 py-3 text-slate-600 font-medium">
                            <span class="text-orange-600 font-bold"><?= $row['kode_masalah'] ?></span> - <?= htmlspecialchars($row['nama_masalah']) ?>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-block px-2 py-0.5 rounded-full font-bold bg-red-100 text-red-700 border border-red-200">
                                <?= $row['nilai_persentase'] ?>%
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="index.php?page=riwayat&detail=<?= $row['id_konsultasi'] ?>" class="inline-flex items-center gap-1 bg-slate-100 hover:bg-brand-50 text-slate-600 hover:text-brand-700 px-2 py-1 rounded font-semibold text-[10px] transition-colors border border-slate-200 hover:border-brand-200" title="Buka Detail">
                                <i data-lucide="eye" class="w-3.5 h-3.5"></i> Detail
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>

                    <?php if (count($dataUrgent) == 0): ?>
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-slate-400 italic">
                            <div class="flex flex-col items-center justify-center gap-1.5">
                                <i data-lucide="smile" class="w-8 h-8 text-slate-300"></i>
                                <span>Luar biasa! Belum ada kasus terindikasi tingkat keparahan tinggi.</span>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Quick Actions & Info (1 Kolom) -->
    <div class="space-y-6">
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
            <h3 class="text-sm font-bold text-slate-800 mb-4 flex items-center gap-1.5"><i data-lucide="zap" class="w-4 h-4 text-brand-600"></i> Aksi Cepat</h3>
            <div class="grid grid-cols-1 gap-3">
                <a href="index.php?page=konsultasi" class="flex items-center p-4 bg-slate-50 border border-slate-200 rounded-xl hover:border-brand-300 hover:bg-brand-50 transition-colors gap-3.5 shadow-sm hover:shadow-md">
                    <div class="w-10 h-10 rounded-xl bg-white text-brand-600 flex items-center justify-center shadow-sm shrink-0 border border-brand-50">
                        <i data-lucide="stethoscope" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <span class="font-bold text-slate-700 text-xs block">Mulai Analisis Baru</span>
                        <span class="text-[10px] text-slate-400 block mt-0.5">Penelusuran gejala Backward Chaining</span>
                    </div>
                </a>
                
                <a href="index.php?page=siswa" class="flex items-center p-4 bg-slate-50 border border-slate-200 rounded-xl hover:border-blue-300 hover:bg-blue-50 transition-colors gap-3.5 shadow-sm hover:shadow-md">
                    <div class="w-10 h-10 rounded-xl bg-white text-blue-600 flex items-center justify-center shadow-sm shrink-0 border border-blue-50">
                        <i data-lucide="user-plus" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <span class="font-bold text-slate-700 text-xs block">Tambah Data Siswa</span>
                        <span class="text-[10px] text-slate-400 block mt-0.5">Daftarkan profil murid baru</span>
                    </div>
                </a>
            </div>
        </div>

        <div class="bg-slate-900 text-slate-200 p-6 rounded-2xl shadow-xl flex flex-col justify-between relative overflow-hidden border border-slate-800">
            <!-- Background light effect -->
            <div class="absolute -right-10 -bottom-10 w-28 h-28 bg-brand-500/10 rounded-full blur-2xl"></div>
            <div>
                <h4 class="font-bold text-white text-xs flex items-center gap-1.5 mb-2"><i data-lucide="cpu" class="w-4 h-4 text-brand-400"></i> Metodologi Ilmiah</h4>
                <p class="text-slate-400 text-[11px] leading-relaxed mb-4">
                    Sistem ini mengkombinasikan pelacakan logis <strong>Backward Chaining</strong> (mencocokkan premis gejala) dengan <strong>Dempster-Shafer</strong> (kombinasi densitas pembuktian keyakinan) untuk diagnosis yang akurat.
                </p>
            </div>
            <a href="index.php?page=aturan" class="text-xs font-bold text-brand-400 hover:text-brand-300 inline-flex items-center gap-1 mt-2">
                Lihat Aturan Pakar <i data-lucide="chevron-right" class="w-4 h-4"></i>
            </a>
        </div>
    </div>
</div>

<!-- CHARTS INITIALIZATION SCRIPT -->
<script>
    document.getElementById('page-title').innerText = 'Dashboard Analitik';

    // 1. Severity Distribution Chart (Donut)
    const severityCtx = document.getElementById('severityChart').getContext('2d');
    new Chart(severityCtx, {
        type: 'doughnut',
        data: {
            labels: ['Tinggi', 'Sedang', 'Rendah'],
            datasets: [{
                data: [<?= $dist_tinggi ?>, <?= $dist_sedang ?>, <?= $dist_rendah ?>],
                backgroundColor: ['#ef4444', '#f97316', '#10b981'],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false // Kita pakai legend manual di bawah
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return ` ${context.label}: ${context.raw} kasus`;
                        }
                    }
                }
            },
            cutout: '70%'
        }
    });

    // 2. Horizontal Bar Chart for Top 5 Problems
    const problemsCtx = document.getElementById('problemsChart').getContext('2d');
    new Chart(problemsCtx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($barLabels) ?>,
            datasets: [{
                label: 'Jumlah Kasus',
                data: <?= json_encode($barCounts) ?>,
                backgroundColor: '#16a34a',
                borderRadius: 8,
                barThickness: 16
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y', // Mengubah menjadi horizontal bar chart
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        stepSize: 1,
                        font: {
                            size: 10
                        }
                    }
                },
                y: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            size: 10,
                            weight: 'bold'
                        }
                    }
                }
            }
        }
    });
</script>
