<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('pages/produk/index.php');
}

verify_csrf();

$id = post_int('id');

if ($id <= 0) {
    flash('error', 'Produk yang ingin dihapus tidak valid.');
    redirect('pages/produk/index.php');
}

$pdo = db();
$stmt = $pdo->prepare('SELECT nama FROM produk WHERE id = ?');
$stmt->execute([$id]);
$produk = $stmt->fetch();

if (!$produk) {
    flash('error', 'Produk tidak ditemukan atau sudah dihapus.');
    redirect('pages/produk/index.php');
}

try {
    $hapus = $pdo->prepare('DELETE FROM produk WHERE id = ?');
    $hapus->execute([$id]);
    flash('success', 'Produk “' . $produk['nama'] . '” berhasil dihapus.');
} catch (PDOException $exception) {
    error_log('Gagal menghapus produk: ' . $exception->getMessage());
    flash('error', 'Produk gagal dihapus karena masih terhubung dengan data lain.');
}

redirect('pages/produk/index.php');
