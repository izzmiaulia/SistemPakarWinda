# PakarBK — Sistem Pakar Bimbingan & Konseling

Aplikasi web untuk membantu Guru BK melakukan identifikasi awal terhadap siswa
berdasarkan indikator yang teramati, lengkap dengan rincian langkah perhitungan
dan laporan yang dapat diekspor ke Excel.

Dibangun dengan **PHP native + MySQL/MariaDB**, tanpa framework.

---

## Fitur

- **Autentikasi** — login/logout dengan password ter-hash (bcrypt)
- **Manajemen Siswa** — CRUD, pencarian, filter kelas, paginasi, **impor & ekspor Excel**
- **Basis Pengetahuan** — CRUD masalah, gejala, dan aturan pakar beserta nilai keyakinannya
- **Konsultasi Interaktif** — tanya-jawab indikator satu per satu, dengan penelusuran
  *backward chaining* dan *backtracking* otomatis
- **Hasil Analisis** — persentase keyakinan, tingkat keparahan, rekomendasi penanganan,
  dan **rincian langkah perhitungan** yang bisa ditelusuri
- **Ekspor Perhitungan** — unduh seluruh tahapan perhitungan sebagai berkas `.xlsx` berformat
- **Riwayat** — pencarian, paginasi, detail, hapus satuan maupun massal
- **Dashboard** — statistik ringkas, distribusi tingkat keparahan, dan tren masalah
- **Profil Sekolah** — ubah nama sekolah, unggah logo, ganti kredensial

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

### 5. Sesuaikan koneksi database

Kredensial diatur di [`models/Database.php`](models/Database.php). Nilai bawaannya
mengikuti pengaturan standar XAMPP:

```php
private $host   = "localhost";
private $user   = "root";
private $pass   = "";
private $dbname = "db_pakar_bk";
```

Ubah bila konfigurasi server Anda berbeda.

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
Password : gurubk123
```

> 🔐 **Segera ganti setelah login pertama** melalui menu **Pengaturan → Profil Sekolah**.
> Kredensial ini tercantum di berkas `db_pakar_bk.sql` yang ada di repository publik,
> sehingga **tidak boleh dipakai** pada pemasangan yang dapat diakses dari luar.

Bila tabel `admin` kosong, aplikasi akan membuat akun bawaan ini secara otomatis
saat pertama kali dijalankan (lihat [`index.php`](index.php)).

---

## Struktur Proyek

```
SistemPakarWinda/
├── index.php               Front controller — sesi, autoload, routing, render layout
├── download_excel.php      Ekspor tahapan perhitungan ke .xlsx
├── db_pakar_bk.sql         Skema database + data contoh
├── composer.json
│
├── config/
│   └── koneksi.php         Autoloader sederhana + inisialisasi koneksi
│
├── models/                 Lapisan akses data (repository)
│   ├── Database.php        Koneksi PDO
│   ├── Siswa.php
│   ├── Masalah.php         Hipotesis / goal
│   ├── Gejala.php          Evidence / indikator
│   ├── Aturan.php          Relasi masalah ↔ gejala + nilai keyakinan
│   ├── Konsultasi.php      Simpan, riwayat, detail, statistik
│   └── MesinInferensi.php  Mesin perhitungan
│
├── layouts/                header · sidebar · footer
│
└── pages/
    ├── dashboard.php
    ├── login.php · logout.php · profil.php
    ├── master/             siswa · masalah · gejala · aturan
    └── konsultasi/         mulai · proses · hasil · riwayat
```

**Routing** memakai daftar putih (*whitelist*) di `index.php`. Halaman diakses lewat
parameter `?page=`, misalnya `index.php?page=dashboard`. Semua halaman selain `login`
memerlukan sesi yang aktif.

---

## Skema Database

```
admin                 Akun pengguna + identitas sekolah
siswa                 Data peserta didik
masalah               Hipotesis yang dapat disimpulkan
gejala                Indikator yang ditanyakan
aturan                Relasi masalah ↔ gejala beserta nilai keyakinan pakar
riwayat_konsultasi    Sesi konsultasi per siswa
├── detail_konsultasi Indikator yang terpenuhi pada sesi tersebut
└── hasil_konsultasi  Hasil akhir + log penelusuran
```

Seluruh tabel memakai InnoDB dengan *foreign key* `ON DELETE CASCADE`, sehingga
menghapus satu siswa akan ikut membersihkan seluruh riwayat konsultasinya.

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
dan kredensial di `models/Database.php` sesuai dengan konfigurasi server Anda.

</details>

<details>
<summary><b>Halaman tampil tanpa gaya (CSS tidak termuat)</b></summary>

Aplikasi memuat Tailwind, Lucide, SweetAlert2, dan Tom-Select melalui CDN, sehingga
memerlukan koneksi internet saat dijalankan.

</details>

---
