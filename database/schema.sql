-- ==============================================================================
-- TECHPULSE HARDWARE IMS - INVENTORY MANAGEMENT SYSTEM DATABASE SCHEMA
-- Pertemuan 4: Relational Database + Advanced PHP (Mini Project 3)
-- Author: Ainur Maida Syahri
-- ==============================================================================

CREATE DATABASE IF NOT EXISTS `inventory_techpulse_p4` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `inventory_techpulse_p4`;

-- Drop tables if exists in reverse order of foreign key dependency
DROP TABLE IF EXISTS `stock_movements`;
DROP TABLE IF EXISTS `product_suppliers`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `suppliers`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `user_profiles`;
DROP TABLE IF EXISTS `users`;

-- ------------------------------------------------------------------------------
-- 1. Table: users
-- ------------------------------------------------------------------------------
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `role` ENUM('admin', 'staff') NOT NULL DEFAULT 'staff',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------------------------
-- 2. Table: user_profiles (Relasi One-to-One: Slide 06)
-- ------------------------------------------------------------------------------
CREATE TABLE `user_profiles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL UNIQUE,
    `phone` VARCHAR(50) DEFAULT NULL,
    `department` VARCHAR(100) DEFAULT NULL,
    `bio` TEXT DEFAULT NULL,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------------------------
-- 3. Table: categories (Tabel Induk Relasi One-to-Many: Slide 07)
-- ------------------------------------------------------------------------------
CREATE TABLE `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `description` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------------------------
-- 4. Table: products (Tabel Anak Relasi One-to-Many: Slide 11)
-- ------------------------------------------------------------------------------
CREATE TABLE `products` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT NOT NULL,
    `sku` VARCHAR(50) NOT NULL UNIQUE,
    `name` VARCHAR(150) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `stock` INT NOT NULL DEFAULT 0,
    `minimum_stock` INT NOT NULL DEFAULT 5,
    `image` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ------------------------------------------------------------------------------
-- 5. Table: suppliers
-- ------------------------------------------------------------------------------
CREATE TABLE `suppliers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `contact_person` VARCHAR(100) DEFAULT NULL,
    `phone` VARCHAR(50) DEFAULT NULL,
    `email` VARCHAR(100) DEFAULT NULL,
    `address` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------------------------
-- 6. Table: product_suppliers (Relasi Many-to-Many / Junction Table: Slide 08)
-- ------------------------------------------------------------------------------
CREATE TABLE `product_suppliers` (
    `product_id` INT NOT NULL,
    `supplier_id` INT NOT NULL,
    `supplier_price` DECIMAL(12,2) DEFAULT NULL,
    PRIMARY KEY (`product_id`, `supplier_id`),
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------------------------
-- 7. Table: stock_movements (Histori Transaksi Masuk & Keluar: Slide 10 & 26)
-- ------------------------------------------------------------------------------
CREATE TABLE `stock_movements` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `product_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `type` ENUM('IN', 'OUT') NOT NULL,
    `quantity` INT NOT NULL,
    `reference_no` VARCHAR(50) DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ==============================================================================
-- SEED DATA AWAL UNTUK PENGUJIAN DAN DEMO
-- ==============================================================================

-- 1. Insert Default Users (Password: admin123 & staff123)
INSERT INTO `users` (`id`, `username`, `password_hash`, `full_name`, `role`) VALUES
(1, 'admin', '$2y$10$AdQP9hn1BXU5j6yT.SY/a.xdHbG7E9VYvd5EB8tNqDNGxXCyw/RPO', 'Ainur Maida Syahri', 'admin'),
(2, 'staff', '$2y$10$C.u8VYJvo/ozCQ0b77QbqegWU3.fefxsdlZVNR0X46Q7vJad9sPbO', 'Kevin Pratama', 'staff');

-- 2. Insert User Profiles (One-to-One)
INSERT INTO `user_profiles` (`user_id`, `phone`, `department`, `bio`) VALUES
(1, '+62-812-9876-5432', 'Hardware Operations Overseer', 'Lead Hardware Architect & Administrator TechPulse Logistics.'),
(2, '+62-813-1122-3344', 'Warehouse & Fulfillment', 'Staff Logistik dan Penanggung Jawab Alur Inventori Hardware.');

