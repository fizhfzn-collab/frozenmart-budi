<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('pages/supplier/index.php');
}

verify_csrf();

$id = post_int('id');

if ($id <= 0) {
    flash('error', 'Supplier yang ingin dihapus tidak valid.');
    redirect('pages/supplier/index.php');
}

$pdo = db();
$stmt = $pdo->prepare('SELECT nama FROM supplier WHERE id = ?');
$stmt->execute([$id]);
$supplier = $stmt->fetch();

if (!$supplier) {
    flash('error', 'Supplier tidak ditemukan atau sudah dihapus.');
    redirect('pages/supplier/index.php');
}

$cek = $pdo->prepare('SELECT COUNT(*) FROM produk WHERE supplier_id = ?');
$cek->execute([$id]);
$jumlah = (int)$cek->fetchColumn();

if ($jumlah > 0) {
    flash('error', 'Supplier “' . $supplier['nama'] . '” masih menyuplai ' . $jumlah . ' produk, jadi belum bisa dihapus.');
    redirect('pages/supplier/index.php');
}

try {
    $hapus = $pdo->prepare('DELETE FROM supplier WHERE id = ?');
    $hapus->execute([$id]);
    flash('success', 'Supplier “' . $supplier['nama'] . '” berhasil dihapus.');
} catch (PDOException $exception) {
    error_log('Gagal menghapus supplier: ' . $exception->getMessage());
    flash('error', 'Supplier gagal dihapus karena masih terhubung dengan data lain.');
}

redirect('pages/supplier/index.php');
