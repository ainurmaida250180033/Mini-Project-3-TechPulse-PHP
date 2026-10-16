<?php
/**
 * Navigation Bar View
 * TechPulse IMS - Menyesuaikan menu berdasarkan Role (RBAC) pengguna: Admin vs Staff
 * Author: Ainur Maida Syahri
 */
$current_script = basename($_SERVER['PHP_SELF']);
?>
<header class="navbar">
    <div class="nav-container">
        <a href="dashboard.php" class="nav-brand">
            ⚡ TECH<span class="accent">PULSE</span> <small style="font-size: 0.65rem; color: var(--text-muted); font-weight: 600; border: 1px solid var(--border-accent); padding: 2px 7px; border-radius: 4px; letter-spacing: 0.5px;">HARDWARE IMS</small>
        </a>

        <nav class="nav-links">
            <a href="dashboard.php" class="<?= $current_script === 'dashboard.php' ? 'active' : '' ?>">
                📊 Dashboard
            </a>
            <a href="products.php" class="<?= in_array($current_script, ['products.php', 'product_create.php', 'product_edit.php', 'product_detail.php']) ? 'active' : '' ?>">
                💻 Komponen & Hardware
            </a>
            <a href="stock_in.php" class="<?= $current_script === 'stock_in.php' ? 'active' : '' ?>" style="color: #34d399;">
                📥 Stok Masuk
            </a>
            <a href="stock_out.php" class="<?= $current_script === 'stock_out.php' ? 'active' : '' ?>" style="color: #fb7185;">
                📤 Stok Keluar
            </a>
            <a href="stock_history.php" class="<?= $current_script === 'stock_history.php' ? 'active' : '' ?>">
                📜 Histori Logistik
            </a>
            <a href="reports.php" class="<?= $current_script === 'reports.php' ? 'active' : '' ?>">
                📈 Laporan Analitik
            </a>
            <a href="categories.php" class="<?= $current_script === 'categories.php' ? 'active' : '' ?>">
                🏷️ Kategori
            </a>
            <a href="suppliers.php" class="<?= $current_script === 'suppliers.php' ? 'active' : '' ?>">
                🏢 Vendor & Supplier
            </a>
            
            <?php if (is_admin()): ?>
                <a href="users.php" class="<?= $current_script === 'users.php' ? 'active' : '' ?>" style="color: var(--amber-neon);">
                    👥 Manajemen User
                </a>
            <?php endif; ?>
        </nav>

        <div class="nav-user">
            <div class="user-badge">
                <span style="font-weight: 600; color: #fff;"><?= e($current_user['username']) ?></span>
                <span class="role-tag <?= $current_user['role'] === 'admin' ? 'role-admin' : 'role-staff' ?>">
                    [<?= strtoupper(e($current_user['role'])) ?>]
                </span>
            </div>
            <a href="logout.php" class="btn btn-secondary btn-sm" onclick="return confirm('Apakah Anda yakin ingin logout?');" title="Logout dari sistem">
                🚪 Keluar
            </a>
        </div>
    </div>
</header>
