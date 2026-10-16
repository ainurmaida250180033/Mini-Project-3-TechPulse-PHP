<?php
/**
 * Product Create Page (Admin Only)
 * Mendukung relasi Many-to-Many dengan Suppliers dan inisialisasi histori transaksi
 */
$page_title = 'Tambah Produk Baru';
require_once __DIR__ . '/views/header.php';
require_admin(); // Slide 24 & 33: Batasi hanya untuk role admin

// Ambil kategori & supplier untuk pilihan form
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();
$suppliers  = $pdo->query("SELECT id, name FROM suppliers ORDER BY name ASC")->fetchAll();

$errors = [];
$sku = '';
$name = '';
$category_id = '';
$price = '';
$stock = '0';
$minimum_stock = '5';
$description = '';
$selected_suppliers = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sku           = strtoupper(trim($_POST['sku'] ?? ''));
    $name          = trim($_POST['name'] ?? '');
    $category_id   = $_POST['category_id'] ?? '';
    $price         = trim($_POST['price'] ?? '0');
    $stock         = trim($_POST['stock'] ?? '0');
    $minimum_stock = trim($_POST['minimum_stock'] ?? '5');
    $description   = trim($_POST['description'] ?? '');
    $selected_suppliers = $_POST['suppliers'] ?? []; // Array supplier_id

    // Validasi Syntactic & Semantic (Slide 21 & Slide 33)
    validate_required($sku, 'SKU Produk', $errors);
    validate_required($name, 'Nama Produk', $errors);
    validate_required($category_id, 'Kategori', $errors);
    validate_non_negative_number($price, 'Harga Produk', $errors);
    validate_non_negative_number($stock, 'Stok Awal', $errors);
    validate_non_negative_number($minimum_stock, 'Batas Stok Minimum', $errors);

    if (empty($errors)) {
        // Validasi keunikan SKU (Slide 33: SKU harus unik)
        validate_unique_sku($pdo, $sku, null, $errors);
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // 1. Simpan data produk
            $insert_sql = "
                INSERT INTO products (category_id, sku, name, description, price, stock, minimum_stock)
                VALUES (:category_id, :sku, :name, :description, :price, :stock, :minimum_stock)
            ";
            $stmt = $pdo->prepare($insert_sql);
            $stmt->execute([
                ':category_id'   => (int)$category_id,
                ':sku'           => $sku,
                ':name'          => $name,
                ':description'   => $description,
                ':price'         => (float)$price,
                ':stock'         => (int)$stock,
                ':minimum_stock' => (int)$minimum_stock
            ]);
            $new_product_id = (int)$pdo->lastInsertId();

            // 2. Simpan Relasi Many-to-Many ke Tabel Junction `product_suppliers` (Slide 08)
            if (!empty($selected_suppliers) && is_array($selected_suppliers)) {
                $supp_stmt = $pdo->prepare("
                    INSERT INTO product_suppliers (product_id, supplier_id, supplier_price)
                    VALUES (:product_id, :supplier_id, :supplier_price)
                ");
                foreach ($selected_suppliers as $supp_id) {
                    $supp_price = (float)$price * 0.85; // Estimasi harga pasokan vendor
                    $supp_stmt->execute([
                        ':product_id'     => $new_product_id,
                        ':supplier_id'    => (int)$supp_id,
                        ':supplier_price' => $supp_price
                    ]);
                }
            }

            // 3. Jika ada stok awal > 0, catat otomatis ke tabel `stock_movements` (Slide 25)
            if ((int)$stock > 0) {
                $movement_stmt = $pdo->prepare("
                    INSERT INTO stock_movements (product_id, user_id, type, quantity, reference_no, notes)
                    VALUES (:product_id, :user_id, 'IN', :quantity, :reference_no, :notes)
                ");
                $movement_stmt->execute([
                    ':product_id'   => $new_product_id,
                    ':user_id'      => (int)$current_user['id'],
                    ':quantity'     => (int)$stock,
                    ':reference_no' => 'INIT-' . date('YmdHis'),
                    ':notes'        => 'Inisialisasi saldo stok awal saat produk baru didaftarkan.'
                ]);
            }

            $pdo->commit();
            set_flash('success', "Produk [{$sku}] {$name} berhasil ditambahkan ke inventaris.");
            header('Location: products.php');
            exit;
        } catch (Throwable $e) {
            $pdo->rollBack();
            $errors[] = "Gagal menyimpan produk: " . $e->getMessage();
        }
    }
}
?>

