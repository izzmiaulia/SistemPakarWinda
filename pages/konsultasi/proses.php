<?php
$siswaModel   = new Siswa($db);
$masalahModel = new Masalah($db);
$aturanModel  = new Aturan($db);
$gejalaModel  = new Gejala($db);

// ─── CASE 1: Inisialisasi Sesi Baru dari mulai.php ────────────────────────────
if (isset($_POST['id_siswa']) && isset($_POST['id_masalah'])) {
    $id_siswa   = $_POST['id_siswa'];
    $id_masalah = $_POST['id_masalah'];

    $siswa   = $siswaModel->getSiswaById($id_siswa);
    $masalah = $masalahModel->getMasalahById($id_masalah);

    if ($siswa && $masalah) {
        $_SESSION['konsultasi'] = [
            'id_siswa'          => $id_siswa,
            'suspected_masalah' => $id_masalah,
            'active_masalah'    => $id_masalah,
            'answers'           => [], // [id_gejala => 1 (YA) / 0 (TIDAK)]
            'logs'              => [
                "Sistem memulai pelacakan Backward Chaining untuk membuktikan dugaan awal (Goal): <strong>[" . $masalah['kode_masalah'] . "] " . htmlspecialchars($masalah['nama_masalah']) . "</strong>."
            ],
            'rejected_masalah'  => []
        ];
        header("Location: index.php?page=proses");
        exit;
    }
}

// Cek jika sesi tidak ada (akses ilegal langsung ke proses.php)
if (!isset($_SESSION['konsultasi'])) {
    header("Location: index.php?page=konsultasi");
    exit;
}

$state =& $_SESSION['konsultasi'];
$siswa = $siswaModel->getSiswaById($state['id_siswa']);

// ─── CASE 2: Memproses Jawaban YA / TIDAK yang Dikirim ──────────────────────
if (isset($_POST['id_gejala']) && isset($_POST['jawaban'])) {
    $id_gejala = (int)$_POST['id_gejala'];
    $jawaban   = (int)$_POST['jawaban']; // 1 = YA, 0 = TIDAK

    if (!isset($state['answers'][$id_gejala])) {
        $state['answers'][$id_gejala] = $jawaban;

        $g = $gejalaModel->getGejalaById($id_gejala);
        $textJawab = $jawaban ? "<span class='text-emerald-600 font-bold'>YA</span>" : "<span class='text-slate-400 font-bold'>TIDAK</span>";
        $state['logs'][] = "Menanyakan gejala <strong>" . $g['kode_gejala'] . "</strong>: \"" . htmlspecialchars($g['nama_gejala']) . "\". Jawaban: " . $textJawab . ".";
    }

    // PRG Pattern: Redirect ke GET request untuk menghindari popup resubmission browser
    header("Location: index.php?page=proses");
    exit;
}

// ─── CASE 3: Algoritma Pelacakan & Backtracking Backward Chaining ────────────
$active_masalah = $state['active_masalah'];
$gejala_aturan  = $aturanModel->getAturanByMasalah($active_masalah);
$total_aturan   = count($gejala_aturan);

$answered_count = 0;
$tidak_count    = 0;
$ya_count       = 0;
$next_gejala    = null;

foreach ($gejala_aturan as $ga) {
    $id_g = $ga['id_gejala'];
    if (array_key_exists($id_g, $state['answers'])) {
        $answered_count++;
        if ($state['answers'][$id_g] == 0) {
            $tidak_count++;
        } else {
            $ya_count++;
        }
    } else {
        if ($next_gejala === null) {
            $next_gejala = $ga;
        }
    }
}

// Cek Kelayakan Hipotesis (Gugur jika > 50% gejala bernilai TIDAK atau semua yang ditanya bernilai TIDAK)
$is_viable = true;
if ($total_aturan > 0) {
    if ($tidak_count > ($total_aturan / 2) || ($answered_count > 0 && $ya_count == 0)) {
        $is_viable = false;
    }
}

