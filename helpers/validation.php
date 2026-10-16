<?php
/**
 * Server-Side Validation Helper
 * Melakukan Syntactic dan Semantic Validation sesuai Slide 21
 */

/**
 * Validasi string wajib diisi (tidak boleh kosong)
 */
function validate_required($value, $field_name, &$errors) {
    if ($value === null || trim((string)$value) === '') {
        $errors[] = "Bidang '{$field_name}' wajib diisi.";
        return false;
    }
    return true;
}

/**
 * Validasi bilangan bulat positif (contoh: quantity transaksi > 0)
 */
function validate_positive_int($value, $field_name, &$errors) {
    $filtered = filter_var($value, FILTER_VALIDATE_INT);
    if ($filtered === false || $filtered <= 0) {
        $errors[] = "Jumlah '{$field_name}' harus berupa bilangan bulat lebih dari 0.";
        return false;
    }
    return true;
}

/**
 * Validasi angka tidak boleh negatif (contoh: harga atau stok awal >= 0)
 */
function validate_non_negative_number($value, $field_name, &$errors) {
    if (!is_numeric($value) || (float)$value < 0) {
        $errors[] = "Nilai '{$field_name}' tidak boleh bernilai negatif.";
        return false;
    }
    return true;
}

/**
 * Validasi keunikan SKU (Semantic Validation)
 */
function validate_unique_sku($pdo, $sku, $exclude_product_id, &$errors) {
    $sql = "SELECT id FROM products WHERE sku = :sku";
    $params = [':sku' => trim($sku)];

    if ($exclude_product_id) {
        $sql .= " AND id != :id";
        $params[':id'] = (int)$exclude_product_id;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    if ($stmt->fetch()) {
        $errors[] = "Kode SKU '{$sku}' sudah digunakan oleh produk lain. SKU harus unik.";
        return false;
    }
    return true;
}

/**
 * Validasi allowlist untuk sorting kolom (Slide 16)
 * Mencegah SQL Injection lewat nama kolom ORDER BY
 */
function validate_allowlist($value, array $allowlist, $default) {
    $value = strtolower(trim((string)$value));
    return in_array($value, $allowlist, true) ? $value : $default;
}

/**
 * Validasi arah urutan (ASC / DESC)
 */
function validate_sort_direction($value, $default = 'ASC') {
    $value = strtoupper(trim((string)$value));
    return in_array($value, ['ASC', 'DESC'], true) ? $value : $default;
}
