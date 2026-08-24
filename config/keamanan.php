<?php
/**
 * Helper keamanan sederhana.
 *
 * Menyediakan token CSRF untuk seluruh formulir dan aksi yang mengubah data.
 * Token disimpan di sesi dan berlaku selama sesi berlangsung.
 */

if (!function_exists('csrf_token')) {
    /** Ambil token CSRF sesi berjalan, dibuat sekali saat pertama dibutuhkan. */
    function csrf_token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }
}

if (!function_exists('csrf_field')) {
    /** Cetak input tersembunyi berisi token CSRF. */
    function csrf_field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES) . '">';
    }
}

if (!function_exists('csrf_valid')) {
    /** Periksa token yang dikirim formulir. */
    function csrf_valid(): bool
    {
        $dikirim = $_POST['_csrf'] ?? '';
        return is_string($dikirim)
            && !empty($_SESSION['_csrf'])
            && hash_equals($_SESSION['_csrf'], $dikirim);
    }
}

if (!function_exists('csrf_wajib')) {
    /**
     * Hentikan pemrosesan bila token tidak sah.
     * Dipanggil di awal setiap penanganan POST yang mengubah data.
     */
    function csrf_wajib(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_valid()) {
            http_response_code(419);
            exit('Sesi tidak sah atau sudah berakhir. Muat ulang halaman lalu ulangi.');
        }
    }
}
