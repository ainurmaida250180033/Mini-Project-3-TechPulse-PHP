<?php
/**
 * Category Management (Master Data)
 * Mengelola kategori implan & validasi referential integrity (Slide 07 & Slide 09)
 */
$page_title = 'Kelola Kategori Produk';
require_once __DIR__ . '/views/header.php';

$errors = [];
$action = $_GET['action'] ?? 'list';
$edit_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// Proses Simpan / Update Kategori (Khusus Admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_admin();

    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    validate_required($name, 'Nama Kategori', $errors);

    if (empty($errors)) {
        // Cek duplikasi nama
        $dup_sql = "SELECT id FROM categories WHERE name = :name" . ($id ? " AND id != :id" : "");
        $dup_params = [':name' => $name];
        if ($id) $dup_params[':id'] = $id;

        $dup_stmt = $pdo->prepare($dup_sql);
        $dup_stmt->execute($dup_params);
        if ($dup_stmt->fetch()) {
            $errors[] = "Kategori dengan nama '{$name}' sudah ada.";
        }
    }

    if (empty($errors)) {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE categories SET name = :name, description = :description WHERE id = :id");
            $stmt->execute([':name' => $name, ':description' => $description, ':id' => $id]);
            set_flash('success', "Kategori '{$name}' berhasil diperbarui.");
        } else {
            $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (:name, :description)");
            $stmt->execute([':name' => $name, ':description' => $description]);
            set_flash('success', "Kategori baru '{$name}' berhasil ditambahkan.");
        }
        header('Location: categories.php');
        exit;
    }
}

// Proses Hapus Kategori (Khusus Admin)
if ($action === 'delete' && $edit_id) {
    require_admin();

    // Cek apakah ada produk yang merujuk ke kategori ini (Referential Integrity - RESTRICT)
    $prod_check = $pdo->prepare("SELECT COUNT(*) AS total FROM products WHERE category_id = :id");
    $prod_check->execute([':id' => $edit_id]);
    $has_products = (int)$prod_check->fetch()['total'];

    if ($has_products > 0) {
        set_flash('danger', "Gagal Menghapus: Terdapat {$has_products} produk yang terhubung ke kategori ini. Kategori tidak dapat dihapus karena aturan Referential Integrity (RESTRICT).");
    } else {
        $del = $pdo->prepare("DELETE FROM categories WHERE id = :id");
        $del->execute([':id' => $edit_id]);
        set_flash('success', "Kategori berhasil dihapus.");
    }
    header('Location: categories.php');
    exit;
}

// Ambil data untuk form edit jika mode edit
$edit_category = null;
if ($action === 'edit' && $edit_id) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = :id");
    $stmt->execute([':id' => $edit_id]);
    $edit_category = $stmt->fetch();
}

// Ambil semua kategori beserta jumlah produknya (LEFT JOIN & COUNT)
$categories = $pdo->query("
    SELECT c.*, COUNT(p.id) AS product_count
    FROM categories c
    LEFT JOIN products p ON p.category_id = c.id
    GROUP BY c.id, c.name, c.description, c.created_at
    ORDER BY c.name ASC
")->fetchAll();
?>

<div class="page-header">
    <div>
        <h1 class="page-title">🏷️ Master Data Kategori</h1>
        <div class="page-subtitle">Struktur klasifikasi hirarki implan, organ sintetis, dan stimulan medis</div>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul style="margin: 0; padding-left: 20px;">
            <?php foreach ($errors as $e): ?>
                <li><?= e($e) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="grid-cols-2">
    <!-- Form Tambah / Edit Kategori (Hanya Admin yang bisa submit) -->
    <div class="card">
        <div class="card-title">
            <span><?= $edit_category ? '✏️ Edit Kategori' : '➕ Tambah Kategori Baru' ?></span>
            <?php if ($edit_category): ?>
                <a href="categories.php" class="btn btn-secondary btn-sm">Batal Edit</a>
            <?php endif; ?>
        </div>

        <?php if (!is_admin()): ?>
            <div class="alert alert-info">
                ℹ️ Anda login sebagai <strong>Staff</strong> (Mode Baca Saja). Penambahan dan perubahan kategori hanya dapat dilakukan oleh <strong>Admin</strong>.
            </div>
        <?php else: ?>
            <form action="categories.php" method="POST">
                <?php if ($edit_category): ?>
                    <input type="hidden" name="id" value="<?= $edit_category['id'] ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label class="form-label" for="name">Nama Kategori *</label>
                    <input type="text" id="name" name="name" class="form-control" 
                           value="<?= e($edit_category['name'] ?? '') ?>" placeholder="Misal: Neural Augmentations" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="description">Deskripsi Singkat</label>
                    <textarea id="description" name="description" class="form-control" 
                              placeholder="Deskripsi jenis produk dalam kategori ini..."><?= e($edit_category['description'] ?? '') ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary">
                    <?= $edit_category ? '💾 Simpan Perubahan' : '➕ Tambahkan Kategori' ?>
                </button>
            </form>
        <?php endif; ?>
    </div>

    <!-- Tabel Daftar Kategori -->
    <div class="card">
        <div class="card-title">
            <span>Daftar Kategori Terdaftar</span>
            <span class="badge badge-purple"><?= count($categories) ?> Kategori</span>
        </div>

        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Nama Kategori</th>
                        <th>Jumlah Produk</th>
                        <?php if (is_admin()): ?>
                            <th style="text-align: right;">Aksi</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td>
                                <strong><?= e($cat['name']) ?></strong><br>
                                <small style="color: var(--text-muted);"><?= e($cat['description'] ?: '-') ?></small>
                            </td>
                            <td>
                                <span class="badge badge-cyan"><?= (int)$cat['product_count'] ?> Produk</span>
                            </td>
                            <?php if (is_admin()): ?>
                                <td style="text-align: right; white-space: nowrap;">
                                    <a href="categories.php?action=edit&id=<?= $cat['id'] ?>" class="btn btn-secondary btn-sm" style="color: #fbbf24;">
                                        ✏️ Edit
                                    </a>
                                    <a href="categories.php?action=delete&id=<?= $cat['id'] ?>" class="btn btn-danger btn-sm" 
                                       onclick="return confirm('Hapus kategori <?= e($cat['name']) ?>? Jika memiliki produk, penghapusan akan dicegah.');">
                                        🗑️
                                    </a>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/views/footer.php'; ?>
