<?php
/**
 * Output Escaping & Formatting Helpers
 * Mencegah XSS (Cross-Site Scripting) sesuai Slide 20
 */

/**
 * Escape string untuk output HTML yang aman
 * Mencegah serangan XSS (Cross-Site Scripting)
 */
function e($string) {
    if ($string === null) {
        return '';
    }
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}

/**
 * Format angka ke mata uang (IDR / Cyberpunk Eddies)
 */
function format_currency($amount) {
    return 'Rp ' . number_format((float)$amount, 0, ',', '.');
}

/**
 * Format tanggal dan waktu ke format Indonesia
 */
function format_datetime($datetime) {
    if (!$datetime) return '-';
    $time = strtotime($datetime);
    return date('d M Y, H:i', $time);
}

/**
 * Format status stok menjadi lencana HTML dengan badge warna
 */
function render_stock_badge($stock, $minimum_stock) {
    $stock = (int)$stock;
    $minimum_stock = (int)$minimum_stock;

    if ($stock <= 0) {
        return '<span class="badge badge-danger">Habis (0)</span>';
    } elseif ($stock <= $minimum_stock) {
        return '<span class="badge badge-warning">Kritis (' . $stock . ')</span>';
    } else {
        return '<span class="badge badge-success">Aman (' . $stock . ')</span>';
    }
}
