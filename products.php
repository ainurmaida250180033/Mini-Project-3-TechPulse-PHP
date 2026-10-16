<?php
/**
 * Product Listing with Search, Filter, Sorting, and Pagination
 * Mengimplementasikan JOIN, Allowlist Sorting, dan Prepared Statement LIMIT/OFFSET
 * Sesuai panduan Slide 12, 13, 16, dan 17
 */
$page_title = 'Daftar Katalog Produk Sibernetik';
require_once __DIR__ . '/views/header.php';

// 1. Tangkap Parameter Query String
$search      = trim($_GET['search'] ?? '');
$category_id = trim($_GET['category_id'] ?? '');
$sort_by     = trim($_GET['sort_by'] ?? 'name');
$sort_dir    = trim($_GET['sort_dir'] ?? 'ASC');
$page        = max(1, (int)($_GET['page'] ?? 1));
$per_page    = 6; // Data per halaman sesuai slide pagination
$offset      = ($page - 1) * $per_page;

// 2. Allowlist Validation untuk Sorting (Slide 16 - Mencegah SQL Injection pada ORDER BY)
$allowed_sort_columns = [
    'name'       => 'p.name',
    'sku'        => 'p.sku',
    'price'      => 'p.price',
    'stock'      => 'p.stock',
    'created_at' => 'p.created_at'
];

$order_column = $allowed_sort_columns[$sort_by] ?? 'p.name';
$order_direction = strtoupper($sort_dir) === 'DESC' ? 'DESC' : 'ASC';

// 3. Bangun Klausa WHERE Dinamis Menggunakan Parameter Binding
$where_clauses = [];
$params = [];

if ($search !== '') {
    $where_clauses[] = "(p.name LIKE :search_name OR p.sku LIKE :search_sku)";
    $params[':search_name'] = "%{$search}%";
    $params[':search_sku']  = "%{$search}%";
}

if ($category_id !== '' && is_numeric($category_id)) {
    $where_clauses[] = "p.category_id = :category_id";
    $params[':category_id'] = (int)$category_id;
}

$where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

// 4. Hitung Total Data untuk Pagination
$count_sql = "
    SELECT COUNT(p.id) AS total
    FROM products p
    JOIN categories c ON c.id = p.category_id
    {$where_sql}
";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_rows = (int)$count_stmt->fetch()['total'];
$total_pages = max(1, (int)ceil($total_rows / $per_page));

// Koreksi page jika melebihi total halaman
if ($page > $total_pages && $total_pages > 0) {
    $page = $total_pages;
    $offset = ($page - 1) * $per_page;
}

// 5. Query Ambil Data Produk (JOIN dengan categories)
$data_sql = "
    SELECT p.id, p.category_id, p.sku, p.name, p.price, p.stock, p.minimum_stock,
           c.name AS category_name
    FROM products p
    JOIN categories c ON c.id = p.category_id
    {$where_sql}
    ORDER BY {$order_column} {$order_direction}
    LIMIT :limit OFFSET :offset
";

$stmt = $pdo->prepare($data_sql);

