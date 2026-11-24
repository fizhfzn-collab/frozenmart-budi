<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('pages/kategori/index.php');
}

verify_csrf();

$id = post_int('id');

if ($id <= 0) {
    flash('error', 'Kategori yang ingin dihapus tidak valid.');
    redirect('pages/kategori/index.php');
}

$pdo = db();
$stmt = $pdo->prepare('SELECT nama FROM kategori WHERE id = ?');
$stmt->execute([$id]);
$kategori = $stmt->fetch();

if (!$kategori) {
    flash('error', 'Kategori tidak ditemukan atau sudah dihapus.');
    redirect('pages/kategori/index.php');
}

$cek = $pdo->prepare('SELECT COUNT(*) FROM produk WHERE kategori_id = ?');
$cek->execute([$id]);
$jumlah = (int)$cek->fetchColumn();

if ($jumlah > 0) {
    flash('error', 'Kategori “' . $kategori['nama'] . '” masih dipakai oleh ' . $jumlah . ' produk, jadi belum bisa dihapus.');
    redirect('pages/kategori/index.php');
}

try {
    $hapus = $pdo->prepare('DELETE FROM kategori WHERE id = ?');
    $hapus->execute([$id]);
    flash('success', 'Kategori “' . $kategori['nama'] . '” berhasil dihapus.');
} catch (PDOException $exception) {
    error_log('Gagal menghapus kategori: ' . $exception->getMessage());
    flash('error', 'Kategori gagal dihapus karena masih terhubung dengan data lain.');
}

redirect('pages/kategori/index.php');