// Jika Hipotesis Gugur, lakukan Backtracking ke Masalah Lain
if (!$is_viable) {
    $m_old = $masalahModel->getMasalahById($active_masalah);
    $state['logs'][] = "Hipotesis <strong>[" . $m_old['kode_masalah'] . "] " . htmlspecialchars($m_old['nama_masalah']) . "</strong> dinyatakan <span class='text-red-600 font-bold'>GUGUR</span> karena gejala yang terpenuhi tidak mencukupi.";
    $state['rejected_masalah'][] = $active_masalah;

    // Cari kandidat hipotesis baru
    $semua_masalah = $masalahModel->getAllMasalah();
    $best_candidate = null;
    $best_score     = -999;

    foreach ($semua_masalah as $cm) {
        $cm_id = $cm['id_masalah'];
        if (in_array($cm_id, $state['rejected_masalah'])) {
            continue;
        }

        $cm_aturan = $aturanModel->getAturanByMasalah($cm_id);
        $cm_ya = 0;
        $cm_tidak = 0;
        $cm_unasked = 0;

        foreach ($cm_aturan as $cma) {
            $id_g = $cma['id_gejala'];
            if (array_key_exists($id_g, $state['answers'])) {
                if ($state['answers'][$id_g] == 1) {
                    $cm_ya++;
                } else {
                    $cm_tidak++;
                }
            } else {
                $cm_unasked++;
            }
        }

        if ($cm_unasked > 0) {
            $score = $cm_ya - $cm_tidak;
            if ($score > $best_score) {
                $best_score = $score;
                $best_candidate = $cm_id;
            }
        }
    }

    if ($best_candidate !== null) {
        $state['active_masalah'] = $best_candidate;
        $m_new = $masalahModel->getMasalahById($best_candidate);
        $state['logs'][] = "Sistem melakukan <strong>Backtracking</strong>. Mengalihkan pengujian ke hipotesis baru: <strong>[" . $m_new['kode_masalah'] . "] " . htmlspecialchars($m_new['nama_masalah']) . "</strong>.";
        
        // Perbarui data active_masalah
        $active_masalah = $best_candidate;
        $gejala_aturan  = $aturanModel->getAturanByMasalah($active_masalah);
        
        $next_gejala = null;
        foreach ($gejala_aturan as $ga) {
            $id_g = $ga['id_gejala'];
            if (!array_key_exists($id_g, $state['answers'])) {
                $next_gejala = $ga;
                break;
            }
        }
    } else {
        // Tidak ada lagi masalah alternatif
        $next_gejala = null;
    }
}

// Data Masalah Aktif saat ini untuk visualisasi
$masalah_aktif = $masalahModel->getMasalahById($active_masalah);
$no_pertanyaan = count($state['answers']) + 1;
?>

