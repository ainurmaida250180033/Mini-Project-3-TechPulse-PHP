<?php
/**
 * 403 Forbidden Error Page
 * Memenuhi pengujian Test Case Hak Akses Terlarang (Slide 24 & Slide 38)
 */
http_response_code(403);
$public_page = true;
$page_title = '403 Akses Ditolak';

require_once __DIR__ . '/helpers/auth.php';
require_once __DIR__ . '/helpers/escape.php';

$user = current_user();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 Forbidden — Akses Ditolak</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🚫</text></svg>">
</head>
<body style="background: #090d16; display: flex; align-items: center; justify-content: center; min-height: 100vh;">
<div style="max-width: 580px; width: 90%; background: #111726; border: 1px solid #ef4444; border-radius: 12px; padding: 36px; box-shadow: 0 15px 35px rgba(0,0,0,0.6); text-align: center;">
    <div style="font-size: 3.5rem; margin-bottom: 12px;">🚫</div>
    <h1 style="color: #f87171; font-size: 2.2rem; margin-bottom: 8px;">403 FORBIDDEN</h1>
    <h3 style="color: #fff; margin-bottom: 16px;">Otorisasi Gagal: Akses Dibatasi</h3>

    <p style="color: #cbd5e1; line-height: 1.6; margin-bottom: 20px; font-size: 0.95rem;">
        Halaman atau tindakan ini memerlukan hak akses tingkat <strong>[ADMIN]</strong>.<br>
        Role Anda saat ini adalah: <span class="badge badge-warning"><?= strtoupper(e($user['role'] ?? 'GUEST')) ?></span>
    </p>

    <div style="background: #0a0e1a; padding: 16px; border-radius: 8px; border-left: 4px solid #ef4444; text-align: left; margin-bottom: 24px; font-size: 0.85rem; color: #94a3b8;">
        <strong style="color: #f87171;">Penjelasan Aturan Bisnis (Slide 24):</strong><br>
        Pengguna dengan role <em>Staff</em> hanya berhak melihat daftar produk serta mencatat transaksi barang masuk dan keluar. Administrasi akun user dan pengelolaan struktural dibatasi ketat di tingkat server.
    </div>

    <div style="display: flex; gap: 12px; justify-content: center;">
        <a href="dashboard.php" class="btn btn-primary">
            ⬅️ Kembali ke Dashboard
        </a>
        <a href="logout.php" class="btn btn-secondary">
            🚪 Login dengan Akun Lain
        </a>
    </div>
</div>
</body>
</html>