-- 3. Insert Categories
INSERT INTO `categories` (`id`, `name`, `description`) VALUES
(1, 'Processors (CPU)', 'Prosesor desktop performa tinggi untuk workstation & gaming enthusiast.'),
(2, 'Graphics Cards (GPU)', 'Kartu grafis generasi mutakhir dengan arsitektur ray-tracing dan tensor AI.'),
(3, 'Memory & Storage', 'Modul DDR5 frekuensi tinggi dan solid-state drive Gen 4/5 ultra-cepat.'),
(4, 'Motherboards & PSU', 'Mainboard chipset kelas atas dan power supply modular bersertifikasi 80+ Gold/Platinum.'),
(5, 'Peripherals & Monitors', 'Monitor gaming refresh rate ultra-tinggi dan aksesoris performa pro-grade.');

-- 4. Insert Suppliers
INSERT INTO `suppliers` (`id`, `name`, `contact_person`, `phone`, `email`, `address`) VALUES
(1, 'ASUS ROG Global Distribution', 'Marcus Chen', '+886-2-2894-3447', 'rog-dist@asus.com.tw', 'No. 15, Li-Te Rd., Beitou Dist., Taipei 112, Taiwan'),
(2, 'MSI Gaming International', 'Elena Rostova', '+886-2-3234-5599', 'gaming-supply@msi.com', 'No. 69, Lide St., Zhonghe Dist., New Taipei City, Taiwan'),
(3, 'Corsair Technologies Inc.', 'David Miller', '+1-888-222-4346', 'b2b@corsair.com', '115 North McCarthy Blvd, Milpitas, CA 95035, USA'),
(4, 'Gigabyte AORUS Supply Chain', 'Kenji Takahashi', '+886-2-8912-4000', 'aorus-sales@gigabyte.com', 'No. 6, Baoqiang Rd., Xindian Dist., New Taipei City, Taiwan'),
(5, 'Kingston FURY Enterprise', 'Sarah Jenkins', '+1-877-546-4786', 'fury-logistics@kingston.com', '17600 Newhope Street, Fountain Valley, CA 92708, USA');

-- 5. Insert Products
INSERT INTO `products` (`id`, `category_id`, `sku`, `name`, `description`, `price`, `stock`, `minimum_stock`) VALUES
(1, 1, 'TP-CPU-14900KS', 'Intel Core i9-14900KS Special Edition', '24 Cores (8P+16E), up to 6.2 GHz, 36MB Intel Smart Cache, Socket LGA1700.', 11200000.00, 7, 3),
(2, 1, 'TP-CPU-7950X3D', 'AMD Ryzen 9 7950X3D 3D V-Cache', '16 Cores, 32 Threads, 144MB V-Cache, 5.7 GHz Max Boost, Socket AM5.', 10450000.00, 9, 3),
(3, 2, 'TP-GPU-RTX4090', 'ASUS ROG Strix GeForce RTX 4090 24GB OC', '24GB GDDR6X, Axial-tech fans, 3.5-slot design, diecast frame, PCIe 4.0.', 34500000.00, 4, 2),
(4, 2, 'TP-GPU-RTX4080S', 'MSI GeForce RTX 4080 Super Gaming X Trio', '16GB GDDR6X, Tri Frozr 3 thermal design, TORX Fan 5.0, RGB Mystic Light.', 19800000.00, 6, 3),
(5, 3, 'TP-RAM-D5-64GB', 'Corsair Vengeance RGB DDR5 64GB (2x32GB) 6000MHz', 'CL30 Optimized AMD EXPO & Intel XMP 3.0, Dynamic Ten-Zone RGB Lighting.', 3850000.00, 14, 5),
(6, 3, 'TP-SSD-2TB-REN', 'Kingston FURY Renegade 2TB M.2 PCIe 4.0 NVMe', 'Read up to 7,300MB/s, Write up to 7,000MB/s, Low profile graphene heat spreader.', 2650000.00, 18, 5),
(7, 4, 'TP-MB-Z790-HERO', 'ASUS ROG Maximus Z790 Dark Hero', 'ATX Motherboard, 20+1+2 power stages, Wi-Fi 7, 5x M.2 slots, PCIe 5.0.', 12200000.00, 5, 2),
(8, 4, 'TP-PSU-RM1000X', 'Corsair RM1000x Shift Fully Modular 80+ Gold', '1000W ATX 3.0 & PCIe 5.0 Certified, Side cable interface, Japanese 105C caps.', 3150000.00, 11, 4),
(9, 5, 'TP-MON-FI32QX', 'Gigabyte AORUS FI32Q-X 32" QHD 240Hz Gaming', 'SS IPS Display, 0.3ms MPRT, VESA DisplayHDR 600, KVM switch, 240Hz (OC 270Hz).', 11900000.00, 3, 2),
(10, 5, 'TP-COOL-360R', 'MSI MAG CoreLiquid 360R V2 Liquid Cooler', '360mm Radiator, 3x ARGB Fans, Integrated Pump Design, Rotatable Blockhead.', 1850000.00, 8, 3);

