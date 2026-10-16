<?php
/**
 * Transaction: Stock Out (Barang Keluar / Dispense)
 * Mengimplementasikan SELECT ... FOR UPDATE dan Validasi Stok Tidak Boleh Negatif
 * Sesuai panduan Slide 26 & Slide 33
 */
$page_title = 'Pencatatan Barang Keluar (OUT)';
require_once __DIR__ . '/views/header.php';

$preset_product_id = filter_input(INPUT_GET, 'product_id', FILTER_VALIDATE_INT);

// Ambil semua produk
$products = $pdo->query("SELECT id, sku, name, stock FROM products ORDER BY name ASC")->fetchAll();

$errors = [];
$product_id   = $preset_product_id ?: '';
$quantity     = '';
$reference_no = 'SO-' . date('Ymd') . '-' . rand(100, 999);
$notes        = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id   = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    $quantity     = trim($_POST['quantity'] ?? '');
    $reference_no = trim($_POST['reference_no'] ?? '');
    $notes        = trim($_POST['notes'] ?? '');

    // 1. Syntactic Validation (Slide 21)
    if (!$product_id) {
        $errors[] = 'Silakan pilih produk yang akan dikeluarkan.';
    }
    validate_positive_int($quantity, 'Kuantitas Keluar', $errors);

    if (empty($errors)) {
        try {
            // Memulai Database Transaction (Slide 26)
            $pdo->beginTransaction();

            // 1. Lock baris produk dengan SELECT ... FOR UPDATE (Slide 26)
            $stmt = $pdo->prepare("SELECT id, name, sku, stock FROM products WHERE id = :id FOR UPDATE");
            $stmt->execute([':id' => $product_id]);
            $prod = $stmt->fetch();

            if (!$prod) {
                throw new Exception("Produk tidak ditemukan dalam database.");
            }

            $current_stock = (int)$prod['stock'];
            $req_qty = (int)$quantity;

            // 2. Semantic Validation & Business Rule (Slide 26 & Slide 33: Stok tidak boleh menjadi negatif)
            if ($req_qty > $current_stock) {
                // Rollback dan kembalikan error validasi yang jelas
                throw new Exception("Kuantitas barang keluar ({$req_qty} unit) MELEBIHI stok fisik yang tersedia ({$current_stock} unit). Transaksi dibatalkan untuk mencegah stok negatif.");
            }

            // 3. Kurangi stok produk
            $update_stmt = $pdo->prepare("UPDATE products SET stock = stock - :qty WHERE id = :id");
            $update_stmt->execute([
                ':qty' => $req_qty,
                ':id'  => $product_id
            ]);

            // 4. Catat histori ke tabel stock_movements (type = 'OUT')
            $mov_stmt = $pdo->prepare("
                INSERT INTO stock_movements (product_id, user_id, type, quantity, reference_no, notes)
                VALUES (:product_id, :user_id, 'OUT', :quantity, :reference_no, :notes)
            ");
            $mov_stmt->execute([
                ':product_id'   => $product_id,
                ':user_id'      => (int)$current_user['id'],
                ':quantity'     => $req_qty,
                ':reference_no' => $reference_no ?: null,
                ':notes'        => $notes ?: 'Pengeluaran/pemasangan implan kepada klien.'
            ]);

            // 5. Commit transaksi jika semua langkah valid
            $pdo->commit();

            $remaining_stock = $current_stock - $req_qty;
            set_flash('success', "Berhasil mengeluarkan {$req_qty} unit produk [{$prod['sku']}] {$prod['name']}. Sisa stok fisik: {$remaining_stock} unit.");
            header('Location: stock_history.php');
            exit;

        } catch (Throwable $e) {
            // Rollback jika ada kegagalan validasi atau error query (Slide 26 & 27)
            $pdo->rollBack();
            $errors[] = $e->getMessage();
        }
    }
}
?>

<div class="page-header">
    <div>
        <h1 class="page-title" style="color: #fb7185;">📤 Pencatatan Mutasi Barang Keluar (OUT)</h1>
        <div class="page-subtitle">Pemeriksaan integritas stok dengan row locking (FOR UPDATE) dan pencegahan stok negatif</div>
    </div>
    <a href="products.php" class="btn btn-secondary">
        ⬅️ Kembali ke Katalog
    </a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <div>
            <strong>Peringatan Validasi Transaksi (Slide 26 & 33):</strong>
            <ul style="margin-top: 6px; padding-left: 20px;">
                <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
<?php endif; ?>

<div class="card" style="max-width: 700px; margin: 0 auto; border-top: 4px solid #f43f5e;">
    <form action="stock_out.php" method="POST">
        <div class="form-group">
            <label class="form-label" for="product_id">Pilih Produk yang Dikeluarkan *</label>
            <select id="product_id" name="product_id" class="form-control" required>
                <option value="">-- Pilih Produk --</option>
                <?php foreach ($products as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= (string)$product_id === (string)$p['id'] ? 'selected' : '' ?>>
                        [<?= e($p['sku']) ?>] <?= e($p['name']) ?> (Tersedia: <?= (int)$p['stock'] ?> unit)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <div class="form-group">
                <label class="form-label" for="quantity">Kuantitas Keluar (Unit) *</label>
                <input type="number" min="1" id="quantity" name="quantity" class="form-control" 
                       placeholder="Misal: 2" value="<?= e($quantity) ?>" required autofocus>
                <small style="color: var(--text-dim); font-size: 0.75rem;">Kuantitas keluar dilarang melebihi stok yang tersedia</small>
            </div>

            <div class="form-group">
                <label class="form-label" for="reference_no">Nomor Referensi / Sales Order (SO)</label>
                <input type="text" id="reference_no" name="reference_no" class="form-control mono" 
                       value="<?= e($reference_no) ?>">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="notes">Tujuan Pengeluaran / Keterangan Klien</label>
            <textarea id="notes" name="notes" class="form-control" 
                      placeholder="Contoh: Operasi pemasangan implan untuk klien solo di ruang bedah klinik..."><?= e($notes) ?></textarea>
        </div>

        <div style="background: rgba(244, 63, 94, 0.08); border-left: 3px solid #f43f5e; padding: 12px 16px; border-radius: 4px; margin-bottom: 20px; font-size: 0.85rem; color: #fecdd3;">
            ⚠️ <strong>Aturan Bisnis (Slide 33):</strong> Sistem secara otomatis mengunci baris data dengan <code>SELECT ... FOR UPDATE</code>. Jika jumlah yang diminta lebih besar dari stok fisik, transaksi akan <strong>di-rollback seketika</strong> dan stok tidak akan pernah menjadi negatif.
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 12px;">
            <a href="products.php" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary" style="background: linear-gradient(135deg, #e11d48, #f43f5e); color: #fff;">
                🚀 Konfirmasi Barang Keluar
            </button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/views/footer.php'; ?>
