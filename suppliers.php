<?php
/**
 * Supplier Management (Master Data)
 * Mengelola data pemasok vendor dan relasi many-to-many produk
 */
$page_title = 'Kelola Vendor & Supplier';
require_once __DIR__ . '/views/header.php';

$errors = [];
$action = $_GET['action'] ?? 'list';
$edit_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// Proses Simpan / Update Supplier (Admin Only)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_admin();

    $name           = trim($_POST['name'] ?? '');
    $contact_person = trim($_POST['contact_person'] ?? '');
    $phone          = trim($_POST['phone'] ?? '');
    $email          = trim($_POST['email'] ?? '');
    $address        = trim($_POST['address'] ?? '');
    $id             = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    validate_required($name, 'Nama Vendor / Supplier', $errors);

    if (empty($errors)) {
        if ($id) {
            $stmt = $pdo->prepare("
                UPDATE suppliers 
                SET name = :name, contact_person = :cp, phone = :phone, email = :email, address = :addr 
                WHERE id = :id
            ");
            $stmt->execute([
                ':name'  => $name,
                ':cp'    => $contact_person,
                ':phone' => $phone,
                ':email' => $email,
                ':addr'  => $address,
                ':id'    => $id
            ]);
            set_flash('success', "Data supplier '{$name}' berhasil diperbarui.");
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO suppliers (name, contact_person, phone, email, address)
                VALUES (:name, :cp, :phone, :email, :addr)
            ");
            $stmt->execute([
                ':name'  => $name,
                ':cp'    => $contact_person,
                ':phone' => $phone,
                ':email' => $email,
                ':addr'  => $address
            ]);
            set_flash('success', "Supplier baru '{$name}' berhasil ditambahkan.");
        }
        header('Location: suppliers.php');
        exit;
    }
}

// Proses Hapus Supplier (Admin Only)
if ($action === 'delete' && $edit_id) {
    require_admin();

    $del = $pdo->prepare("DELETE FROM suppliers WHERE id = :id");
    $del->execute([':id' => $edit_id]);
    set_flash('success', "Supplier berhasil dihapus.");
    header('Location: suppliers.php');
    exit;
}

$edit_supplier = null;
if ($action === 'edit' && $edit_id) {
    $stmt = $pdo->prepare("SELECT * FROM suppliers WHERE id = :id");
    $stmt->execute([':id' => $edit_id]);
    $edit_supplier = $stmt->fetch();
}

// Ambil semua data supplier beserta jumlah jenis produk yang dipasok
$suppliers = $pdo->query("
    SELECT s.*, COUNT(ps.product_id) AS total_supplied_products
    FROM suppliers s
    LEFT JOIN product_suppliers ps ON ps.supplier_id = s.id
    GROUP BY s.id, s.name, s.contact_person, s.phone, s.email, s.address, s.created_at
    ORDER BY s.name ASC
")->fetchAll();
?>

<div class="page-header">
    <div>
        <h1 class="page-title">🏢 Master Data Vendor & Supplier</h1>
        <div class="page-subtitle">Jejaring vendor manufaktur hardware dan distributor resmi komponen PC</div>
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
    <!-- Form Tambah/Edit Supplier -->
    <div class="card">
        <div class="card-title">
            <span><?= $edit_supplier ? '✏️ Edit Data Supplier' : '➕ Tambah Supplier Baru' ?></span>
            <?php if ($edit_supplier): ?>
                <a href="suppliers.php" class="btn btn-secondary btn-sm">Batal Edit</a>
            <?php endif; ?>
        </div>

        <?php if (!is_admin()): ?>
            <div class="alert alert-info">
                ℹ️ Anda login sebagai <strong>Staff</strong> (Mode Baca Saja). Penambahan dan perubahan supplier hanya dapat dilakukan oleh <strong>Admin</strong>.
            </div>
        <?php else: ?>
            <form action="suppliers.php" method="POST">
                <?php if ($edit_supplier): ?>
                    <input type="hidden" name="id" value="<?= $edit_supplier['id'] ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label class="form-label" for="name">Nama Vendor / Perusahaan *</label>
                    <input type="text" id="name" name="name" class="form-control" 
                           value="<?= e($edit_supplier['name'] ?? '') ?>" placeholder="Misal: ASUS ROG Global Distribution" required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                    <div class="form-group">
                        <label class="form-label" for="contact_person">Contact Person (PIC)</label>
                        <input type="text" id="contact_person" name="contact_person" class="form-control" 
                               value="<?= e($edit_supplier['contact_person'] ?? '') ?>" placeholder="Nama penanggung jawab">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="phone">Nomor Telepon / Hotline</label>
                        <input type="text" id="phone" name="phone" class="form-control mono" 
                               value="<?= e($edit_supplier['phone'] ?? '') ?>" placeholder="+62-...">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">Alamat Email Resmi</label>
                    <input type="email" id="email" name="email" class="form-control" 
                           value="<?= e($edit_supplier['email'] ?? '') ?>" placeholder="supply@vendor.corp">
                </div>

                <div class="form-group">
                    <label class="form-label" for="address">Alamat Kantor / Depot Fasilitas</label>
                    <textarea id="address" name="address" class="form-control" 
                              placeholder="Lokasi fasilitas manufaktur atau drop point..."><?= e($edit_supplier['address'] ?? '') ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary">
                    <?= $edit_supplier ? '💾 Simpan Perubahan' : '➕ Daftarkan Supplier' ?>
                </button>
            </form>
        <?php endif; ?>
    </div>

    <!-- Tabel Daftar Supplier -->
    <div class="card">
        <div class="card-title">
            <span>Daftar Supplier Terverifikasi</span>
            <span class="badge badge-cyan"><?= count($suppliers) ?> Vendor</span>
        </div>

        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Nama Vendor</th>
                        <th>Kontak PIC</th>
                        <th>Produk</th>
                        <?php if (is_admin()): ?>
                            <th style="text-align: right;">Aksi</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($suppliers as $s): ?>
                        <tr>
                            <td>
                                <strong><?= e($s['name']) ?></strong><br>
                                <small style="color: var(--text-muted);"><?= e($s['address'] ?: '-') ?></small>
                            </td>
                            <td>
                                <strong><?= e($s['contact_person'] ?: '-') ?></strong><br>
                                <small class="mono" style="color: var(--cyan-neon);"><?= e($s['phone'] ?: '') ?></small><br>
                                <small style="color: #94a3b8;"><?= e($s['email'] ?: '') ?></small>
                            </td>
                            <td>
                                <span class="badge badge-purple"><?= (int)$s['total_supplied_products'] ?> Jenis</span>
                            </td>
                            <?php if (is_admin()): ?>
                                <td style="text-align: right; white-space: nowrap;">
                                    <a href="suppliers.php?action=edit&id=<?= $s['id'] ?>" class="btn btn-secondary btn-sm" style="color: #fbbf24;">
                                        ✏️ Edit
                                    </a>
                                    <a href="suppliers.php?action=delete&id=<?= $s['id'] ?>" class="btn btn-danger btn-sm" 
                                       onclick="return confirm('Hapus supplier <?= e($s['name']) ?>?');">
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
