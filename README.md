# PakarBK — Sistem Pakar Bimbingan & Konseling

Aplikasi web untuk membantu Guru BK mengidentifikasi kecenderungan tipe
kepribadian siswa (**DISC**) berdasarkan indikator yang dijawab, memakai metode
**Certainty Factor** dan **Backward Chaining**.

Dibangun dengan **PHP native + MySQL/MariaDB**, tanpa framework.

> **Status:** seluruh alur sudah berjalan penuh &mdash; basis pengetahuan, mesin
> perhitungan, tanya-jawab konsultasi, halaman hasil, hingga ekspor rincian
> perhitungan ke Excel. Keluaran aplikasi telah dicocokkan dengan angka acuan
> penelitian dan sama persis sampai lima angka di belakang koma. Lapisan keamanan
> (peran akun, validasi unggahan, jejak audit) sudah terpasang — rinciannya di
> bagian [Keamanan](#keamanan).
>
> Yang masih menunggu data dari pihak sekolah: teks rekomendasi final tiap tipe
> kepribadian, hasil validasi bobot oleh pakar BK, data siswa sebenarnya, dan
> logo sekolah. Semuanya dapat diisi lewat antarmuka tanpa mengubah kode.

---

## Fitur

- **Autentikasi & Peran** — login/logout dengan password ter-hash (bcrypt), dua peran akun
  (**Pakar BK/Admin**: akses penuh termasuk master data; **Guru BK**: konsultasi & data
  siswa saja), wajib ganti password pada login pertama
- **Manajemen Siswa** — CRUD, pencarian, filter kelas, paginasi, **impor & ekspor Excel**
- **Tipe Kepribadian** — empat hipotesis DISC beserta deskripsi dan rekomendasi layanan BK
- **Indikator** — 16 pernyataan swalapor yang menjadi dasar penilaian
- **Aturan Pakar** — relasi tipe ↔ indikator beserta nilai keyakinan pakar, **dapat diubah
  langsung dari antarmuka** sehingga hasil validasi pakar tidak memerlukan perubahan kode
- **Mesin Certainty Factor** — perhitungan berikut rincian langkahnya, dilengkapi berkas uji
- **Konsultasi Interaktif** — pernyataan ditampilkan satu per satu dengan lima tingkat
  keyakinan, disertai log penelusuran *backward chaining* yang tampil langsung di layar
- **Hasil Analisis** — peringkat keempat tipe beserta rincian perhitungan tiap langkah,
  bukan satu angka tunggal
- **Ekspor Perhitungan** — unduh seluruh tahapan sebagai berkas `.xlsx` berformat
- **Riwayat** — pencarian, paginasi, tautan ke hasil lengkap, hapus satuan maupun massal
- **Dashboard** — statistik ringkas, distribusi tipe kepribadian, dan sebarannya per kelas
- **Profil Sekolah** — ubah nama sekolah, unggah logo, ganti kredensial

### Keamanan

- **Token CSRF** pada seluruh formulir; penghapusan hanya menerima POST bertoken
- **Sesi** diperbarui (`session_regenerate_id`) setelah berhasil masuk
- **Percobaan masuk dibatasi** — terkunci sementara setelah beberapa kali gagal
- **Gerbang peran** di sisi server (`role_wajib`), bukan sekadar menyembunyikan menu —
  navigasi langsung ke URL halaman master data tetap ditolak untuk peran `guru_bk`
- **Validasi berkas unggahan** berdasarkan isi berkas sungguhan (MIME + *magic byte*),
  bukan ekstensi nama berkas yang gampang dipalsukan. Berlaku untuk logo sekolah
  maupun impor data siswa
- **Folder `uploads/` dikunci** lewat `.htaccess` — apa pun yang tersimpan di sana
  tidak pernah dieksekusi sebagai PHP
- **Jejak audit ringan** — kolom `id_admin`, `created_at`, dan `updated_at` pada
  keempat tabel master mencatat siapa mengubah apa dan kapan
- **Kredensial tidak ter-commit** — pengaturan database dan password bawaan berada
  di `config/local.php` yang di-`.gitignore`

---

## Kebutuhan Sistem

| Komponen | Versi | Catatan |
|---|---|---|
| PHP | **8.2** atau lebih baru | |
| MySQL / MariaDB | MySQL 8.0+ atau MariaDB 10.4+ | Keduanya didukung |
| Composer | 2.x | Untuk memasang dependency |
| Ekstensi PHP | `pdo_mysql`, `mbstring`, **`zip`**, **`gd`**, `fileinfo`, `dom`, `simplexml`, `xml`, `xmlreader`, `xmlwriter`, `iconv`, `zlib`, `ctype`, `filter`, `libxml` | `zip` dan `gd` sering **nonaktif** secara bawaan di XAMPP |

Satu-satunya dependency eksternal adalah [PhpSpreadsheet](https://github.com/PHPOffice/PhpSpreadsheet)
untuk fitur impor/ekspor Excel.

---

## Instalasi

### 1. Klon repository

```bash
git clone https://github.com/izzmiaulia/SistemPakarWinda.git
cd SistemPakarWinda
```

### 2. Aktifkan ekstensi PHP yang diperlukan

Buka `php.ini` (di XAMPP: `C:\xampp\php\php.ini`) dan hilangkan tanda `;` di depan
dua baris berikut:

```ini
extension=gd
extension=zip
```

Simpan, lalu restart Apache bila sedang berjalan.

> **Cara memastikan sudah aktif:**
> ```bash
> php -r "var_dump(extension_loaded('zip'), extension_loaded('gd'));"
> ```
> Keduanya harus `bool(true)`.

### 3. Pasang dependency

```bash
composer install
```

### 4. Siapkan database

Pastikan MySQL/MariaDB sudah berjalan, lalu buat database:

```bash
mysql -u root -e "CREATE DATABASE IF NOT EXISTS db_pakar_bk CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
```

Impor skema beserta data contohnya:

```bash
mysql -u root db_pakar_bk < db_pakar_bk.sql
```

### 5. Sesuaikan koneksi database (opsional)

Kredensial database dan password admin bawaan diatur lewat `config/local.php`
(gitignored, tidak ikut ter-commit). Kalau tidak dibuat, aplikasi memakai nilai
bawaan XAMPP (`root`, tanpa password) dan otomatis tetap bisa jalan.

Untuk mengubahnya, salin templatenya lalu sesuaikan:

```bash
cp config/local.example.php config/local.php
```

```php
return [
    'db_host' => 'localhost',
    'db_user' => 'root',
    'db_pass' => '',
    'db_name' => 'db_pakar_bk',
    'default_admin_password' => 'GantiSegera#2026',
];
```

---

## Menjalankan

### Server bawaan PHP (paling praktis)

```bash
php -S localhost:8000
```

Buka **http://localhost:8000**

### Lewat Apache (XAMPP)

Letakkan folder proyek di dalam `htdocs`, lalu akses
`http://localhost/SistemPakarWinda`.

---

## Login Awal

```
Username : gurubk
```

Password bawaan **tidak** dicantumkan di sini maupun di berkas mana pun yang ter-commit.
Ia ditentukan oleh `default_admin_password` di `config/local.php` (salin dari
[`config/local.example.php`](config/local.example.php) — berkas ini sudah
di-`.gitignore`). Kalau `config/local.php` tidak dibuat, aplikasi memakai nilai
bawaan yang tertulis di `config/local.example.php`.

Akun ini **wajib mengganti password pada login pertama** — sistem otomatis
mengarahkan ke halaman Profil sampai penggantian selesai, tidak bisa dilewati.

Bila tabel `admin` kosong, aplikasi akan membuat akun bawaan ini secara otomatis
saat pertama kali dijalankan (lihat [`index.php`](index.php)), dengan peran
`pakar` (akses penuh).

---

## Struktur Proyek

```
SistemPakarWinda/
├── index.php               Front controller — sesi, autoload, routing, render layout
├── download_perhitungan.php  Ekspor rincian perhitungan ke .xlsx
├── db_pakar_bk.sql         Skema database + basis pengetahuan (pemasangan baru)
├── migration_role_password_akun.sql  ALTER inkremental untuk DB yang sudah ada
├── composer.json
│
├── config/
│   ├── koneksi.php         Autoloader sederhana + inisialisasi koneksi
│   ├── keamanan.php        Helper token CSRF + gerbang peran (role_wajib)
│   ├── unggah.php          Validasi berkas unggahan (MIME/magic byte)
│   └── local.example.php   Template config/local.php (kredensial DB, password bawaan)
│
├── models/                 Lapisan akses data (repository)
│   ├── Database.php        Koneksi PDO, baca config/local.php bila ada
│   ├── Siswa.php
│   ├── Kepribadian.php     Hipotesis H01–H04 beserta rule R01–R04
│   ├── Gejala.php          Indikator G01–G16
│   ├── Aturan.php          Relasi tipe ↔ indikator + nilai CF pakar
│   ├── Konsultasi.php      Simpan, riwayat, detail, statistik
│   └── MesinInferensiCF.php  Mesin perhitungan Certainty Factor
│
├── tests/
│   └── test_cf.php         Uji mesin terhadap angka acuan penelitian
│
├── uploads/
│   └── .htaccess           Kunci eksekusi PHP di folder unggahan
│
├── layouts/                header · sidebar · footer
│
└── pages/
    ├── dashboard.php
    ├── login.php · logout.php · profil.php
    ├── master/             siswa · kepribadian · gejala · aturan
    └── konsultasi/         mulai · proses · hasil · riwayat
```

Isi folder `uploads/` (logo yang diunggah) tidak ikut ter-commit — hanya berkas
`.htaccess` penguncinya yang disertakan.

**Routing** memakai daftar putih (*whitelist*) di `index.php`. Halaman diakses lewat
parameter `?page=`, misalnya `index.php?page=dashboard`. Tiga lapis penjagaan
dijalankan berurutan sebelum halaman dimuat:

1. **Sesi** — semua halaman selain `login` memerlukan sesi aktif
2. **Wajib ganti password** — selama `harus_ganti_password` masih menyala, seluruh
   halaman selain `login`/`logout`/`profil` dialihkan paksa ke halaman Profil
3. **Peran** — `role_wajib('pakar')` di awal `kepribadian.php`, `gejala.php`, dan
   `aturan.php`, dipanggil **sebelum** `csrf_wajib()` karena otorisasi juga berlaku
   untuk permintaan GET, bukan hanya POST

---

## Skema Database

```
admin                 Akun pengguna + identitas sekolah + peran (guru_bk/pakar)
siswa                 Data peserta didik + id_admin/created_at/updated_at

kepribadian           Hipotesis H01–H04 (tipe D/I/S/C) + rule R01–R04,
                      deskripsi, rekomendasi layanan BK, + id_admin/created_at/updated_at
gejala                16 indikator pernyataan (G01–G16) + id_admin/created_at/updated_at
aturan                Relasi kepribadian ↔ gejala + nilai CF pakar (0–1),
                      + id_admin/created_at/updated_at

riwayat_konsultasi    Sesi konsultasi per siswa
├── detail_konsultasi Jawaban atas SETIAP indikator + bobot pakar saat itu
├── hasil_konsultasi  Tipe dengan keyakinan tertinggi + log penelusuran
└── hasil_detail      Skor keempat tipe beserta peringkatnya
```

Seluruh tabel memakai InnoDB. *Foreign key* ke `siswa`/`kepribadian`/`gejala` memakai
`ON DELETE CASCADE` (menghapus satu siswa ikut membersihkan riwayat konsultasinya).
*Foreign key* `id_admin` (kolom audit ringan: siapa & kapan) sengaja memakai
`ON DELETE SET NULL` — menghapus akun admin tidak boleh ikut menghapus data siswa
atau basis pengetahuan.

Collation yang dipakai adalah `utf8mb4_general_ci` agar dapat dipasang baik pada
MariaDB maupun MySQL 8.

Kolom `cf_pakar` pada `detail_konsultasi` menyimpan bobot pakar **pada saat
konsultasi dijalankan**, sehingga rincian perhitungan sebuah sesi tetap dapat
direproduksi persis walaupun bobot pada tabel `aturan` diubah setelahnya.

---

## Metode

**Backward Chaining** menentukan arah penalaran — sistem berangkat dari hipotesis,
lalu menelusuri mundur ke aturan dan indikator pendukungnya, bukan sebaliknya.

**Certainty Factor** menghitung tingkat keyakinan dari indikator yang terpenuhi:

```
CF Evidence  = CF pakar × CF pengguna
Kombinasi    = CF₁ + CF₂ × (1 − CF₁)
```

Skala keyakinan pengguna:

| Jawaban | Nilai |
|---|---|
| Tidak | 0.0 |
| Kurang Yakin | 0.4 |
| Cukup Yakin | 0.6 |
| Yakin | 0.8 |
| Sangat Yakin | 1.0 |

Setiap sesi konsultasi menyimpan log penelusuran, sehingga proses pengambilan
kesimpulan dapat ditinjau ulang dan tidak berupa kotak hitam.

### Basis pengetahuan

```
R01 : H01 Dominance  ← G01, G02, G03, G04
R02 : H02 Influence  ← G05, G06, G07, G08
R03 : H03 Steadiness ← G09, G10, G11, G12
R04 : H04 Compliance ← G13, G14, G15, G16
```

Nilai keyakinan pakar tersimpan pada tabel `aturan` dan dapat diubah melalui menu
**Aturan Pakar** tanpa menyentuh kode.

### Menguji mesin perhitungan

```bash
php tests/test_cf.php
```

Berkas uji mencocokkan keluaran mesin dengan angka acuan penelitian sampai lima
angka di belakang koma, mencakup CF evidence tiap indikator, kombinasi bertahap,
CF akhir keempat hipotesis, penentuan peringkat, dan kasus tepi.

---

## Pemecahan Masalah

<details>
<summary><b>ERROR 1273 (HY000): Unknown collation: 'utf8mb4_0900_ai_ci'</b></summary>

Terjadi bila memakai MariaDB, karena `utf8mb4_0900_ai_ci` hanya ada di MySQL 8.

Repository ini sudah memakai `utf8mb4_general_ci` yang didukung keduanya. Bila
error ini masih muncul, kemungkinan Anda memakai salinan lama dari berkas `.sql`.

</details>

<details>
<summary><b>Your lock file does not contain a compatible set of packages</b></summary>

Muncul bila versi PHP Anda lebih rendah daripada yang dipakai saat `composer.lock`
dibuat. Sesuaikan `config.platform.php` di `composer.json` dengan versi PHP Anda,
lalu jalankan:

```bash
composer update
```

</details>

<details>
<summary><b>Class "ZipArchive" not found — atau ekspor Excel gagal</b></summary>

Ekstensi `zip` (dan `gd`) belum aktif. Ikuti kembali **Instalasi langkah 2**.

</details>

<details>
<summary><b>Koneksi Database OOP Gagal</b></summary>

Pastikan layanan MySQL/MariaDB sudah berjalan, database `db_pakar_bk` sudah dibuat,
dan kredensial di `config/local.php` (lihat `config/local.example.php`) sesuai
dengan konfigurasi server Anda. Kalau `config/local.php` belum dibuat, aplikasi
memakai nilai bawaan XAMPP (`root`, tanpa password).

</details>

<details>
<summary><b>Halaman tampil tanpa gaya (CSS tidak termuat)</b></summary>

Aplikasi memuat Tailwind, Lucide, SweetAlert2, dan Tom-Select melalui CDN, sehingga
memerlukan koneksi internet saat dijalankan.

</details>

<details>
<summary><b>Peringatan di console: "cdn.tailwindcss.com should not be used in production"</b></summary>

**Ini normal dan memang sengaja dibiarkan tampil.**

Tailwind dimuat lewat CDN, belum melalui proses *build* lokal. Peringatan itu adalah
pemberitahuan resmi dari Tailwind bahwa cara ini tidak dianjurkan untuk lingkungan
produksi sungguhan.

Kode sebelumnya menimpa `console.warn()` untuk menyaring pesan tersebut agar tidak
muncul. Cara itu sudah dibuang — membungkam peringatan tidak menghilangkan
keterbatasannya, hanya menyembunyikannya dari orang yang perlu tahu.

Untuk skala pemakaian aplikasi ini (satu sekolah, dijalankan lokal), CDN masih
memadai. Bila nanti dipasang di server produksi, Tailwind sebaiknya dipasang lewat
Tailwind CLI atau PostCSS.

</details>

---
