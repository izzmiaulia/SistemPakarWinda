<?php
$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Cari admin di database
    $stmt = $db->prepare("SELECT * FROM admin WHERE username = :user LIMIT 1");
    $stmt->execute([':user' => $username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['login'] = true;
        $_SESSION['id_admin'] = $admin['id_admin'];
        header('Location: index.php?page=dashboard');
        exit;
    } else {
        $error = 'Username atau Password salah!';
    }
}
?>
<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 bg-slate-50">
    <div class="max-w-md w-full bg-white p-8 rounded-2xl shadow-xl border border-slate-100">
        <div class="text-center mb-8">
            <?php if (!empty($GLOBALS['admin_profile']['logo_sekolah']) && file_exists($GLOBALS['admin_profile']['logo_sekolah'])): ?>
                <img src="<?= $GLOBALS['admin_profile']['logo_sekolah'] ?>" class="mx-auto h-20 w-auto object-contain mb-3" alt="Logo Sekolah">
            <?php else: ?>
                <div class="mx-auto w-16 h-16 bg-brand-50 rounded-full flex items-center justify-center mb-4 shadow-sm border border-brand-100 animate-pulse">
                    <i data-lucide="graduation-cap" class="w-8 h-8 text-brand-600"></i>
                </div>
            <?php endif; ?>
            
            <h2 class="text-3xl font-extrabold text-slate-800">Pakar<span class="text-brand-600">BK</span></h2>
            <?php if (!empty($GLOBALS['admin_profile']['nama_sekolah'])): ?>
                <p class="mt-1.5 text-sm font-bold text-slate-500 uppercase tracking-wide"><?= htmlspecialchars($GLOBALS['admin_profile']['nama_sekolah']) ?></p>
            <?php endif; ?>
            <p class="mt-1 text-xs text-slate-400 font-medium">Layanan Bimbingan & Konseling</p>
        </div>
        
        <form class="space-y-6" method="POST" action="index.php?page=login">
            <?php if ($error): ?>
                <div class="bg-red-50 text-red-700 p-4 rounded-xl text-sm font-semibold flex items-center border border-red-100">
                    <i data-lucide="alert-circle" class="w-5 h-5 mr-2 shrink-0"></i> <?= $error ?>
                </div>
            <?php endif; ?>
            
            <div class="space-y-4">
                <div>
                    <label for="username" class="block text-sm font-bold text-slate-700 mb-1">Username</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i data-lucide="user" class="w-5 h-5 text-slate-400"></i>
                        </div>
                        <input id="username" name="username" type="text" required class="appearance-none rounded-xl relative block w-full px-3 py-3 pl-10 border border-slate-300 placeholder-slate-400 text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 sm:text-sm bg-slate-50/50" placeholder="gurubk">
                    </div>
                </div>
                <div>
                    <label for="password" class="block text-sm font-bold text-slate-700 mb-1">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i data-lucide="lock" class="w-5 h-5 text-slate-400"></i>
                        </div>
                        <input id="password" name="password" type="password" required class="appearance-none rounded-xl relative block w-full px-3 py-3 pl-10 border border-slate-300 placeholder-slate-400 text-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 sm:text-sm bg-slate-50/50" placeholder="••••••••">
                    </div>
                </div>
            </div>

            <div>
                <button type="submit" class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-bold rounded-xl text-white bg-brand-600 hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 transition-colors shadow-md shadow-brand-500/20">
                    Masuk ke Sistem
                    <i data-lucide="arrow-right" class="w-5 h-5 ml-2 absolute right-4 group-hover:translate-x-1 transition-transform"></i>
                </button>
            </div>
        </form>
    </div>
</div>
