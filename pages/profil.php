<?php
// Handle form submission
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_lengkap = trim($_POST['nama_lengkap']);
    $username = trim($_POST['username']);
    $nama_sekolah = trim($_POST['nama_sekolah']);
    $password = $_POST['password'];

    // Validasi input wajib
    if (empty($nama_lengkap) || empty($username) || empty($nama_sekolah)) {
        $error = 'Semua field wajib diisi (kecuali password baru).';
    } else {
        // Cek username unik di database (kecuali id_admin yang sekarang)
        $stmtUser = $db->prepare("SELECT COUNT(*) FROM admin WHERE username = :user AND id_admin != :id");
        $stmtUser->execute([':user' => $username, ':id' => $admin_profile['id_admin']]);
        if ($stmtUser->fetchColumn() > 0) {
            $error = 'Username sudah digunakan oleh akun lain.';
        } else {
            // Handle file upload logo_sekolah
            $logo_path = $admin_profile['logo_sekolah'];
            if (isset($_FILES['logo_sekolah']) && $_FILES['logo_sekolah']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath = $_FILES['logo_sekolah']['tmp_name'];
                $fileName = $_FILES['logo_sekolah']['name'];
                $fileSize = $_FILES['logo_sekolah']['size'];
                $fileType = $_FILES['logo_sekolah']['type'];
                
                $fileNameCmps = explode(".", $fileName);
                $fileExtension = strtolower(end($fileNameCmps));
                
                $allowedExtensions = ['jpg', 'jpeg', 'png'];
                if (in_array($fileExtension, $allowedExtensions)) {
                    // Cek direktori uploads
                    if (!is_dir('uploads')) {
                        mkdir('uploads', 0777, true);
                    }
                    
                    // Path file baru
                    $newFileName = 'logo_' . time() . '.' . $fileExtension;
                    $uploadFileDir = 'uploads/';
                    $dest_path = $uploadFileDir . $newFileName;
                    
                    if (move_uploaded_file($fileTmpPath, $dest_path)) {
                        // Hapus file lama jika ada
                        if (!empty($admin_profile['logo_sekolah']) && file_exists($admin_profile['logo_sekolah'])) {
                            @unlink($admin_profile['logo_sekolah']);
                        }
                        $logo_path = $dest_path;
                    } else {
                        $error = 'Ada masalah saat mengunggah file logo.';
                    }
                } else {
                    $error = 'Unggahan gagal. Jenis file yang diperbolehkan hanya: ' . implode(',', $allowedExtensions);
                }
            }

            if (empty($error)) {
                // Siapkan SQL update
                if (!empty($password)) {
                    // Update password baru
                    $passHash = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $db->prepare("UPDATE admin SET username = :user, password = :pass, nama_lengkap = :nama, nama_sekolah = :sekolah, logo_sekolah = :logo WHERE id_admin = :id");
                    $params = [
                        ':user' => $username,
                        ':pass' => $passHash,
                        ':nama' => $nama_lengkap,
                        ':sekolah' => $nama_sekolah,
                        ':logo' => $logo_path,
                        ':id' => $admin_profile['id_admin']
                    ];
                } else {
                    // Update tanpa ganti password
                    $stmt = $db->prepare("UPDATE admin SET username = :user, nama_lengkap = :nama, nama_sekolah = :sekolah, logo_sekolah = :logo WHERE id_admin = :id");
                    $params = [
                        ':user' => $username,
                        ':nama' => $nama_lengkap,
                        ':sekolah' => $nama_sekolah,
                        ':logo' => $logo_path,
                        ':id' => $admin_profile['id_admin']
                    ];
                }

                if ($stmt->execute($params)) {
                    // Update session/global variable agar langsung terefleksi di sidebar
                    $admin_profile = $db->query("SELECT * FROM admin WHERE id_admin = " . $admin_profile['id_admin'])->fetch();
                    $GLOBALS['admin_profile'] = $admin_profile;
                    $success = 'Profil sekolah & admin berhasil diperbarui!';
                } else {
                    $error = 'Gagal menyimpan perubahan ke database.';
                }
            }
        }
    }
}
?>

<div class="mb-6">
    <h2 class="text-2xl font-bold text-slate-800">Profil & Identitas Sekolah</h2>
    <p class="text-slate-500 mt-1 text-sm">Sesuaikan informasi instansi dan kredensial akses administrator Guru BK.</p>
</div>

<!-- SweetAlert2 Alerts -->
<?php if (!empty($success)): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({ icon: 'success', title: 'Berhasil!', text: '<?= addslashes($success) ?>', confirmButtonColor: '#16a34a' });
        });
    </script>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({ icon: 'error', title: 'Gagal!', text: '<?= addslashes($error) ?>', confirmButtonColor: '#dc2626' });
        });
    </script>
