<?php
/**
 * Inventory Reports & Aggregate Analytics
 * Mengimplementasikan GROUP BY, COUNT, SUM, AVG, dan CASE WHEN
 * Sesuai panduan Slide 14 & Slide 28
 */
$page_title = 'Laporan Agregat Inventaris';
require_once __DIR__ . '/views/header.php';

// 1. Query Laporan Mutasi per Produk (Slide 28)
$report_products_sql = "
    SELECT p.id, p.sku, p.name, p.stock, p.price,
           c.name AS category_name,
           (p.stock * p.price) AS total_value,
           COALESCE(SUM(CASE WHEN sm.type = 'IN' THEN sm.quantity ELSE 0 END), 0) AS total_in,
           COALESCE(SUM(CASE WHEN sm.type = 'OUT' THEN sm.quantity ELSE 0 END), 0) AS total_out
    FROM products p
    JOIN categories c ON c.id = p.category_id
    LEFT JOIN stock_movements sm ON sm.product_id = p.id
    GROUP BY p.id, p.sku, p.name, p.stock, p.price, c.name
    ORDER BY p.name ASC
";
$product_reports = $pdo->query($report_products_sql)->fetchAll();

// 2. Query Laporan Ringkasan per Kategori (Slide 14)
$report_categories_sql = "
    SELECT c.id, c.name,
           COUNT(p.id) AS total_products,
           COALESCE(SUM(p.stock), 0) AS total_stock,
           COALESCE(AVG(p.price), 0) AS average_price,
           COALESCE(SUM(p.stock * p.price), 0) AS category_value
    FROM categories c
    LEFT JOIN products p ON p.category_id = c.id
    GROUP BY c.id, c.name
    ORDER BY category_value DESC
";
$category_reports = $pdo->query($report_categories_sql)->fetchAll();

// Total kalkulasi keseluruhan
$grand_total_stock = 0;
$grand_total_val   = 0;
$grand_total_in    = 0;
$grand_total_out   = 0;

foreach ($product_reports as $pr) {
    $grand_total_stock += $pr['stock'];
    $grand_total_val   += $pr['total_value'];
    $grand_total_in    += $pr['total_in'];
    $grand_total_out   += $pr['total_out'];
}
?>

<div class="page-header">
    <div>
        <h1 class="page-title">📈 Laporan Inventaris Berbasis Fungsi Agregat</h1>
        <div class="page-subtitle">Analisis perputaran barang menggunakan GROUP BY, SUM(CASE WHEN...), COUNT, dan AVG</div>
    </div>
    <button class="btn btn-secondary" onclick="window.print()">
        🖨️ Cetak Dokumen Laporan
    </button>
</div>

<!-- Kartu Ringkasan Metrik Laporan -->
<div class="grid-cols-4">
    <div class="stat-card">
        <div class="stat-info">
            <div class="stat-label">Total Unit Fisik Tersedia</div>
            <div class="stat-value"><?= number_format($grand_total_stock) ?> unit</div>
        </div>
        <div class="stat-icon">📦</div>
    </div>

    <div class="stat-card">
        <div class="stat-info">
            <div class="stat-label">Total Nilai Kapitalisasi Aset</div>
            <div class="stat-value" style="font-size: 1.4rem; color: #38bdf8;"><?= format_currency($grand_total_val) ?></div>
        </div>
        <div class="stat-icon">💎</div>
    </div>

    <div class="stat-card success">
        <div class="stat-info">
            <div class="stat-label">Total Volume Masuk (IN)</div>
            <div class="stat-value" style="color: #34d399;">+<?= number_format($grand_total_in) ?> unit</div>
        </div>
        <div class="stat-icon">📥</div>
    </div>

    <div class="stat-card danger">
        <div class="stat-info">
            <div class="stat-label">Total Volume Keluar (OUT)</div>
            <div class="stat-value" style="color: #fb7185;">-<?= number_format($grand_total_out) ?> unit</div>
        </div>
        <div class="stat-icon">📤</div>
    </div>
</div>

<!-- Laporan 1: Ringkasan per Kategori (Slide 14) -->
<div class="card" style="margin-bottom: 28px;">
    <div class="card-title">
        <span>🏷️ 1. Laporan Agregasi per Kategori Produk (Slide 14)</span>
        <span class="mono" style="font-size: 0.8rem; color: var(--text-dim);">COUNT(p.id), SUM(p.stock), AVG(p.price)</span>
    </div>

    <div class="table-responsive">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>Nama Kategori</th>
                    <th>Jumlah Jenis Produk</th>
                    <th>Total Stok Fisik</th>
                    <th>Rata-rata Harga Satuan</th>
                    <th>Subtotal Valuasi Aset</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($category_reports as $cr): ?>
                    <tr>
                        <td><strong><?= e($cr['name']) ?></strong></td>
                        <td class="mono"><?= (int)$cr['total_products'] ?> SKU</td>
                        <td class="mono" style="font-weight: 700;"><?= number_format($cr['total_stock']) ?> unit</td>
                        <td class="mono" style="color: #94a3b8;"><?= format_currency($cr['average_price']) ?></td>
                        <td class="mono" style="color: var(--cyan-neon); font-weight: 700;"><?= format_currency($cr['category_value']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Laporan 2: Laporan Arus Barang per Produk (Slide 28) -->
<div class="card">
    <div class="card-title">
        <span>🔄 2. Laporan Perputaran Arus Mutasi Produk (Slide 28)</span>
        <span class="mono" style="font-size: 0.8rem; color: var(--text-dim);">SUM(CASE WHEN sm.type='IN'...)</span>
    </div>

    <div class="table-responsive">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>SKU & Nama Produk</th>
                    <th>Kategori</th>
                    <th>Total Masuk (+IN)</th>
                    <th>Total Keluar (-OUT)</th>
                    <th>Sisa Stok Fisik</th>
                    <th>Harga Satuan</th>
                    <th>Total Nilai Aset</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($product_reports as $pr): ?>
                    <tr>
                        <td>
                            <strong style="color: #fff;"><?= e($pr['name']) ?></strong><br>
                            <small class="mono" style="color: var(--cyan-neon);"><?= e($pr['sku']) ?></small>
                        </td>
                        <td><span class="badge badge-purple"><?= e($pr['category_name']) ?></span></td>
                        <td class="mono" style="color: #34d399; font-weight: 700;">+<?= number_format($pr['total_in']) ?></td>
                        <td class="mono" style="color: #fb7185; font-weight: 700;">-<?= number_format($pr['total_out']) ?></td>
                        <td class="mono" style="font-size: 1rem; font-weight: 700; color: #fff;">
                            <?= number_format($pr['stock']) ?>
                        </td>
                        <td class="mono" style="color: #94a3b8;"><?= format_currency($pr['price']) ?></td>
                        <td class="mono" style="color: #38bdf8; font-weight: 700;"><?= format_currency($pr['total_value']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background: #0d121e; font-weight: 700;">
                    <td colspan="2" style="text-align: right; text-transform: uppercase;">Total Akumulasi:</td>
                    <td class="mono" style="color: #34d399;">+<?= number_format($grand_total_in) ?></td>
                    <td class="mono" style="color: #fb7185;">-<?= number_format($grand_total_out) ?></td>
                    <td class="mono" style="color: #fff;"><?= number_format($grand_total_stock) ?> unit</td>
                    <td>-</td>
                    <td class="mono" style="color: var(--cyan-neon); font-size: 1.05rem;"><?= format_currency($grand_total_val) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/views/footer.php'; ?>
