<?php
/**
 * Database Connection Configuration (PDO)
 * TechPulse IMS - Pertemuan 4: Relational Database & Advanced PHP
 * Author: Ainur Maida Syahri
 */

// Konfigurasi Database TechPulse (dapat disesuaikan untuk XAMPP lokal maupun hosting)
$db_host = getenv('DB_HOST') ?: '127.0.0.1';
$db_port = getenv('DB_PORT') ?: '3306';
$db_name = getenv('DB_NAME') ?: 'inventory_techpulse_p4';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false, // Native prepared statements
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
];

try {
    $dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (PDOException $e) {
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Database Connection Error - TechPulse</title>
        <style>
            body { background: #07090e; color: #f8fafc; font-family: 'Plus Jakarta Sans', Segoe UI, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 20px; box-sizing: border-box; }
            .error-card { background: #0e1526; border: 1px solid #ef4444; border-radius: 12px; max-width: 650px; width: 100%; padding: 32px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
            h2 { color: #f87171; margin-top: 0; display: flex; align-items: center; gap: 10px; }
            p { line-height: 1.6; color: #cbd5e1; }
            code { background: #07090e; color: #38bdf8; padding: 3px 8px; border-radius: 4px; font-size: 0.9em; }
            .steps { background: #07090e; padding: 16px 20px; border-radius: 8px; margin: 16px 0; border-left: 4px solid #06b6d4; }
            .steps ol { margin: 0; padding-left: 20px; }
            .steps li { margin-bottom: 8px; }
            .error-detail { font-size: 0.85em; color: #94a3b8; background: #07090e; padding: 12px; border-radius: 6px; word-break: break-all; margin-top: 16px; border: 1px dashed #334155; }
        </style>
    </head>
    <body>
        <div class="error-card">
            <h2>⚠️ Gagal Terhubung ke Database TechPulse</h2>
            <p>Aplikasi <strong>TechPulse IMS</strong> tidak dapat tersambung ke database MySQL <code><?= htmlspecialchars($db_name) ?></code>.</p>
            <div class="steps">
                <strong>Langkah perbaikan:</strong>
                <ol>
                    <li>Buka <strong>XAMPP Control Panel</strong> dan klik tombol <strong>Start</strong> pada modul <strong>MySQL</strong>.</li>
                    <li>Pastikan database <code>inventory_techpulse_p4</code> sudah diimpor dari file <code>database/schema.sql</code>.</li>
                </ol>
            </div>
            <div class="error-detail">
                <strong>Pesan Kesalahan Teknis:</strong><br>
                <?= htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'); ?>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

/**
 * Mendapatkan koneksi PDO aktif
 */
function get_db() {
    global $pdo;
    return $pdo;
}