<?php endif; ?>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <!-- Form Profil -->
    <div class="col-span-1 md:col-span-2">
        <div class="card overflow-hidden">
            <div class="bg-brand-600 text-white p-6">
                <h3 class="font-bold text-lg flex items-center gap-2">
                    <i data-lucide="settings" class="w-5 h-5"></i> Konfigurasi Profil BK
                </h3>
                <p class="text-brand-100 text-xs mt-1">Ubah identitas instansi dan kata sandi berkala untuk mengamankan data siswa.</p>
            </div>
            
            <form method="POST" action="index.php?page=profil" enctype="multipart/form-data" class="p-6 space-y-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2 flex items-center gap-1.5">
                            <i data-lucide="user" class="w-4 h-4 text-slate-400"></i> Nama Lengkap Admin
                        </label>
                        <input type="text" name="nama_lengkap" required value="<?= htmlspecialchars($admin_profile['nama_lengkap']) ?>" class="w-full border border-slate-300 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 bg-white text-slate-700 text-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2 flex items-center gap-1.5">
                            <i data-lucide="user-check" class="w-4 h-4 text-slate-400"></i> Username Baru/Tetap
                        </label>
                        <input type="text" name="username" required value="<?= htmlspecialchars($admin_profile['username']) ?>" class="w-full border border-slate-300 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 bg-white text-slate-700 text-sm">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2 flex items-center gap-1.5">
                        <i data-lucide="lock" class="w-4 h-4 text-slate-400"></i> Kata Sandi Baru
                    </label>
                    <input type="password" name="password" placeholder="Kosongkan jika tidak ingin mengubah password" class="w-full border border-slate-300 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 bg-white text-slate-700 text-sm">
                    <p class="text-[10px] text-slate-400 mt-1.5">Password disimpan dengan enkripsi searah (Bcrypt) untuk keamanan.</p>
                </div>

                <div class="border-t border-slate-100 pt-5">
                    <h4 class="font-bold text-slate-700 text-sm mb-4">Profil Lembaga / Sekolah</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2 flex items-center gap-1.5">
                                <i data-lucide="graduation-cap" class="w-4 h-4 text-slate-400"></i> Nama Sekolah
                            </label>
                            <input type="text" name="nama_sekolah" required value="<?= htmlspecialchars($admin_profile['nama_sekolah']) ?>" class="w-full border border-slate-300 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 bg-white text-slate-700 text-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2 flex items-center gap-1.5">
                                <i data-lucide="image" class="w-4 h-4 text-slate-400"></i> Unggah Logo Sekolah
                            </label>
                            <input type="file" name="logo_sekolah" accept="image/png, image/jpeg, image/jpg" class="w-full text-slate-500 text-sm file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100 cursor-pointer">
                            <p class="text-[10px] text-slate-400 mt-1.5">Format didukung: PNG, JPG, JPEG (Max. 2MB)</p>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end pt-4 border-t border-slate-100">
                    <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white px-6 py-2.5 rounded-xl font-bold flex items-center transition-colors shadow-md shadow-brand-500/20">
                        <i data-lucide="save" class="w-4 h-4 mr-2"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Preview Logo Sekolah -->
    <div class="col-span-1">
        <div class="card p-6 flex flex-col items-center justify-center text-center space-y-4">
            <h4 class="font-bold text-slate-700 text-sm w-full text-left border-b pb-2">Pratinjau Logo</h4>
            <?php if (!empty($admin_profile['logo_sekolah']) && file_exists($admin_profile['logo_sekolah'])): ?>
                <img src="<?= $admin_profile['logo_sekolah'] ?>" class="w-32 h-32 object-contain rounded-xl p-2 bg-slate-50 border border-slate-150 shadow-sm" alt="Logo Sekolah">
                <div>
                    <p class="text-sm font-bold text-slate-800"><?= htmlspecialchars($admin_profile['nama_sekolah']) ?></p>
                    <p class="text-xs text-slate-500 mt-1">Logo Sekolah Aktif</p>
                </div>
            <?php else: ?>
                <div class="w-32 h-32 bg-slate-50 rounded-xl flex items-center justify-center border border-dashed border-slate-300">
                    <i data-lucide="graduation-cap" class="text-slate-400 w-12 h-12"></i>
                </div>
                <div>
                    <p class="text-xs text-slate-400 italic">Belum ada logo diunggah. Menampilkan logo default.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    document.getElementById('page-title').innerText = 'Profil Sekolah & Admin';
</script>