// Bind nilai filter/pencarian
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
// Bind LIMIT dan OFFSET secara integer eksplisit (Slide 17 & 18)
$stmt->bindValue(':limit', (int)$per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();

// Ambil semua kategori untuk dropdown filter
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();

/**
 * Helper mempertahankan query string saat pagination atau sorting berubah
 */
function build_query_url($overrides = []) {
    $current_params = $_GET;
    $merged = array_merge($current_params, $overrides);
    return 'products.php?' . http_build_query($merged);
}
?>

<div class="page-header">
    <div>
        <h1 class="page-title">📦 Katalog Inventaris Produk</h1>
        <div class="page-subtitle">Pencarian, pemfilteran kategori, pengurutan aman, dan manajemen stok</div>
    </div>
    <?php if (is_admin()): ?>
        <a href="product_create.php" class="btn btn-primary">
            ➕ Tambah Produk Baru
        </a>
    <?php endif; ?>
</div>

<!-- Form Search, Filter, dan Sorting (Slide 16) -->
<div class="filter-bar">
    <form method="GET" action="products.php" class="filter-form">
        <input type="hidden" name="page" value="1">
        
        <!-- Input Search (Slide 13) -->
        <div style="flex: 1; min-width: 220px;">
            <input type="text" name="search" class="form-control" 
                   placeholder="🔍 Cari nama atau SKU..." value="<?= e($search) ?>">
        </div>

        <!-- Filter Kategori (Slide 16) -->
        <div style="min-width: 180px;">
            <select name="category_id" class="form-control">
                <option value="">Semua Kategori</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= (string)$category_id === (string)$cat['id'] ? 'selected' : '' ?>>
                        <?= e($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Sorting Column (Allowlist) -->
        <div style="min-width: 150px;">
            <select name="sort_by" class="form-control">
                <option value="name" <?= $sort_by === 'name' ? 'selected' : '' ?>>Urut: Nama</option>
                <option value="sku" <?= $sort_by === 'sku' ? 'selected' : '' ?>>Urut: SKU</option>
                <option value="price" <?= $sort_by === 'price' ? 'selected' : '' ?>>Urut: Harga</option>
                <option value="stock" <?= $sort_by === 'stock' ? 'selected' : '' ?>>Urut: Stok</option>
                <option value="created_at" <?= $sort_by === 'created_at' ? 'selected' : '' ?>>Urut: Tanggal</option>
            </select>
        </div>

        <!-- Sorting Direction -->
        <div style="min-width: 110px;">
            <select name="sort_dir" class="form-control">
                <option value="ASC" <?= $order_direction === 'ASC' ? 'selected' : '' ?>>A &rarr; Z (ASC)</option>
                <option value="DESC" <?= $order_direction === 'DESC' ? 'selected' : '' ?>>Z &rarr; A (DESC)</option>
            </select>
        </div>

        <button type="submit" class="btn btn-secondary">
            Terapkan
        </button>

        <?php if ($search !== '' || $category_id !== '' || $sort_by !== 'name' || $sort_dir !== 'ASC'): ?>
            <a href="products.php" class="btn btn-secondary btn-sm" title="Reset filter">
                ✕ Reset
            </a>
        <?php endif; ?>
    </form>

    <div style="font-size: 0.85rem; color: var(--text-muted);">
        Menampilkan <strong><?= count($products) ?></strong> dari <strong><?= $total_rows ?></strong> item
    </div>
</div>

<!-- Tabel Listing Produk -->
<div class="table-responsive">
    <table class="custom-table">
        <thead>
            <tr>
                <th>SKU</th>
                <th>Nama Produk</th>
                <th>Kategori</th>
                <th>Harga Satuan</th>
                <th>Stok / Min</th>
                <th>Status</th>
                <th style="text-align: right;">Aksi Tindakan</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($products)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 36px; color: var(--text-muted);">
                        🔍 Tidak ditemukan data produk yang cocok dengan kriteria pencarian atau filter.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($products as $p): ?>
                    <tr>
                        <td class="mono" style="color: var(--cyan-neon); font-weight: 600;">
                            <?= e($p['sku']) ?>
                        </td>
                        <td>
                            <strong><?= e($p['name']) ?></strong>
                        </td>
                        <td>
                            <span class="badge badge-purple"><?= e($p['category_name']) ?></span>
                        </td>
                        <td class="mono" style="color: #38bdf8;">
                            <?= format_currency($p['price']) ?>
                        </td>
                        <td>
                            <strong style="color: #fff;"><?= (int)$p['stock'] ?></strong>
                            <small style="color: var(--text-dim);">/ min <?= (int)$p['minimum_stock'] ?></small>
                        </td>
                        <td>
                            <?= render_stock_badge($p['stock'], $p['minimum_stock']) ?>
                        </td>
                        <td style="text-align: right; white-space: nowrap;">
                            <a href="product_detail.php?id=<?= $p['id'] ?>" class="btn btn-secondary btn-sm" title="Lihat detail & histori">
                                👁️ Detail
                            </a>
                            
                            <?php if (is_admin()): ?>
                                <a href="product_edit.php?id=<?= $p['id'] ?>" class="btn btn-secondary btn-sm" style="color: #fbbf24;" title="Edit data produk">
                                    ✏️ Edit
                                </a>
                                <a href="product_delete.php?id=<?= $p['id'] ?>" class="btn btn-danger btn-sm" 
                                   onclick="return confirm('Apakah Anda yakin ingin menghapus produk [<?= e($p['sku']) ?>]? Jika produk memiliki histori transaksi, penghapusan akan diblokir oleh aturan referensial.');" 
                                   title="Hapus produk">
                                    🗑️ Hapus
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Pagination Navigasi (Slide 17) -->
<?php if ($total_pages > 1): ?>
    <div class="pagination">
        <!-- Tombol Previous -->
        <a href="<?= build_query_url(['page' => max(1, $page - 1)]) ?>" 
           class="page-link <?= $page <= 1 ? 'disabled' : '' ?>">
            &laquo; Prev
        </a>

        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="<?= build_query_url(['page' => $i]) ?>" 
               class="page-link <?= $i === $page ? 'active' : '' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>

        <!-- Tombol Next -->
        <a href="<?= build_query_url(['page' => min($total_pages, $page + 1)]) ?>" 
           class="page-link <?= $page >= $total_pages ? 'disabled' : '' ?>">
            Next &raquo;
        </a>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/views/footer.php'; ?>
