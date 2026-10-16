<?php
/**
 * Logout Script
 * Menghancurkan session dan mengarahkan kembali ke login
 */
require_once __DIR__ . '/helpers/auth.php';

// Kosongkan array sesi
$_SESSION = [];

// Hapus cookie sesi jika ada
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Hancurkan session
session_destroy();

// Mulai sesi baru khusus untuk pesan notifikasi
session_start();
set_flash('info', 'Anda telah berhasil keluar dari sesi.');
header('Location: login.php');
exit;
