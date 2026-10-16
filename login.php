<?php
/**
 * Login Authentication Page
 * Mengimplementasikan Prepared Statement, Password Hashing, dan Session Regeneration
 * Sesuai panduan Slide 18, 22, dan 23
 */
$public_page = true;
$page_title = 'Login Masuk Sistem';

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/escape.php';
require_once __DIR__ . '/helpers/auth.php';

// Jika sudah login, langsung alihkan ke dashboard
if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error = null;
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi!';
    } else {
        // Prepared statement untuk mencegah SQL Injection (Slide 18 & 19)
        $stmt = $pdo->prepare('SELECT id, username, password_hash, full_name, role FROM users WHERE username = :username LIMIT 1');
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();

        // Verifikasi password hash (Slide 23)
        if ($user && password_verify($password, $user['password_hash'])) {
            // Regenerasi ID sesi untuk mencegah Session Fixation (Slide 22)
            session_regenerate_id(true);

            // Simpan identitas pengguna ke sesi
            $_SESSION['user'] = [
                'id'        => (int)$user['id'],
                'username'  => $user['username'],
                'full_name' => $user['full_name'],
                'role'      => $user['role']
            ];

            set_flash('success', "Selamat datang kembali, {$user['full_name']}! Sesi aktif sebagai [" . strtoupper($user['role']) . "].");
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Username atau password yang Anda masukkan salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?> — TechPulse IMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>⚡</text></svg>">
</head>
<body style="background: radial-gradient(circle at 50% 30%, #152238 0%, #060911 100%);">
<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <div style="font-size: 2.8rem; margin-bottom: 8px;">⚡</div>
            <h1>TECH<span class="accent" style="color: var(--cyan-neon);">PULSE</span></h1>
            <p>PC Hardware & Gaming Gear Logistics IMS</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger" style="margin-bottom: 20px;">
                <span>⚠️</span>
                <div><?= e($error) ?></div>
            </div>
        <?php endif; ?>

        <?php
        $flash = get_flash();
        if ($flash):
        ?>
            <div class="alert alert-<?= e($flash['type']) ?>" style="margin-bottom: 20px;">
                <div><?= e($flash['message']) ?></div>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label class="form-label" for="username">Username Pengguna</label>
                <input type="text" id="username" name="username" class="form-control" 
                       value="<?= e($username) ?>" placeholder="Contoh: admin atau staff" required autofocus>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" 
                       placeholder="Masukkan password Anda" required>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; margin-top: 10px;">
                🔐 Autentikasi Masuk
            </button>
        </form>

        <div class="demo-account-box">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <span style="font-weight: 700; color: #fff;">Akun Demo Pengujian:</span>
                <span style="font-size: 0.75rem; color: var(--text-dim);">Klik untuk isi otomatis</span>
            </div>
            <div style="display: flex; gap: 8px; margin-bottom: 8px;">
                <button type="button" class="btn btn-secondary btn-sm" style="flex: 1;" onclick="fillDemo('admin', 'admin123')">
                    👑 Admin (Full Access)
                </button>
                <button type="button" class="btn btn-secondary btn-sm" style="flex: 1;" onclick="fillDemo('staff', 'staff123')">
                    🛠️ Staff (Operator)
                </button>
            </div>
            <div style="font-size: 0.78rem; line-height: 1.4;">
                • <strong>admin</strong> / <code>admin123</code> (Kelola User, Master Data, Transaksi)<br>
                • <strong>staff</strong> / <code>staff123</code> (Lihat Produk & Catat Transaksi Masuk/Keluar)
            </div>
        </div>
    </div>
</div>

<script>
function fillDemo(user, pass) {
    document.getElementById('username').value = user;
    document.getElementById('password').value = pass;
}
</script>
</body>
</html>
