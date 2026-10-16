<?php
/**
 * Product Edit Page (Admin Only)
 * Memperbarui data produk dan relasi Many-to-Many dengan suppliers
 */
$page_title = 'Edit Data Produk';
require_once __DIR__ . '/views/header.php';
require_admin();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    set_flash('danger', 'ID Produk tidak valid.');
    header('Location: products.php');
    exit;
}

// Ambil data produk
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id");
$stmt->execute([':id' => $id]);
$product = $stmt->fetch();

if (!$product) {
    set_flash('danger', 'Data produk tidak ditemukan.');
    header('Location: products.php');
    exit;
}

// Ambil kategori & supplier
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();
$suppliers  = $pdo->query("SELECT id, name FROM suppliers ORDER BY name ASC")->fetchAll();

// Ambil supplier yang sudah terhubung dengan produk ini (junction table)
$supp_stmt = $pdo->prepare("SELECT supplier_id FROM product_suppliers WHERE product_id = :product_id");
$supp_stmt->execute([':product_id' => $id]);
$existing_suppliers = $supp_stmt->fetchAll(PDO::FETCH_COLUMN);

$errors = [];
$sku           = $product['sku'];
$name          = $product['name'];
$category_id   = $product['category_id'];
$price         = $product['price'];
$minimum_stock = $product['minimum_stock'];
$description   = $product['description'];
$selected_suppliers = $existing_suppliers;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sku           = strtoupper(trim($_POST['sku'] ?? ''));
    $name          = trim($_POST['name'] ?? '');
    $category_id   = $_POST['category_id'] ?? '';
    $price         = trim($_POST['price'] ?? '0');
    $minimum_stock = trim($_POST['minimum_stock'] ?? '5');
    $description   = trim($_POST['description'] ?? '');
    $selected_suppliers = $_POST['suppliers'] ?? [];

    validate_required($sku, 'SKU Produk', $errors);
    validate_required($name, 'Nama Produk', $errors);
    validate_required($category_id, 'Kategori', $errors);
    validate_non_negative_number($price, 'Harga Produk', $errors);
    validate_non_negative_number($minimum_stock, 'Batas Stok Minimum', $errors);

    if (empty($errors)) {
        // Cek keunikan SKU (kecuali produk ini sendiri)
        validate_unique_sku($pdo, $sku, $id, $errors);
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $update_sql = "
                UPDATE products
                SET category_id = :category_id,
                    sku = :sku,
                    name = :name,
                    description = :description,
                    price = :price,
                    minimum_stock = :minimum_stock
                WHERE id = :id
            ";
            $update_stmt = $pdo->prepare($update_sql);
            $update_stmt->execute([
                ':category_id'   => (int)$category_id,
                ':sku'           => $sku,
                ':name'          => $name,
                ':description'   => $description,
                ':price'         => (float)$price,
                ':minimum_stock' => (int)$minimum_stock,
                ':id'            => $id
            ]);

            // Perbarui Junction Table: Hapus relasi lama, masukkan relasi baru
            $del_supp = $pdo->prepare("DELETE FROM product_suppliers WHERE product_id = :id");
            $del_supp->execute([':id' => $id]);

            if (!empty($selected_suppliers) && is_array($selected_suppliers)) {
                $ins_supp = $pdo->prepare("
                    INSERT INTO product_suppliers (product_id, supplier_id, supplier_price)
                    VALUES (:product_id, :supplier_id, :supplier_price)
                ");
                foreach ($selected_suppliers as $supp_id) {
                    $ins_supp->execute([
                        ':product_id'     => $id,
                        ':supplier_id'    => (int)$supp_id,
                        ':supplier_price' => (float)$price * 0.85
                    ]);
                }
            }

            $pdo->commit();
            set_flash('success', "Data produk [{$sku}] berhasil diperbarui.");
            header('Location: products.php');
            exit;
        } catch (Throwable $e) {
            $pdo->rollBack();
            $errors[] = "Gagal memperbarui produk: " . $e->getMessage();
        }
    }
}
?>

<div class="page-header">
    <div>
        <h1 class="page-title">✏️ Edit Produk [<?= e($product['sku']) ?>]</h1>
        <div class="page-subtitle">Perbarui rincian spesifikasi, harga, dan supplier terkait</div>
    </div>
    <a href="products.php" class="btn btn-secondary">
        ⬅️ Kembali ke Katalog
    </a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <div>
            <strong>Gagal Memperbarui Data:</strong>
            <ul style="margin-top: 6px; padding-left: 20px;">
                <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
<?php endif; ?>

<div class="card" style="max-width: 800px; margin: 0 auto;">
    <form action="product_edit.php?id=<?= $id ?>" method="POST">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <div class="form-group">
                <label class="form-label" for="sku">Kode SKU (Unik) *</label>
                <input type="text" id="sku" name="sku" class="form-control mono" 
                       value="<?= e($sku) ?>" required>
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
            <label class="form-label" for="name">Nama Produk *</label>
            <input type="text" id="name" name="name" class="form-control" 
                   value="<?= e($name) ?>" required>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px;">
            <div class="form-group">
                <label class="form-label" for="price">Harga Satuan (Rp) *</label>
                <input type="number" step="0.01" min="0" id="price" name="price" class="form-control" 
                       value="<?= e($price) ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">Stok Fisik Saat Ini</label>
                <input type="text" class="form-control" value="<?= (int)$product['stock'] ?> (Gunakan menu Transaksi untuk ubah stok)" disabled>
            </div>

            <div class="form-group">
                <label class="form-label" for="minimum_stock">Batas Minimum Stok *</label>
                <input type="number" min="0" id="minimum_stock" name="minimum_stock" class="form-control" 
                       value="<?= e($minimum_stock) ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="description">Deskripsi</label>
            <textarea id="description" name="description" class="form-control"><?= e($description) ?></textarea>
        </div>

        <div class="form-group">
            <label class="form-label">Vendor / Supplier Pemasok (Many-to-Many):</label>
            <div style="background: var(--bg-input); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 14px; max-height: 180px; overflow-y: auto;">
                <?php foreach ($suppliers as $s): ?>
                    <label style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px; cursor: pointer; font-size: 0.9rem;">
                        <input type="checkbox" name="suppliers[]" value="<?= $s['id'] ?>" 
                               <?= in_array($s['id'], $selected_suppliers) ? 'checked' : '' ?>>
                        <span><?= e($s['name']) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px;">
            <a href="products.php" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary">
                💾 Simpan Perubahan
            </button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/views/footer.php'; ?>
