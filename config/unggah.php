<?php
/**
 * Validasi isi berkas unggahan berdasarkan konten sesungguhnya (MIME/magic byte),
 * bukan hanya nama/ekstensi berkas yang mudah dipalsukan.
 */

if (!function_exists('berkas_valid_gambar')) {
    /**
     * Validasi unggahan logo. Mengembalikan:
     *   ['ok' => bool, 'error' => ?string, 'ekstensi' => ?string]
     * Ekstensi yang dikembalikan diturunkan dari MIME asli, bukan dari nama
     * berkas kiriman klien, supaya nama berkas tersimpan selalu konsisten
     * dengan isinya.
     */
    function berkas_valid_gambar(array $file, int $maxBytes = 2 * 1024 * 1024): array
    {
        $gagal = fn(string $pesan) => ['ok' => false, 'error' => $pesan, 'ekstensi' => null];

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return $gagal('Ada masalah saat mengunggah file logo.');
        }
        if (($file['size'] ?? 0) > $maxBytes) {
            return $gagal('Ukuran file logo maksimal 2MB.');
        }

        $mimeKeEkstensi = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
        ];

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!isset($mimeKeEkstensi[$mime])) {
            return $gagal('Jenis file tidak dikenali sebagai gambar JPG/PNG yang sah.');
        }

        // Konfirmasi tambahan: isi berkas benar-benar bisa didekode sebagai gambar raster.
        if (@getimagesize($file['tmp_name']) === false) {
            return $gagal('File bukan gambar yang valid.');
        }

        return ['ok' => true, 'error' => null, 'ekstensi' => $mimeKeEkstensi[$mime]];
    }
}

if (!function_exists('berkas_valid_xlsx')) {
    /**
     * Validasi unggahan Excel (.xlsx) sebelum diserahkan ke IOFactory::load().
     * .xlsx adalah kontainer ZIP, sehingga finfo_file() kadang melaporkan
     * "application/zip" generik, bukan MIME spreadsheet spesifik, tergantung
     * basis data magic number di sistem. Karena itu allow-list menerima
     * keduanya, dengan byte magic ZIP (PK\x03\x04) sebagai penjaga utama.
     */
    function berkas_valid_xlsx(array $file, int $maxBytes = 5 * 1024 * 1024): array
    {
        $gagal = fn(string $pesan) => ['ok' => false, 'error' => $pesan];

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return $gagal('Gagal mengunggah file.');
        }
        if (($file['size'] ?? 0) > $maxBytes) {
            return $gagal('Ukuran file Excel maksimal 5MB.');
        }

        $mimeDiizinkan = ['application/zip', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $mimeDiizinkan, true)) {
            return $gagal('File bukan berformat .xlsx yang sah.');
        }

        $pembuka = fopen($file['tmp_name'], 'rb');
        $magic   = $pembuka ? fread($pembuka, 4) : '';
        if ($pembuka) {
            fclose($pembuka);
        }
        if ($magic !== "PK\x03\x04") {
            return $gagal('File bukan berformat .xlsx yang sah.');
        }

        return ['ok' => true, 'error' => null];
    }
}