<div class="page-header">
    <div>
        <h1 class="page-title">➕ Tambah Implan / Produk Baru</h1>
        <div class="page-subtitle">Pendaftaran unit produk ke database relasional dengan validasi ketat</div>
    </div>
    <a href="products.php" class="btn btn-secondary">
        ⬅️ Kembali ke Katalog
    </a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <div>
            <strong>Gagal Menyimpan Data (Validasi Server):</strong>
            <ul style="margin-top: 6px; padding-left: 20px;">
                <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
<?php endif; ?>

<div class="card" style="max-width: 800px; margin: 0 auto;">
    <form action="product_create.php" method="POST">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <div class="form-group">
                <label class="form-label" for="sku">Kode SKU (Harus Unik) *</label>
                <input type="text" id="sku" name="sku" class="form-control mono" 
                       placeholder="Misal: TP-GPU-4090" value="<?= e($sku) ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="category_id">Kategori Induk *</label>
                <select id="category_id" name="category_id" class="form-control" required>
                    <option value="">-- Pilih Kategori --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= (string)$category_id === (string)$cat['id'] ? 'selected' : '' ?>>
                            <?= e($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="name">Nama Komponen / Hardware *</label>
            <input type="text" id="name" name="name" class="form-control" 
                   placeholder="Contoh: ASUS ROG Strix GeForce RTX 4090 OC" value="<?= e($name) ?>" required>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px;">
            <div class="form-group">
                <label class="form-label" for="price">Harga Satuan (Rp) *</label>
                <input type="number" step="0.01" min="0" id="price" name="price" class="form-control" 
                       placeholder="0.00" value="<?= e($price) ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="stock">Stok Awal Fisik *</label>
                <input type="number" min="0" id="stock" name="stock" class="form-control" 
                       value="<?= e($stock) ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="minimum_stock">Batas Minimum Stok *</label>
                <input type="number" min="0" id="minimum_stock" name="minimum_stock" class="form-control" 
                       value="<?= e($minimum_stock) ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="description">Deskripsi Spesifikasi Teknis</label>
            <textarea id="description" name="description" class="form-control" placeholder="Rincian arsitektur, interface PCIe, dimensi radiator, dll..."><?= e($description) ?></textarea>
        </div>

        <!-- Relasi Many-to-Many: Pilih Supplier yang Menyediakan -->
        <div class="form-group">
            <label class="form-label">Vendor / Supplier Pemasok (Many-to-Many):</label>
            <div style="background: var(--bg-input); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 14px; max-height: 180px; overflow-y: auto;">
                <?php if (empty($suppliers)): ?>
                    <p style="color: var(--text-muted); font-size: 0.85rem;">Belum ada data supplier. Tambahkan di menu Supplier terlebih dahulu.</p>
                <?php else: ?>
                    <?php foreach ($suppliers as $s): ?>
                        <label style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px; cursor: pointer; font-size: 0.9rem;">
                            <input type="checkbox" name="suppliers[]" value="<?= $s['id'] ?>" 
                                   <?= in_array($s['id'], $selected_suppliers) ? 'checked' : '' ?>>
                            <span><?= e($s['name']) ?></span>
                        </label>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px;">
            <a href="products.php" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary">
                💾 Simpan Produk ke Database
            </button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/views/footer.php'; ?>
