<?php
/**
 * Dashboard & Inventory Summary View
 * Menghitung dan menampilkan statistik berbasis fungsi agregat SQL
 * Sesuai panduan Slide 14 & Slide 29
 */
$page_title = 'Dashboard Ringkasan Inventaris';
require_once __DIR__ . '/views/header.php';

// 1. Total Produk (COUNT)
$stmt_prod = $pdo->query("SELECT COUNT(id) AS total_products FROM products");
$total_products = (int)$stmt_prod->fetch()['total_products'];

// 2. Total Nilai Inventory (SUM(stock * price))
$stmt_val = $pdo->query("SELECT COALESCE(SUM(stock * price), 0) AS inventory_value FROM products");
$inventory_value = (float)$stmt_val->fetch()['inventory_value'];

// 3. Jumlah Produk Stok Rendah (stock <= minimum_stock)
$stmt_low = $pdo->query("SELECT COUNT(id) AS low_stock_count FROM products WHERE stock <= minimum_stock");
$low_stock_count = (int)$stmt_low->fetch()['low_stock_count'];

// 4. Total Kuantitas Barang Masuk (SUM(quantity) WHERE type = 'IN')
$stmt_in = $pdo->query("SELECT COALESCE(SUM(quantity), 0) AS total_in FROM stock_movements WHERE type = 'IN'");
$total_in = (int)$stmt_in->fetch()['total_in'];

// 5. Total Kuantitas Barang Keluar (SUM(quantity) WHERE type = 'OUT')
$stmt_out = $pdo->query("SELECT COALESCE(SUM(quantity), 0) AS total_out FROM stock_movements WHERE type = 'OUT'");
$total_out = (int)$stmt_out->fetch()['total_out'];

