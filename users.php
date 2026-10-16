<?php
/**
 * User & Role Management (Admin Only)
 * Mengimplementasikan RBAC, Password Hashing, dan Relasi One-to-One dengan user_profiles
 * Sesuai panduan Slide 06, 23, 24, dan 33
 */
$page_title = 'Kelola Akun Pengguna & Hak Akses (RBAC)';
require_once __DIR__ . '/views/header.php';

// Proteksi tingkat server: Hanya Admin yang diizinkan mengakses modul ini
// Jika role pengguna adalah 'staff', sistem akan me-redirect ke forbidden.php dengan HTTP 403
require_admin();

$errors = [];
$action = $_GET['action'] ?? 'list';
$edit_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// Tambah Pengguna Baru
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username   = strtolower(trim($_POST['username'] ?? ''));
    $password   = $_POST['password'] ?? '';
    $full_name  = trim($_POST['full_name'] ?? '');
    $role       = trim($_POST['role'] ?? 'staff');
    $phone      = trim($_POST['phone'] ?? '');
    $department = trim($_POST['department'] ?? '');

    validate_required($username, 'Username', $errors);
    validate_required($password, 'Password', $errors);
    validate_required($full_name, 'Nama Lengkap', $errors);

    if (!in_array($role, ['admin', 'staff'], true)) {
        $errors[] = 'Role pengguna tidak valid.';
    }

    if (strlen($password) < 6) {
        $errors[] = 'Password minimal harus terdiri dari 6 karakter.';
    }

    if (empty($errors)) {
        // Cek duplikasi username
        $check = $pdo->prepare("SELECT id FROM users WHERE username = :username");
        $check->execute([':username' => $username]);
        if ($check->fetch()) {
            $errors[] = "Username '{$username}' sudah digunakan. Pilih username lain.";
        }
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Hash password dengan BCRYPT (Slide 23)
            $password_hash = password_hash($password, PASSWORD_BCRYPT);

            // 1. Simpan ke tabel users
            $stmt = $pdo->prepare("
                INSERT INTO users (username, password_hash, full_name, role)
                VALUES (:username, :hash, :full_name, :role)
            ");
            $stmt->execute([
                ':username'  => $username,
                ':hash'      => $password_hash,
                ':full_name' => $full_name,
                ':role'      => $role
            ]);
            $new_user_id = (int)$pdo->lastInsertId();

            // 2. Simpan ke tabel user_profiles (Relasi One-to-One: Slide 06)
            $prof_stmt = $pdo->prepare("
                INSERT INTO user_profiles (user_id, phone, department, bio)
                VALUES (:user_id, :phone, :dept, :bio)
            ");
            $prof_stmt->execute([
                ':user_id' => $new_user_id,
                ':phone'   => $phone ?: null,
                ':dept'    => $department ?: null,
                ':bio'     => "Akun terdaftar sebagai role {$role}."
            ]);

            $pdo->commit();
            set_flash('success', "Pengguna baru [{$username}] dengan role [{$role}] berhasil didaftarkan.");
            header('Location: users.php');
            exit;
        } catch (Throwable $e) {
            $pdo->rollBack();
            $errors[] = "Gagal mendaftarkan user: " . $e->getMessage();
        }
    }
}

// Hapus Pengguna
if ($action === 'delete' && $edit_id) {
    if ($edit_id === (int)$current_user['id']) {
        set_flash('danger', 'Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif digunakan.');
    } else {
        // Cek apakah user memiliki riwayat transaksi di stock_movements (Integritas Referensial)
        $chk_move = $pdo->prepare("SELECT COUNT(*) AS total FROM stock_movements WHERE user_id = :id");
        $chk_move->execute([':id' => $edit_id]);
        $has_movements = (int)$chk_move->fetch()['total'];

        if ($has_movements > 0) {
            set_flash('danger', "User tidak dapat dihapus karena tercatat sebagai operator dalam {$has_movements} transaksi mutasi stok (Aturan RESTRICT).");
        } else {
            $del = $pdo->prepare("DELETE FROM users WHERE id = :id");
            $del->execute([':id' => $edit_id]);
            set_flash('success', "User berhasil dihapus.");
        }
    }
    header('Location: users.php');
    exit;
}