-- 6. Insert Product-Suppliers (Many-to-Many Junction)
INSERT INTO `product_suppliers` (`product_id`, `supplier_id`, `supplier_price`) VALUES
(1, 1, 9800000.00),
(2, 4, 9100000.00),
(3, 1, 31000000.00),
(4, 2, 17500000.00),
(5, 3, 3200000.00),
(5, 5, 3150000.00),
(6, 5, 2100000.00),
(7, 1, 10500000.00),
(8, 3, 2650000.00),
(9, 4, 10200000.00),
(10, 2, 1450000.00);

-- 7. Insert Initial Stock Movements (Histori Transaksi Masuk & Keluar)
INSERT INTO `stock_movements` (`product_id`, `user_id`, `type`, `quantity`, `reference_no`, `notes`) VALUES
(1, 1, 'IN', 10, 'PO-2026-001', 'Penerimaan kontainer batch 1 Intel Core i9-14900KS dari distributor resmi'),
(1, 2, 'OUT', 3, 'SO-2026-001', 'Pengiriman unit custom PC gaming pro order #7712'),
(2, 1, 'IN', 12, 'PO-2026-002', 'Stok masuk AMD Ryzen 9 7950X3D batch Q4'),
(2, 2, 'OUT', 3, 'SO-2026-002', 'Pengeluaran untuk workstation editing studio'),
(3, 1, 'IN', 6, 'PO-2026-003', 'Alokasi batch terbatas ASUS ROG RTX 4090 OC'),
(3, 2, 'OUT', 2, 'SO-2026-003', 'Pesanan esport arena gaming rig'),
(4, 1, 'IN', 8, 'PO-2026-004', 'Restock MSI RTX 4080 Super'),
(4, 2, 'OUT', 2, 'SO-2026-004', 'Pengiriman order retail customer'),
(5, 1, 'IN', 20, 'PO-2026-005', 'Pengadaan Corsair DDR5 64GB kit dari Corsair Tech'),
(5, 2, 'OUT', 6, 'SO-2026-005', 'Pemasangan paket PC simulator'),
(6, 1, 'IN', 25, 'PO-2026-006', 'Pengadaan Kingston FURY SSD NVMe 2TB'),
(6, 2, 'OUT', 7, 'SO-2026-006', 'Distribusi perakitan high-speed storage server'),
(7, 1, 'IN', 6, 'PO-2026-007', 'Pengiriman motherboard ROG Maximus Z790 Dark Hero'),
(7, 2, 'OUT', 1, 'SO-2026-007', 'Rakit PC showcase benchmark'),
(8, 1, 'IN', 15, 'PO-2026-008', 'Penerimaan Corsair RM1000x Shift PSU'),
(8, 2, 'OUT', 4, 'SO-2026-008', 'Pengeluaran komponen power unit untuk order build'),
(9, 1, 'IN', 5, 'PO-2026-009', 'Impor display AORUS FI32Q-X 240Hz monitor'),
(9, 2, 'OUT', 2, 'SO-2026-009', 'Display visual tournament setup'),
(10, 1, 'IN', 10, 'PO-2026-010', 'Pengadaan liquid cooler MSI 360R V2'),
(10, 2, 'OUT', 2, 'SO-2026-010', 'Pemasangan sistem pendingin order customer');
