<?php
/**
 * Product Detail & Relation View
 * Menampilkan rincian implan, relasi many-to-many supplier, dan histori stok
 */
$page_title = 'Rincian Produk & Histori';
require_once __DIR__ . '/views/header.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    set_flash('danger', 'ID Produk tidak valid.');
    header('Location: products.php');
    exit;
}

// 1. Ambil data produk dan kategori (JOIN)
$stmt = $pdo->prepare("
    SELECT p.*, c.name AS category_name, c.description AS category_desc
    FROM products p
    JOIN categories c ON c.id = p.category_id
    WHERE p.id = :id
");
$stmt->execute([':id' => $id]);
$product = $stmt->fetch();

if (!$product) {
    set_flash('danger', 'Data produk tidak ditemukan.');
    header('Location: products.php');
    exit;
}

// 2. Ambil Supplier Terkait via Junction Table (Many-to-Many - Slide 08)
$supp_stmt = $pdo->prepare("
    SELECT s.name AS supplier_name, s.contact_person, s.phone, s.email, ps.supplier_price
    FROM product_suppliers ps
    JOIN suppliers s ON s.id = ps.supplier_id
    WHERE ps.product_id = :id
    ORDER BY s.name ASC
");
$supp_stmt->execute([':id' => $id]);
$suppliers = $supp_stmt->fetchAll();

// 3. Ambil Histori Transaksi Produk Ini (Slide 10)
$mov_stmt = $pdo->prepare("
    SELECT sm.*, u.full_name AS operator_name, u.role AS operator_role
    FROM stock_movements sm
    JOIN users u ON u.id = sm.user_id
    WHERE sm.product_id = :id
    ORDER BY sm.created_at DESC
");
$mov_stmt->execute([':id' => $id]);
$movements = $mov_stmt->fetchAll();
?>

<div class="page-header">
    <div>
        <h1 class="page-title">🔍 Rincian Spesifikasi Produk</h1>
        <div class="page-subtitle">Detail data, relasi supplier vendor, dan jejak histori transaksi</div>
    </div>
    <div style="display: flex; gap: 10px;">
        <a href="stock_in.php?product_id=<?= $product['id'] ?>" class="btn btn-primary" style="background: linear-gradient(135deg, #059669, #10b981); color: #fff;">
            📥 + Restock Masuk
        </a>
        <a href="stock_out.php?product_id=<?= $product['id'] ?>" class="btn btn-primary" style="background: linear-gradient(135deg, #e11d48, #f43f5e); color: #fff;">
            📤 - Catat Keluar
        </a>
        <a href="products.php" class="btn btn-secondary">
            ⬅️ Kembali ke Katalog
        </a>
    </div>
</div>

<div class="grid-cols-2">
    <!-- Card Info Produk -->
    <div class="card">
        <div class="card-title">
            <span class="mono" style="color: var(--cyan-neon); font-size: 1.3rem;">[<?= e($product['sku']) ?>]</span>
            <?= render_stock_badge($product['stock'], $product['minimum_stock']) ?>
        </div>

        <h2 style="color: #fff; margin-bottom: 8px;"><?= e($product['name']) ?></h2>
        <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 20px; line-height: 1.6;">
            <?= e($product['description'] ?: 'Tidak ada rincian deskripsi spesifikasi tambahan.') ?>
        </p>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; background: var(--bg-input); padding: 16px; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
            <div>
                <span style="font-size: 0.8rem; color: var(--text-muted);">Kategori Induk:</span>
                <div style="font-weight: 600; color: #c084fc;"><?= e($product['category_name']) ?></div>
            </div>
            <div>
                <span style="font-size: 0.8rem; color: var(--text-muted);">Harga Jual Satuan:</span>
                <div class="mono" style="font-weight: 700; color: #38bdf8;"><?= format_currency($product['price']) ?></div>
            </div>
            <div>
                <span style="font-size: 0.8rem; color: var(--text-muted);">Stok Fisik Tersedia:</span>
                <div style="font-size: 1.2rem; font-weight: 700; color: #fff;"><?= (int)$product['stock'] ?> unit</div>
            </div>
            <div>
                <span style="font-size: 0.8rem; color: var(--text-muted);">Batas Minimum Stok:</span>
                <div style="font-weight: 600; color: #fbbf24;"><?= (int)$product['minimum_stock'] ?> unit</div>
            </div>
            <div style="grid-column: span 2; border-top: 1px dashed var(--border-color); padding-top: 10px;">
                <span style="font-size: 0.8rem; color: var(--text-muted);">Total Valuasi Aset Stok:</span>
                <div class="mono" style="font-size: 1.25rem; font-weight: 700; color: var(--cyan-neon);">
                    <?= format_currency($product['stock'] * $product['price']) ?>
                </div>
            </div>
        </div>

        <?php if (is_admin()): ?>
            <div style="margin-top: 20px; display: flex; gap: 10px;">
                <a href="product_edit.php?id=<?= $product['id'] ?>" class="btn btn-secondary btn-sm" style="color: #fbbf24;">
                    ✏️ Edit Data Produk
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Card Relasi Many-to-Many: Suppliers -->
    <div class="card">
        <div class="card-title">
            <span>🏢 Pemasok / Vendor Terkait</span>
            <span class="badge badge-cyan"><?= count($suppliers) ?> Vendor</span>
        </div>

        <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 14px;">
            Relasi <em>Many-to-Many</em> melalui tabel persimpangan <code>product_suppliers</code> (Slide 08).
        </p>

        <?php if (empty($suppliers)): ?>
            <p style="color: var(--text-muted); font-size: 0.9rem;">Belum ada supplier yang dihubungkan ke produk ini.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Nama Supplier</th>
                            <th>Kontak</th>
                            <th>Harga Vendor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($suppliers as $s): ?>
                            <tr>
                                <td>
                                    <strong><?= e($s['supplier_name']) ?></strong><br>
                                    <small style="color: var(--text-dim);"><?= e($s['contact_person']) ?></small>
                                </td>
                                <td>
                                    <small><?= e($s['phone'] ?: '-') ?></small><br>
                                    <small style="color: var(--cyan-neon);"><?= e($s['email'] ?: '-') ?></small>
                                </td>
                                <td class="mono" style="color: #38bdf8;">
                                    <?= $s['supplier_price'] ? format_currency($s['supplier_price']) : '-' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Card Histori Pergerakan Stok untuk Produk Ini -->
<div class="card" style="margin-top: 10px;">
    <div class="card-title">
        <span>📜 Buku Besar / Log Pergerakan Stok Khusus Produk Ini</span>
        <span class="badge badge-purple"><?= count($movements) ?> Catatan</span>
    </div>

    <div class="table-responsive">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>Waktu Catat</th>
                    <th>Tipe Transaksi</th>
                    <th>Kuantitas</th>
                    <th>No. Referensi / PO</th>
                    <th>Operator / Petugas</th>
                    <th>Catatan Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($movements)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 24px;">
                            Belum ada catatan mutasi transaksi untuk produk ini.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($movements as $m): ?>
                        <tr>
                            <td><small><?= format_datetime($m['created_at']) ?></small></td>
                            <td>
                                <?php if ($m['type'] === 'IN'): ?>
                                    <span class="badge badge-success">+ MASUK</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">- KELUAR</span>
                                <?php endif; ?>
                            </td>
                            <td class="mono" style="font-weight: 700; font-size: 1rem;">
                                <?= $m['type'] === 'IN' ? '+' : '-' ?><?= (int)$m['quantity'] ?>
                            </td>
                            <td class="mono"><small><?= e($m['reference_no'] ?: '-') ?></small></td>
                            <td>
                                <strong><?= e($m['operator_name']) ?></strong><br>
                                <small style="color: var(--text-dim);">[<?= strtoupper(e($m['operator_role'])) ?>]</small>
                            </td>
                            <td><small><?= e($m['notes'] ?: '-') ?></small></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/views/footer.php'; ?>