<div class="mb-6">
    <h2 class="text-2xl font-bold text-slate-800">Pertanyaan Analisis Interaktif</h2>
    <p class="text-slate-500 mt-1 text-sm">Penelusuran gejala satu per satu menggunakan metode Backward Chaining.</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <!-- Panel Kiri: Informasi Sesi & Log Pelacakan -->
    <div class="col-span-1 space-y-5">
        <div class="card p-5">
            <h3 class="font-bold text-slate-800 border-b border-slate-100 pb-3 mb-4 flex items-center gap-1.5">
                <i data-lucide="info" class="w-4 h-4 text-brand-600"></i> Informasi Sesi
            </h3>
            <div class="space-y-3 text-sm">
                <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase">Siswa</p>
                    <p class="font-semibold text-slate-700"><?= htmlspecialchars($siswa['nama_siswa']) ?></p>
                    <p class="text-xs text-slate-500">NIS: <?= $siswa['nis'] ?> | Kelas: <?= $siswa['kelas'] ?></p>
                </div>
                <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase">Hipotesis Aktif</p>
                    <span class="inline-block mt-1 px-2.5 py-1 rounded bg-orange-50 border border-orange-200 text-orange-700 font-bold text-xs">
                        <?= $masalah_aktif['kode_masalah'] ?> – <?= htmlspecialchars($masalah_aktif['nama_masalah']) ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="card p-5">
            <h3 class="font-bold text-slate-800 border-b border-slate-100 pb-3 mb-4 flex items-center gap-1.5">
                <i data-lucide="git-branch" class="w-4 h-4 text-brand-600"></i> Log Pelacakan BC
            </h3>
            <div class="space-y-3 max-h-[220px] overflow-y-auto pr-1 text-xs text-slate-600 leading-relaxed scrollbar-thin">
                <?php foreach (array_reverse($state['logs']) as $log): ?>
                <div class="border-b border-slate-50 pb-2">
                    <p><?= $log ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Panel Kanan: Kartu Pertanyaan / Selesai -->
    <div class="col-span-1 md:col-span-2">
        <div class="card p-8 min-h-[300px] flex flex-col justify-between">
            <?php if ($next_gejala !== null): ?>
                <!-- TAMPILAN PERTANYAAN -->
                <div>
                    <div class="flex justify-between items-center mb-6">
                        <span class="text-xs font-bold bg-blue-100 text-blue-700 border border-blue-200 px-3 py-1 rounded-full">Pertanyaan ke-<?= $no_pertanyaan ?></span>
                        <span class="text-xs text-slate-400">Kode Gejala: <strong><?= $next_gejala['kode_gejala'] ?></strong></span>
                    </div>

                    <h3 class="text-xl font-bold text-slate-800 leading-snug mb-8">
                        Apakah siswa menunjukkan perilaku/kondisi: <br>
                        <span class="text-brand-700 mt-2 block font-extrabold">"<?= htmlspecialchars($next_gejala['nama_gejala']) ?>"</span>?
                    </h3>
                </div>

                <div class="flex flex-col sm:flex-row gap-4 pt-6 border-t border-slate-100">
                    <form method="POST" class="flex-1">
                        <input type="hidden" name="id_gejala" value="<?= $next_gejala['id_gejala'] ?>">
                        <input type="hidden" name="jawaban" value="1">
                        <button type="submit" class="w-full py-4 bg-emerald-600 hover:bg-emerald-700 text-white rounded-2xl font-bold text-base transition-colors shadow-md shadow-emerald-500/20 flex items-center justify-center gap-2">
                            <i data-lucide="check" class="w-5 h-5"></i> YA (Sesuai)
                        </button>
                    </form>
                    
                    <form method="POST" class="flex-1">
                        <input type="hidden" name="id_gejala" value="<?= $next_gejala['id_gejala'] ?>">
                        <input type="hidden" name="jawaban" value="0">
                        <button type="submit" class="w-full py-4 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-2xl font-bold text-base transition-colors flex items-center justify-center gap-2">
                            <i data-lucide="x" class="w-5 h-5"></i> TIDAK (Tidak Sesuai)
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <!-- TAMPILAN SELESAI -->
                <div class="text-center py-6 flex-1 flex flex-col justify-center items-center">
                    <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mb-4">
                        <i data-lucide="party-popper" class="w-8 h-8"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-800 mb-2">Penelusuran Gejala Selesai</h3>
                    <p class="text-slate-500 text-sm max-w-md mx-auto mb-8">Sistem telah selesai menguji semua rute hipotesis yang layak berdasarkan masukan fakta Anda.</p>
                    
                    <form action="index.php?page=hasil" method="POST" class="w-full max-w-sm">
                        <input type="hidden" name="id_siswa" value="<?= $state['id_siswa'] ?>">
                        <input type="hidden" name="id_masalah_hipotesis" value="<?= $state['active_masalah'] ?>">
                        <?php 
                        // Kumpulkan semua id_gejala yang dijawab YA
                        foreach ($state['answers'] as $id_g => $jawaban) {
                            if ($jawaban == 1) {
                                echo '<input type="hidden" name="gejala[]" value="' . $id_g . '">';
                            }
                        }
                        ?>
                        <button type="submit" class="w-full py-3.5 bg-brand-600 hover:bg-brand-700 text-white rounded-xl font-bold transition-colors shadow-md shadow-brand-500/20 flex items-center justify-center gap-2">
                            Lihat Hasil Analisis <i data-lucide="arrow-right" class="w-4 h-4"></i>
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="mt-4 flex justify-between">
            <a href="#" onclick="batalkanSesi(event)" class="text-slate-400 hover:text-slate-600 text-sm font-medium flex items-center gap-1">
                <i data-lucide="trash-2" class="w-4 h-4"></i> Batalkan Sesi
            </a>
        </div>
    </div>
</div>

<script>
    document.getElementById('page-title').innerText = 'Checklist Gejala';

    function batalkanSesi(e) {
        e.preventDefault();
        Swal.fire({
            title: 'Batalkan Sesi?',
            text: 'Semua jawaban yang telah diisi pada sesi konsultasi ini akan hilang.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, batalkan!',
            cancelButtonText: 'Lanjutkan'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'index.php?page=konsultasi';
            }
        });
    }
</script>
