    <!-- Sidebar -->
    <aside id="sidebar" class="w-64 bg-white border-r border-slate-200 flex flex-col h-full shrink-0 shadow-sm z-10 transition-all duration-300 overflow-hidden">
        <!-- Logo -->
        <div class="h-20 flex items-center px-5 border-b border-slate-100 sidebar-logo gap-3">
            <?php if (!empty($GLOBALS['admin_profile']['logo_sekolah']) && file_exists($GLOBALS['admin_profile']['logo_sekolah'])): ?>
                <img src="<?= $GLOBALS['admin_profile']['logo_sekolah'] ?>" class="w-10 h-10 object-contain shrink-0 rounded" alt="Logo Sekolah">
            <?php else: ?>
                <div class="w-10 h-10 bg-brand-50 rounded-xl flex items-center justify-center border border-brand-100 shrink-0">
                    <i data-lucide="graduation-cap" class="text-brand-600 w-5 h-5"></i>
                </div>
            <?php endif; ?>
            <div class="overflow-hidden menu-text">
                <span class="font-bold text-base tracking-tight text-slate-800 block leading-tight">Pakar<span class="text-brand-600">BK</span></span>
                <?php if (!empty($GLOBALS['admin_profile']['nama_sekolah'])): ?>
                    <span class="text-[10px] font-semibold text-slate-400 block truncate leading-none mt-1"><?= htmlspecialchars($GLOBALS['admin_profile']['nama_sekolah']) ?></span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1 overflow-x-hidden">
            <?php 
                function getMenuClass($current_page, $menu_page) {
                    if ($current_page === $menu_page) {
                        return 'flex items-center px-3 py-2.5 rounded-xl font-bold bg-brand-50 text-brand-700 transition-colors group';
                    }
                    return 'flex items-center px-3 py-2.5 rounded-xl hover:bg-brand-50 text-slate-700 hover:text-brand-700 font-medium transition-colors group';
                }
                function getIconClass($current_page, $menu_page) {
                    if ($current_page === $menu_page) {
                        return 'w-5 h-5 mr-3 shrink-0 text-brand-600';
                    }
                    return 'w-5 h-5 mr-3 shrink-0 text-slate-400 group-hover:text-brand-600 transition-colors';
                }
            ?>

            <a href="index.php?page=dashboard" class="<?= getMenuClass($page, 'dashboard') ?>" title="Dashboard">
                <i data-lucide="layout-dashboard" class="<?= getIconClass($page, 'dashboard') ?>"></i>
                <span class="menu-text whitespace-nowrap">Dashboard</span>
            </a>
            
            <div class="pt-4 pb-2 px-3 menu-text">
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider whitespace-nowrap">Master Data</p>
            </div>
            
            <a href="index.php?page=siswa" class="<?= getMenuClass($page, 'siswa') ?>" title="Data Siswa">
                <i data-lucide="users" class="<?= getIconClass($page, 'siswa') ?>"></i>
                <span class="menu-text whitespace-nowrap">Data Siswa</span>
            </a>
            <a href="index.php?page=masalah" class="<?= getMenuClass($page, 'masalah') ?>" title="Data Masalah">
                <i data-lucide="alert-triangle" class="<?= getIconClass($page, 'masalah') ?>"></i>
                <span class="menu-text whitespace-nowrap">Data Masalah</span>
            </a>
            <a href="index.php?page=gejala" class="<?= getMenuClass($page, 'gejala') ?>" title="Data Gejala">
                <i data-lucide="activity" class="<?= getIconClass($page, 'gejala') ?>"></i>
                <span class="menu-text whitespace-nowrap">Data Gejala</span>
            </a>
            <a href="index.php?page=aturan" class="<?= getMenuClass($page, 'aturan') ?>" title="Aturan Pakar">
                <i data-lucide="git-merge" class="<?= getIconClass($page, 'aturan') ?>"></i>
                <span class="menu-text whitespace-nowrap">Aturan Pakar</span>
            </a>

            <div class="pt-4 pb-2 px-3 menu-text">
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider whitespace-nowrap">Sistem Pakar</p>
            </div>

            <a href="index.php?page=konsultasi" class="<?= getMenuClass($page, 'konsultasi') ?>" title="Mulai Konsultasi">
                <i data-lucide="stethoscope" class="<?= getIconClass($page, 'konsultasi') ?>"></i>
                <span class="menu-text whitespace-nowrap">Mulai Konsultasi</span>
            </a>
            <a href="index.php?page=riwayat" class="<?= getMenuClass($page, 'riwayat') ?>" title="Riwayat Analisis">
                <i data-lucide="history" class="<?= getIconClass($page, 'riwayat') ?>"></i>
                <span class="menu-text whitespace-nowrap">Riwayat Analisis</span>
            </a>

            <div class="pt-4 pb-2 px-3 menu-text">
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider whitespace-nowrap">Pengaturan</p>
            </div>
            <a href="index.php?page=profil" class="<?= getMenuClass($page, 'profil') ?>" title="Profil Sekolah">
                <i data-lucide="settings" class="<?= getIconClass($page, 'profil') ?>"></i>
                <span class="menu-text whitespace-nowrap">Profil Sekolah</span>
            </a>

            <div class="pt-8 px-3">
                <a href="index.php?page=logout" class="flex items-center justify-center w-full px-4 py-2 bg-red-50 text-red-600 hover:bg-red-100 font-bold rounded-xl transition-colors" title="Keluar">
                    <i data-lucide="log-out" class="w-4 h-4 shrink-0"></i> 
                    <span class="menu-text whitespace-nowrap ml-2">Keluar</span>
                </a>
            </div>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 overflow-y-auto bg-slate-50 relative flex flex-col h-screen">
        <!-- Topbar -->
        <header class="bg-white/80 backdrop-blur-md border-b border-slate-200 sticky top-0 z-10 px-8 py-4 flex justify-between items-center shrink-0">
            <div class="flex items-center">
                <button id="toggleSidebar" class="mr-4 p-2 rounded-lg text-slate-400 hover:text-brand-600 hover:bg-brand-50 transition-colors">
                    <i data-lucide="menu" class="w-6 h-6"></i>
                </button>
                <h2 class="text-xl font-bold text-slate-800" id="page-title">Dashboard</h2>
            </div>
            <div class="flex items-center space-x-4">
                <?php if (!empty($GLOBALS['admin_profile']['logo_sekolah']) && file_exists($GLOBALS['admin_profile']['logo_sekolah'])): ?>
                    <img src="<?= $GLOBALS['admin_profile']['logo_sekolah'] ?>" class="w-10 h-10 rounded-full object-cover border border-slate-200" alt="Logo">
                <?php else: ?>
                    <div class="w-10 h-10 rounded-full bg-brand-100 flex items-center justify-center text-brand-600 font-bold border border-brand-200">
                        <?= strtoupper(substr($GLOBALS['admin_profile']['nama_lengkap'] ?? 'BK', 0, 2)) ?>
                    </div>
                <?php endif; ?>
                <div>
                    <p class="text-sm font-bold text-slate-700"><?= htmlspecialchars($GLOBALS['admin_profile']['nama_lengkap'] ?? 'Guru BK') ?></p>
                    <p class="text-xs text-slate-500"><?= htmlspecialchars($GLOBALS['admin_profile']['nama_sekolah'] ?? 'Administrator') ?></p>
                </div>
            </div>
        </header>

        <!-- Content Area -->
        <div class="p-8">