// Ambil semua pengguna beserta relasi 1-to-1 profilnya (JOIN user_profiles - Slide 06)
$users = $pdo->query("
    SELECT u.id, u.username, u.full_name, u.role, u.created_at,
           up.phone, up.department
    FROM users u
    LEFT JOIN user_profiles up ON up.user_id = u.id
    ORDER BY u.id ASC
")->fetchAll();
?>

<div class="page-header">
    <div>
        <h1 class="page-title" style="color: var(--amber-neon);">👥 Administrasi Pengguna & Role (RBAC)</h1>
        <div class="page-subtitle">Modul eksklusif Admin untuk pendaftaran akun, password hashing, dan pembagian hak akses</div>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul style="margin: 0; padding-left: 20px;">
            <?php foreach ($errors as $e): ?>
                <li><?= e($e) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="grid-cols-2">
    <!-- Form Tambah User Baru -->
    <div class="card">
        <div class="card-title">
            <span>➕ Daftarkan Akun Pengguna Baru</span>
        </div>

        <form action="users.php" method="POST">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                <div class="form-group">
                    <label class="form-label" for="username">Username Login *</label>
                    <input type="text" id="username" name="username" class="form-control" 
                           placeholder="Misal: ripper01" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password (Min. 6 Karakter) *</label>
                    <input type="password" id="password" name="password" class="form-control" 
                           placeholder="••••••••" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="full_name">Nama Lengkap & Gelar *</label>
                <input type="text" id="full_name" name="full_name" class="form-control" 
                       placeholder="Misal: Judy Alvarez (Hardware Specialist)" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="role">Hak Akses / Role Otorisasi (Slide 24) *</label>
                <select id="role" name="role" class="form-control" required>
                    <option value="staff">Staff (Operator: Kelola Transaksi & Lihat Produk)</option>
                    <option value="admin">Admin (Akses Penuh: Master Data, User & Laporan)</option>
                </select>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                <div class="form-group">
                    <label class="form-label" for="phone">Nomor Telepon (Profil)</label>
                    <input type="text" id="phone" name="phone" class="form-control mono" 
                           placeholder="+62-...">
                </div>

                <div class="form-group">
                    <label class="form-label" for="department">Departemen / Divisi (Profil)</label>
                    <input type="text" id="department" name="department" class="form-control" 
                           placeholder="Misal: Bio-Surgical Unit">
                </div>
            </div>

            <div style="background: rgba(245, 158, 11, 0.08); border-left: 3px solid var(--amber-neon); padding: 12px 14px; border-radius: 4px; font-size: 0.82rem; color: #fde68a; margin-bottom: 20px;">
                🔒 <strong>Keamanan:</strong> Password otomatis dienkripsi menggunakan algoritma <code>PASSWORD_BCRYPT</code> satu arah (Slide 23). Data profil disimpan terpisah di tabel <code>user_profiles</code> dengan relasi <em>One-to-One</em> (Slide 06).
            </div>

            <button type="submit" class="btn btn-primary" style="background: linear-gradient(135deg, #d97706, #f59e0b); color: #000;">
                💾 Daftarkan Pengguna
            </button>
        </form>
    </div>

    <!-- Tabel Daftar Pengguna -->
    <div class="card">
        <div class="card-title">
            <span>Daftar Pengguna Aktif</span>
            <span class="badge badge-purple"><?= count($users) ?> Akun</span>
        </div>

        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Username & Nama</th>
                        <th>Role Akses</th>
                        <th>Departemen</th>
                        <th style="text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td>
                                <strong><?= e($u['full_name']) ?></strong><br>
                                <small class="mono" style="color: var(--cyan-neon);">@<?= e($u['username']) ?></small>
                                <?php if ($u['id'] === (int)$current_user['id']): ?>
                                    <span class="badge badge-success" style="font-size: 0.65rem;">(Anda)</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($u['role'] === 'admin'): ?>
                                    <span class="badge badge-warning">👑 ADMIN</span>
                                <?php else: ?>
                                    <span class="badge badge-cyan">🛠️ STAFF</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small><?= e($u['department'] ?: '-') ?></small><br>
                                <small class="mono" style="color: var(--text-dim);"><?= e($u['phone'] ?: '') ?></small>
                            </td>
                            <td style="text-align: right;">
                                <?php if ($u['id'] !== (int)$current_user['id']): ?>
                                    <a href="users.php?action=delete&id=<?= $u['id'] ?>" class="btn btn-danger btn-sm" 
                                       onclick="return confirm('Hapus akun <?= e($u['username']) ?>?');">
                                        🗑️ Hapus
                                    </a>
                                <?php else: ?>
                                    <span style="font-size: 0.75rem; color: var(--text-dim);">Aktif</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/views/footer.php'; ?>
