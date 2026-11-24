-- ============================================================
--  FrostMart — Aplikasi Pencatatan Produk Frozen Food Pak Budi
--  Database khusus versi FrostMart (berbeda dari projek lain).
--  Import file ini lewat phpMyAdmin / MySQL CLI.
-- ============================================================

CREATE DATABASE IF NOT EXISTS frozenmart_budi
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE frozenmart_budi;

CREATE TABLE IF NOT EXISTS kategori (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nama VARCHAR(100) NOT NULL,
    deskripsi VARCHAR(255) NOT NULL DEFAULT '',
    dibuat_pada TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_kategori_nama (nama)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS supplier (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nama VARCHAR(120) NOT NULL,
    kontak VARCHAR(100) NOT NULL DEFAULT '',
    telepon VARCHAR(30) NOT NULL DEFAULT '',
    alamat VARCHAR(255) NOT NULL DEFAULT '',
    dibuat_pada TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_supplier_nama (nama)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS produk (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    kode VARCHAR(20) NOT NULL,
    nama VARCHAR(150) NOT NULL,
    kategori_id INT UNSIGNED NOT NULL,
    supplier_id INT UNSIGNED NOT NULL,
    harga DECIMAL(12, 2) NOT NULL,
    stok INT UNSIGNED NOT NULL DEFAULT 0,
    satuan VARCHAR(20) NOT NULL DEFAULT 'pack',
    dibuat_pada TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    diubah_pada TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_produk_kode (kode),
    KEY idx_produk_kategori (kategori_id),
    KEY idx_produk_supplier (supplier_id),
    CONSTRAINT fk_produk_kategori FOREIGN KEY (kategori_id)
        REFERENCES kategori (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_produk_supplier FOREIGN KEY (supplier_id)
        REFERENCES supplier (id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Data contoh kategori
-- ------------------------------------------------------------
INSERT INTO kategori (id, nama, deskripsi) VALUES
    (1, 'Daging & Unggas', 'Ayam, sapi, kambing, dan olahan daging beku.'),
    (2, 'Seafood', 'Ikan, udang, cumi, dan hasil laut beku.'),
    (3, 'Sayur & Buah', 'Sayuran dan buah potong yang dibekukan.'),
    (4, 'Makanan Siap Saji', 'Nugget, sosis, pizza, dan camilan beku.'),
    (5, 'Bakery & Pastry', 'Roti, pastry, dan kue beku.'),
    (6, 'Es Krim & Minuman', 'Es krim, yoghurt beku, dan minuman dingin.')
ON DUPLICATE KEY UPDATE
    nama = VALUES(nama),
    deskripsi = VALUES(deskripsi);

-- ------------------------------------------------------------
-- Data contoh supplier
-- ------------------------------------------------------------
INSERT INTO supplier (id, nama, kontak, telepon, alamat) VALUES
    (1, 'PT Artha Frozen Nusantara', 'Pak Arif Nugraha', '0812-1111-2222', 'Jl. Industri Raya No. 18, Tangerang'),
    (2, 'CV Bahari Seafood', 'Ibu Sari Wulandari', '0813-3333-4444', 'Jl. Pelabuhan Muara Baru No. 7, Jakarta Utara'),
    (3, 'UD Tani Segar', 'Pak Slamet Riyadi', '0856-5555-6666', 'Jl. Raya Lembang KM 8, Bandung'),
    (4, 'Toko Sumber Rasa', 'Ibu Dewi Lestari', '0878-7777-8888', 'Jl. Pasar Baru No. 21, Bekasi'),
    (5, 'Dapur Boga Beku', 'Mas Raka Pratama', '0821-9999-0000', 'Jl. Cibaduyut No. 45, Bandung')
ON DUPLICATE KEY UPDATE
    nama = VALUES(nama),
    kontak = VALUES(kontak),
    telepon = VALUES(telepon),
    alamat = VALUES(alamat);

-- ------------------------------------------------------------
-- Data contoh produk (14 SKU)
-- ------------------------------------------------------------
INSERT INTO produk (id, kode, nama, kategori_id, supplier_id, harga, stok, satuan) VALUES
    (1, 'FRZ-001', 'Ayam Fillet Frozen', 1, 1, 38000, 24, 'kg'),
    (2, 'FRZ-002', 'Nugget Ayam Original', 4, 1, 32000, 42, 'pack'),
    (3, 'FRZ-003', 'Sosis Sapi Premium', 1, 1, 45000, 8, 'pack'),
    (4, 'FRZ-004', 'Daging Sapi Slice', 1, 4, 118000, 11, 'kg'),
    (5, 'FRZ-005', 'Udang Vaname Kupas', 2, 2, 95000, 16, 'kg'),
    (6, 'FRZ-006', 'Ikan Tongkol Potong', 2, 2, 62000, 21, 'kg'),
    (7, 'FRZ-007', 'Cumi Ring Frozen', 2, 2, 78000, 6, 'pack'),
    (8, 'FRZ-008', 'Brokoli Frozen', 3, 3, 24000, 30, 'pack'),
    (9, 'FRZ-009', 'Wortel Potong Beku', 3, 3, 18000, 36, 'kg'),
    (10, 'FRZ-010', 'Edamame Kupas', 3, 3, 26000, 28, 'pack'),
    (11, 'FRZ-011', 'Pizza Beef Mozzarella', 4, 4, 52000, 13, 'pcs'),
    (12, 'FRZ-012', 'Risoles Ragout', 4, 4, 35000, 19, 'pack'),
    (13, 'FRZ-013', 'Croissant Butter', 5, 5, 28000, 9, 'pack'),
    (14, 'FRZ-014', 'Es Krim Vanilla', 6, 5, 21000, 0, 'box')
ON DUPLICATE KEY UPDATE
    kode = VALUES(kode),
    nama = VALUES(nama),
    kategori_id = VALUES(kategori_id),
    supplier_id = VALUES(supplier_id),
    harga = VALUES(harga),
    stok = VALUES(stok),
    satuan = VALUES(satuan);
