<?php
/**
 * Authentication & Authorization Helper
 * Mengatur sesi login, verifikasi role (RBAC), dan proteksi halaman
 * Sesuai panduan Slide 22, 23, dan 24
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Cek apakah pengguna saat ini sudah login
 */
function is_logged_in() {
    return isset($_SESSION['user']) && !empty($_SESSION['user']['id']);
}

/**
 * Ambil data pengguna yang sedang login saat ini
 */
function current_user() {
    return is_logged_in() ? $_SESSION['user'] : null;
}

/**
 * Cek apakah pengguna memiliki role admin
 */
function is_admin() {
    return is_logged_in() && ($_SESSION['user']['role'] === 'admin');
}

/**
 * Cek apakah pengguna memiliki role staff
 */
function is_staff() {
    return is_logged_in() && ($_SESSION['user']['role'] === 'staff');
}

/**
 * Batasi akses: Hanya pengguna yang sudah login
 * Jika belum, redirect ke login.php
 */
function require_login() {
    if (!is_logged_in()) {
        $_SESSION['flash'] = [
            'type' => 'warning',
            'message' => 'Silakan login terlebih dahulu untuk mengakses sistem.'
        ];
        header('Location: login.php');
        exit;
    }
}

/**
 * Batasi akses: Hanya role Admin
 * Sesuai Slide 24: Basic Authorization (Staff tidak boleh mengakses modul ini)
 */
function require_admin() {
    require_login();
    if (!is_admin()) {
        http_response_code(403);
        // Arahkan ke halaman forbidden untuk pengujian test case
        header('Location: forbidden.php');
        exit;
    }
}

/**
 * Set flash message (notifikasi satu kali tayang)
 */
function set_flash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message
    ];
}

/**
 * Ambil flash message lalu hapus dari sesi
 */
function get_flash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}
