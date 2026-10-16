<?php
/**
 * Product Deletion Handler (Admin Only)
 * Memvalidasi aturan integritas referensial (Slide 09 & Slide 33)
 * Produk yang memiliki histori transaksi dilarang dihapus sembarangan
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/auth.php';
require_once __DIR__ . '/helpers/escape.php';

require_admin();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    set_flash('danger', 'ID Produk tidak valid.');
    header('Location: products.php');
    exit;
}

// Cek keberadaan produk
$stmt = $pdo->prepare("SELECT id, sku, name FROM products WHERE id = :id");
$stmt->execute([':id' => $id]);
$product = $stmt->fetch();

if (!$product) {
    set_flash('danger', 'Data produk tidak ditemukan.');
    header('Location: products.php');
    exit;
}

// 1. Cek Integritas Referensial (Slide 09 & 33: Aturan RESTRICT)
$check_stmt = $pdo->prepare("SELECT COUNT(*) AS movement_count FROM stock_movements WHERE product_id = :id");
$check_stmt->execute([':id' => $id]);
$movement_count = (int)$check_stmt->fetch()['movement_count'];

if ($movement_count > 0) {
    // Larang penghapusan karena melanggar integritas histori data
    set_flash('danger', "Gagal Menghapus: Produk [{$product['sku']}] memiliki {$movement_count} catatan histori pergerakan stok. Sesuai aturan bisnis (Slide 33) dan integritas referensial (RESTRICT), produk dengan riwayat transaksi tidak boleh dihapus.");
    header('Location: products.php');
    exit;
}

// 2. Jika tidak ada histori transaksi, lakukan penghapusan aman
try {
    $pdo->beginTransaction();

    // Hapus relasi di junction table
    $del_junction = $pdo->prepare("DELETE FROM product_suppliers WHERE product_id = :id");
    $del_junction->execute([':id' => $id]);

    // Hapus produk
    $del_prod = $pdo->prepare("DELETE FROM products WHERE id = :id");
    $del_prod->execute([':id' => $id]);

    $pdo->commit();
    set_flash('success', "Produk [{$product['sku']}] {$product['name']} berhasil dihapus dari sistem.");
} catch (Throwable $e) {
    $pdo->rollBack();
    set_flash('danger', 'Gagal menghapus produk: ' . $e->getMessage());
}

header('Location: products.php');
exit;