// 6. Data Produk Stok Rendah untuk tabel peringatan
$stmt_low_items = $pdo->query("
    SELECT p.id, p.sku, p.name, p.stock, p.minimum_stock, c.name AS category_name
    FROM products p
    JOIN categories c ON c.id = p.category_id
    WHERE p.stock <= p.minimum_stock
    ORDER BY p.stock ASC
    LIMIT 6
");
$low_items = $stmt_low_items->fetchAll();

// 7. Data 5 Transaksi Terakhir (stock movements)
$stmt_recent = $pdo->query("
    SELECT sm.id, sm.type, sm.quantity, sm.reference_no, sm.created_at,
           p.name AS product_name, p.sku,
           u.full_name AS operator_name
    FROM stock_movements sm
    JOIN products p ON p.id = sm.product_id
    JOIN users u ON u.id = sm.user_id
    ORDER BY sm.created_at DESC
    LIMIT 5
");
$recent_movements = $stmt_recent->fetchAll();
?>

<div class="hero-banner" style="margin-bottom: 1.5rem; background: #0f172a; border-radius: 12px; position: relative; overflow: hidden; border: 1px solid var(--border-color);">
    <img src="assets/banner.jpg" alt="TechPulse Hardware Banner" style="position: absolute; width: 100%; height: 100%; object-fit: cover; opacity: 0.28;">
    <div style="position: relative; z-index: 2; padding: 2rem 2.5rem; background: linear-gradient(90deg, rgba(7,9,14,0.95) 0%, rgba(7,9,14,0.6) 60%, transparent 100%);">
        <div style="display: inline-block; padding: 3px 10px; background: rgba(6,182,212,0.15); border: 1px solid var(--border-accent); border-radius: 20px; font-size: 0.78rem; font-weight: 700; color: var(--cyan-neon); margin-bottom: 8px;">
            ⚡ ENTERPRISE PC HARDWARE & LOGISTICS
        </div>
        <h1 style="font-size: 1.8rem; margin: 0 0 6px 0; color: #fff;">TechPulse Inventory Hub</h1>
        <p style="color: var(--text-muted); margin: 0; font-size: 0.95rem; max-width: 650px;">
            Sistem pergudangan dan kontrol logistik terintegrasi untuk prosesor, GPU, memori berkecepatan tinggi, dan periferal gaming enthusiast.
        </p>
    </div>
</div>

<div class="page-header">
    <div>
        <h2 class="page-title">📊 Metrik Ringkasan Inventori</h2>
        <div class="page-subtitle">Monitoring real-time nilai aset, level stok kritis, dan volume alur masuk-keluar</div>
    </div>
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <a href="stock_in.php" class="btn btn-primary" style="background: linear-gradient(135deg, #059669, #10b981); color: #fff;">
            📥 + Stok Masuk
        </a>
        <a href="stock_out.php" class="btn btn-primary" style="background: linear-gradient(135deg, #e11d48, #f43f5e); color: #fff;">
            📤 - Stok Keluar
        </a>
    </div>
</div>

<!-- 5 Widget Statistik Sesuai Slide 29 -->
<div class="grid-cols-4">
    <div class="stat-card">
        <div class="stat-info">
            <div class="stat-label">Total Produk Terdaftar</div>
            <div class="stat-value"><?= number_format($total_products) ?></div>
            <div style="font-size: 0.75rem; color: var(--text-dim); margin-top: 4px;">COUNT(products.id)</div>
        </div>
        <div class="stat-icon">📦</div>
    </div>

    <div class="stat-card">
        <div class="stat-info">
            <div class="stat-label">Total Nilai Inventory</div>
            <div class="stat-value" style="font-size: 1.45rem; color: #38bdf8;"><?= format_currency($inventory_value) ?></div>
            <div style="font-size: 0.75rem; color: var(--text-dim); margin-top: 4px;">SUM(stock &times; price)</div>
        </div>
        <div class="stat-icon">💰</div>
    </div>

    <div class="stat-card <?= $low_stock_count > 0 ? 'warning' : 'success' ?>">
        <div class="stat-info">
            <div class="stat-label">Produk Stok Rendah</div>
            <div class="stat-value" style="color: <?= $low_stock_count > 0 ? '#fbbf24' : '#34d399' ?>;">
                <?= number_format($low_stock_count) ?>
            </div>
            <div style="font-size: 0.75rem; color: var(--text-dim); margin-top: 4px;">stock &le; minimum_stock</div>
        </div>
        <div class="stat-icon">⚠️</div>
    </div>

    <div class="stat-card success">
        <div class="stat-info">
            <div class="stat-label">Akumulasi Masuk (IN)</div>
            <div class="stat-value" style="color: #34d399;">+<?= number_format($total_in) ?></div>
            <div style="font-size: 0.75rem; color: var(--text-dim); margin-top: 4px;">SUM(qty) WHERE type=IN</div>
        </div>
        <div class="stat-icon">📥</div>
    </div>
</div>

<!-- Section 2: Stok Kritis & Transaksi Terbaru -->
<div class="grid-cols-2">
    <!-- Tabel Peringatan Stok Kritis -->
    <div class="card">
        <div class="card-title">
            <span>🚨 Peringatan Stok Kritis (&le; Min)</span>
            <span class="badge badge-warning"><?= count($low_items) ?> Perlu Restock</span>
        </div>
        
        <?php if (empty($low_items)): ?>
            <p style="color: var(--text-muted); font-size: 0.9rem; padding: 12px 0;">Semua stok produk saat ini berada dalam kondisi aman.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>SKU & Produk</th>
                            <th>Stok / Min</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($low_items as $item): ?>
                            <tr>
                                <td>
                                    <strong><?= e($item['name']) ?></strong><br>
                                    <small class="mono" style="color: var(--cyan-neon);"><?= e($item['sku']) ?></small>
                                </td>
                                <td>
                                    <span style="font-weight: 700; color: #fb7185;"><?= $item['stock'] ?></span>
                                    <span style="color: var(--text-dim);">/ min <?= $item['minimum_stock'] ?></span>
                                </td>
                                <td>
                                    <?= render_stock_badge($item['stock'], $item['minimum_stock']) ?>
                                </td>
                                <td>
                                    <a href="stock_in.php?product_id=<?= $item['id'] ?>" class="btn btn-secondary btn-sm" style="color: #34d399;">
                                        + Restock
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Tabel 5 Transaksi Terakhir -->
    <div class="card">
        <div class="card-title">
            <span>⚡ Histori Pergerakan Stok Terbaru</span>
            <a href="stock_history.php" class="btn btn-secondary btn-sm">Lihat Semua ➡️</a>
        </div>

        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Tipe</th>
                        <th>Produk</th>
                        <th>Qty</th>
                        <th>Petugas</th>
                        <th>Waktu</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent_movements)): ?>
                        <tr><td colspan="5" style="text-align: center; color: var(--text-muted);">Belum ada riwayat pergerakan stok.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recent_movements as $m): ?>
                            <tr>
                                <td>
                                    <?php if ($m['type'] === 'IN'): ?>
                                        <span class="badge badge-success">+ MASUK</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">- KELUAR</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?= e($m['product_name']) ?></strong><br>
                                    <small class="mono" style="color: var(--text-dim);"><?= e($m['sku']) ?></small>
                                </td>
                                <td style="font-weight: 700; font-family: monospace;">
                                    <?= $m['type'] === 'IN' ? '+' : '-' ?><?= (int)$m['quantity'] ?>
                                </td>
                                <td><small><?= e($m['operator_name']) ?></small></td>
                                <td><small style="color: var(--text-muted);"><?= format_datetime($m['created_at']) ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/views/footer.php'; ?>
