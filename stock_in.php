<?php
/**
 * Transaction: Stock In (Barang Masuk / Restock)
 * Menggunakan Database Transaction Atomik (Slide 25 & 27)
 */
$page_title = 'Pencatatan Barang Masuk (IN)';
require_once __DIR__ . '/views/header.php';

$preset_product_id = filter_input(INPUT_GET, 'product_id', FILTER_VALIDATE_INT);

// Ambil semua produk untuk pilihan dropdown
$products = $pdo->query("SELECT id, sku, name, stock FROM products ORDER BY name ASC")->fetchAll();

$errors = [];
$product_id   = $preset_product_id ?: '';
$quantity     = '';
$reference_no = 'PO-' . date('Ymd') . '-' . rand(100, 999);
$notes        = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id   = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    $quantity     = trim($_POST['quantity'] ?? '');
    $reference_no = trim($_POST['reference_no'] ?? '');
    $notes        = trim($_POST['notes'] ?? '');

    // 1. Syntactic & Semantic Validation (Slide 21)
    if (!$product_id) {
        $errors[] = 'Silakan pilih produk yang akan ditambahkan stoknya.';
    }
    validate_positive_int($quantity, 'Kuantitas Masuk', $errors);

    if (empty($errors)) {
        try {
            // Memulai Database Transaction (Slide 25 & 27)
            $pdo->beginTransaction();

            // 1. Verifikasi produk ada
            $check = $pdo->prepare("SELECT id, name, sku, stock FROM products WHERE id = :id FOR UPDATE");
            $check->execute([':id' => $product_id]);
            $prod = $check->fetch();

            if (!$prod) {
                throw new Exception("Produk tidak ditemukan di database.");
            }

            $qty_int = (int)$quantity;

            // 2. Tambah stok produk
            $update_stmt = $pdo->prepare("UPDATE products SET stock = stock + :qty WHERE id = :id");
            $update_stmt->execute([
                ':qty' => $qty_int,
                ':id'  => $product_id
            ]);

            // 3. Catat ke tabel histori stock_movements
            $mov_stmt = $pdo->prepare("
                INSERT INTO stock_movements (product_id, user_id, type, quantity, reference_no, notes)
                VALUES (:product_id, :user_id, 'IN', :quantity, :reference_no, :notes)
            ");
            $mov_stmt->execute([
                ':product_id'   => $product_id,
                ':user_id'      => (int)$current_user['id'],
                ':quantity'     => $qty_int,
                ':reference_no' => $reference_no ?: null,
                ':notes'        => $notes ?: 'Penerimaan pasokan barang masuk.'
            ]);

            // 4. Commit transaksi jika semua operasi berhasil
            $pdo->commit();

            $new_stock = $prod['stock'] + $qty_int;
            set_flash('success', "Berhasil menambahkan {$qty_int} unit ke produk [{$prod['sku']}] {$prod['name']}. Stok sekarang: {$new_stock} unit.");
            header('Location: stock_history.php');
            exit;

        } catch (Throwable $e) {
            // Batalkan semua perubahan jika terjadi kegagalan (Slide 27)
            $pdo->rollBack();
            $errors[] = "Gagal memproses transaksi barang masuk: " . $e->getMessage();
        }
    }
}
?>

<div class="page-header">
    <div>
        <h1 class="page-title" style="color: #34d399;">📥 Pencatatan Mutasi Barang Masuk (IN)</h1>
        <div class="page-subtitle">Proses penambahan saldo stok dengan transaksi atomik dan pencatatan audit log</div>
    </div>
    <a href="products.php" class="btn btn-secondary">
        ⬅️ Kembali ke Katalog
    </a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <div>
            <strong>Gagal Memproses Transaksi (Validasi):</strong>
            <ul style="margin-top: 6px; padding-left: 20px;">
                <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
<?php endif; ?>

<div class="card" style="max-width: 700px; margin: 0 auto; border-top: 4px solid #10b981;">
    <form action="stock_in.php" method="POST">
        <div class="form-group">
            <label class="form-label" for="product_id">Pilih Komponen / Produk Hardware *</label>
            <select id="product_id" name="product_id" class="form-control" required>
                <option value="">-- Pilih Produk --</option>
                <?php foreach ($products as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= (string)$product_id === (string)$p['id'] ? 'selected' : '' ?>>
                        [<?= e($p['sku']) ?>] <?= e($p['name']) ?> (Stok Saat Ini: <?= (int)$p['stock'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <div class="form-group">
                <label class="form-label" for="quantity">Kuantitas Masuk (Unit) *</label>
                <input type="number" min="1" id="quantity" name="quantity" class="form-control" 
                       placeholder="Misal: 10" value="<?= e($quantity) ?>" required autofocus>
                <small style="color: var(--text-dim); font-size: 0.75rem;">Kuantitas harus bilangan bulat > 0</small>
            </div>

            <div class="form-group">
                <label class="form-label" for="reference_no">Nomor Referensi / Surat Jalan (PO)</label>
                <input type="text" id="reference_no" name="reference_no" class="form-control mono" 
                       value="<?= e($reference_no) ?>">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="notes">Catatan Transaksi / Sumber Pasokan</label>
            <textarea id="notes" name="notes" class="form-control" 
                      placeholder="Contoh: Pengiriman batch 1 dari ASUS Global Distribution via ekspedisi kargo..."><?= e($notes) ?></textarea>
        </div>

        <div style="background: rgba(16, 185, 129, 0.08); border-left: 3px solid #10b981; padding: 12px 16px; border-radius: 4px; margin-bottom: 20px; font-size: 0.85rem; color: #a7f3d0;">
            ℹ️ <strong>Aturan Transaksi Database:</strong> Penambahan stok produk dan pencatatan ke tabel <code>stock_movements</code> dieksekusi dalam satu transaksi database atomik (Commit / Rollback).
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 12px;">
            <a href="products.php" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary" style="background: linear-gradient(135deg, #059669, #10b981); color: #fff;">
                ✅ Konfirmasi Barang Masuk
            </button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/views/footer.php'; ?>
