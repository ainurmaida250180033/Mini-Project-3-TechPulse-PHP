<?php
/**
 * Stock Movement Audit History Log
 * Menampilkan catatan pergerakan barang masuk dan keluar secara kronologis
 */
$page_title = 'Histori Mutasi Stok (IN & OUT)';
require_once __DIR__ . '/views/header.php';

// Filter parameter
$type_filter    = trim($_GET['type'] ?? '');
$product_filter = filter_input(INPUT_GET, 'product_id', FILTER_VALIDATE_INT);
$page           = max(1, (int)($_GET['page'] ?? 1));
$per_page       = 10;
$offset         = ($page - 1) * $per_page;

$where = [];
$params = [];

if (in_array($type_filter, ['IN', 'OUT'], true)) {
    $where[] = "sm.type = :type";
    $params[':type'] = $type_filter;
}

if ($product_filter) {
    $where[] = "sm.product_id = :prod_id";
    $params[':prod_id'] = $product_filter;
}

$where_sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Hitung total data
$count_stmt = $pdo->prepare("
    SELECT COUNT(sm.id) AS total
    FROM stock_movements sm
    {$where_sql}
");
$count_stmt->execute($params);
$total_rows = (int)$count_stmt->fetch()['total'];
$total_pages = max(1, (int)ceil($total_rows / $per_page));

// Query data pergerakan stok
$data_sql = "
    SELECT sm.*, p.sku, p.name AS product_name, c.name AS category_name,
           u.full_name AS operator_name, u.role AS operator_role
    FROM stock_movements sm
    JOIN products p ON p.id = sm.product_id
    JOIN categories c ON c.id = p.category_id
    JOIN users u ON u.id = sm.user_id
    {$where_sql}
    ORDER BY sm.created_at DESC
    LIMIT :limit OFFSET :offset
";
$stmt = $pdo->prepare($data_sql);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue(':limit', (int)$per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
$stmt->execute();
$movements = $stmt->fetchAll();

// Daftar produk untuk filter
$products = $pdo->query("SELECT id, sku, name FROM products ORDER BY name ASC")->fetchAll();

function build_history_url($overrides = []) {
    return 'stock_history.php?' . http_build_query(array_merge($_GET, $overrides));
}
?>

<div class="page-header">
    <div>
        <h1 class="page-title">📜 Histori Mutasi Persediaan (Audit Trail)</h1>
        <div class="page-subtitle">Jejak rekaman lengkap seluruh aktivitas barang masuk dan keluar</div>
    </div>
    <div style="display: flex; gap: 10px;">
        <a href="stock_in.php" class="btn btn-primary" style="background: linear-gradient(135deg, #059669, #10b981); color: #fff;">
            📥 Catat Masuk
        </a>
        <a href="stock_out.php" class="btn btn-primary" style="background: linear-gradient(135deg, #e11d48, #f43f5e); color: #fff;">
            📤 Catat Keluar
        </a>
    </div>
</div>

<!-- Filter Bar -->
<div class="filter-bar">
    <form method="GET" action="stock_history.php" class="filter-form">
        <input type="hidden" name="page" value="1">

        <div style="min-width: 160px;">
            <select name="type" class="form-control">
                <option value="">Semua Tipe Mutasi</option>
                <option value="IN" <?= $type_filter === 'IN' ? 'selected' : '' ?>>+ Barang Masuk (IN)</option>
                <option value="OUT" <?= $type_filter === 'OUT' ? 'selected' : '' ?>>- Barang Keluar (OUT)</option>
            </select>
        </div>

        <div style="min-width: 240px; flex: 1;">
            <select name="product_id" class="form-control">
                <option value="">Semua Komponen Hardware</option>
                <?php foreach ($products as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= (int)$product_filter === (int)$p['id'] ? 'selected' : '' ?>>
                        [<?= e($p['sku']) ?>] <?= e($p['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="btn btn-secondary">
            Filter Log
        </button>

        <?php if ($type_filter !== '' || $product_filter): ?>
            <a href="stock_history.php" class="btn btn-secondary btn-sm">
                ✕ Reset
            </a>
        <?php endif; ?>
    </form>

    <div style="font-size: 0.85rem; color: var(--text-muted);">
        Total: <strong><?= $total_rows ?></strong> mutasi tercatat
    </div>
</div>

<!-- Tabel Histori -->
<div class="table-responsive">
    <table class="custom-table">
        <thead>
            <tr>
                <th>Waktu Transaksi</th>
                <th>Tipe</th>
                <th>Produk & Kategori</th>
                <th>Kuantitas</th>
                <th>No. Referensi</th>
                <th>Petugas Pelaksana</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($movements)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 36px;">
                        Tidak ada histori transaksi mutasi stok yang ditemukan.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($movements as $m): ?>
                    <tr>
                        <td>
                            <small class="mono" style="color: var(--text-muted);"><?= format_datetime($m['created_at']) ?></small>
                        </td>
                        <td>
                            <?php if ($m['type'] === 'IN'): ?>
                                <span class="badge badge-success">+ MASUK</span>
                            <?php else: ?>
                                <span class="badge badge-danger">- KELUAR</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="product_detail.php?id=<?= $m['product_id'] ?>" style="color: #fff; text-decoration: none; font-weight: 600;">
                                <?= e($m['product_name']) ?>
                            </a><br>
                            <span class="mono" style="color: var(--cyan-neon); font-size: 0.78rem;">[<?= e($m['sku']) ?>]</span>
                            <span class="badge badge-purple" style="font-size: 0.7rem;"><?= e($m['category_name']) ?></span>
                        </td>
                        <td class="mono" style="font-size: 1.1rem; font-weight: 700; color: <?= $m['type'] === 'IN' ? '#34d399' : '#fb7185' ?>;">
                            <?= $m['type'] === 'IN' ? '+' : '-' ?><?= (int)$m['quantity'] ?>
                        </td>
                        <td class="mono">
                            <small><?= e($m['reference_no'] ?: '-') ?></small>
                        </td>
                        <td>
                            <strong><?= e($m['operator_name']) ?></strong><br>
                            <small class="badge badge-cyan" style="font-size: 0.65rem;"><?= strtoupper(e($m['operator_role'])) ?></small>
                        </td>
                        <td>
                            <small style="color: var(--text-muted);"><?= e($m['notes'] ?: '-') ?></small>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
    <div class="pagination">
        <a href="<?= build_history_url(['page' => max(1, $page - 1)]) ?>" 
           class="page-link <?= $page <= 1 ? 'disabled' : '' ?>">&laquo; Prev</a>

        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="<?= build_history_url(['page' => $i]) ?>" 
               class="page-link <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>

        <a href="<?= build_history_url(['page' => min($total_pages, $page + 1)]) ?>" 
           class="page-link <?= $page >= $total_pages ? 'disabled' : '' ?>">Next &raquo;</a>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/views/footer.php'; ?>
