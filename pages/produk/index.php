<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';

$pdo = db();

$cari = query('q');
$filterKategori = query_int('kategori');
$filterSupplier = query_int('supplier');
$filterStatus = query('status');
$halaman = max(1, query_int('halaman'));
$perHalaman = 8;

if (!in_array($filterStatus, ['', 'aman', 'menipis', 'habis'], true)) {
    $filterStatus = '';
}

$kategoriList = $pdo->query('SELECT id, nama FROM kategori ORDER BY nama ASC')->fetchAll();
$supplierList = $pdo->query('SELECT id, nama FROM supplier ORDER BY nama ASC')->fetchAll();

$where = [];
$params = [];

if ($cari !== '') {
    $where[] = '(p.nama LIKE :cari_nama OR p.kode LIKE :cari_kode)';
    $params[':cari_nama'] = '%' . $cari . '%';
    $params[':cari_kode'] = '%' . $cari . '%';
}

if ($filterKategori > 0) {
    $where[] = 'p.kategori_id = :kategori';
    $params[':kategori'] = $filterKategori;
}

if ($filterSupplier > 0) {
    $where[] = 'p.supplier_id = :supplier';
    $params[':supplier'] = $filterSupplier;
}

if ($filterStatus === 'aman') {
    $where[] = 'p.stok >= 10';
} elseif ($filterStatus === 'menipis') {
    $where[] = 'p.stok > 0 AND p.stok < 10';
} elseif ($filterStatus === 'habis') {
    $where[] = 'p.stok = 0';
}

$whereSql = $where !== [] ? ' WHERE ' . implode(' AND ', $where) : '';

$countStmt = $pdo->prepare('SELECT COUNT(*) FROM produk p' . $whereSql);
$countStmt->execute($params);
$totalData = (int)$countStmt->fetchColumn();
$totalHalaman = max(1, (int)ceil($totalData / $perHalaman));
$halaman = min($halaman, $totalHalaman);
$offset = ($halaman - 1) * $perHalaman;

$dataStmt = $pdo->prepare(
    'SELECT p.*, k.nama AS kategori, s.nama AS supplier
     FROM produk p
     INNER JOIN kategori k ON k.id = p.kategori_id
     INNER JOIN supplier s ON s.id = p.supplier_id
     ' . $whereSql . '
     ORDER BY p.id DESC
     LIMIT :limit OFFSET :offset'
);
foreach ($params as $key => $value) {
    $dataStmt->bindValue($key, $value, PDO::PARAM_STR);
}
$dataStmt->bindValue(':limit', $perHalaman, PDO::PARAM_INT);
$dataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$dataStmt->execute();
$produkList = $dataStmt->fetchAll();

$filterAktif = array_filter([
    'q' => $cari,
    'kategori' => $filterKategori ?: null,
    'supplier' => $filterSupplier ?: null,
    'status' => $filterStatus ?: null,
], static fn($item) => $item !== null && $item !== '');

$halamanUrl = static function (int $nomor) use ($filterAktif): string {
    $filterAktif['halaman'] = $nomor;

    return base_url('pages/produk/index.php?' . http_build_query($filterAktif));
};

$dari = $totalData === 0 ? 0 : $offset + 1;
$sampai = min($offset + $perHalaman, $totalData);

$statusLabels = [
    'aman' => 'Stok aman',
    'menipis' => 'Stok menipis',
    'habis' => 'Stok habis',
];

$pageTitle = 'Daftar Produk';
$pageSubtitle = 'Cari, filter, dan kelola seluruh produk frozen food.';
$activeNav = 'produk';

require __DIR__ . '/../../partials/header.php';
?>

<div class="page-intro">
    <div class="breadcrumb">
        <a href="<?= e(base_url('index.php')) ?>">Ringkasan</a>
        <?= icon('chevron-right') ?>
        <span>Produk</span>
    </div>
    <div class="page-intro__row">
        <div>
            <h2><?= angka_ribuan($totalData) ?> produk ditemukan</h2>
            <p>Gunakan filter kategori dan supplier untuk melihat kelompok produk tertentu.</p>
        </div>
        <a class="btn btn--primary" href="<?= e(base_url('pages/produk/form.php')) ?>">
            <?= icon('plus') ?> Tambah produk
        </a>
    </div>
</div>

<form class="filter-card" method="get" action="<?= e(base_url('pages/produk/index.php')) ?>">
    <div class="filter-card__search">
        <label for="q">Cari produk</label>
        <div class="input-with-icon">
            <?= icon('search') ?>
            <input id="q" name="q" type="search" value="<?= e($cari) ?>" placeholder="Nama atau kode produk…" autocomplete="off">
        </div>
    </div>

    <div class="filter-card__group">
        <div class="field field--compact">
            <label for="kategori">Kategori</label>
            <select id="kategori" name="kategori">
                <option value="">Semua kategori</option>
                <?php foreach ($kategoriList as $kategori): ?>
                    <option value="<?= (int)$kategori['id'] ?>" <?= $filterKategori === (int)$kategori['id'] ? 'selected' : '' ?>>
                        <?= e($kategori['nama']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field field--compact">
            <label for="supplier">Supplier</label>
            <select id="supplier" name="supplier">
                <option value="">Semua supplier</option>
                <?php foreach ($supplierList as $supplier): ?>
                    <option value="<?= (int)$supplier['id'] ?>" <?= $filterSupplier === (int)$supplier['id'] ? 'selected' : '' ?>>
                        <?= e($supplier['nama']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field field--compact">
            <label for="status">Status stok</label>
            <select id="status" name="status">
                <option value="">Semua status</option>
                <?php foreach ($statusLabels as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= $filterStatus === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="filter-card__actions">
        <button class="btn btn--dark" type="submit"><?= icon('filter') ?> Terapkan</button>
        <?php if ($filterAktif !== []): ?>
            <a class="btn btn--ghost" href="<?= e(base_url('pages/produk/index.php')) ?>"><?= icon('refresh') ?> Reset</a>
        <?php endif; ?>
    </div>
</form>

<?php if ($filterAktif !== []): ?>
    <div class="active-filters">
        <span>Filter aktif:</span>
        <?php if ($cari !== ''): ?>
            <a class="filter-chip" href="<?= e($halamanUrl(1)) ?>">“<?= e($cari) ?>”</a>
        <?php endif; ?>
        <?php if ($filterKategori > 0): ?>
            <?php foreach ($kategoriList as $kategori): ?>
                <?php if ((int)$kategori['id'] === $filterKategori): ?>
                    <span class="filter-chip"><?= icon('tag') ?> <?= e($kategori['nama']) ?></span>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
        <?php if ($filterSupplier > 0): ?>
            <?php foreach ($supplierList as $supplier): ?>
                <?php if ((int)$supplier['id'] === $filterSupplier): ?>
                    <span class="filter-chip"><?= icon('truck') ?> <?= e($supplier['nama']) ?></span>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
        <?php if ($filterStatus !== ''): ?>
            <span class="filter-chip"><?= icon('alert') ?> <?= e($statusLabels[$filterStatus]) ?></span>
        <?php endif; ?>
    </div>
<?php endif; ?>

<section class="card card--table product-table-card">
    <?php if (count($produkList) === 0): ?>
        <div class="empty-state">
            <?= icon('box') ?>
            <h4>Produk tidak ditemukan</h4>
            <p>Coba ubah kata kunci atau filter yang dipilih.</p>
            <a class="btn btn--soft" href="<?= e(base_url('pages/produk/index.php')) ?>"><?= icon('refresh') ?> Tampilkan semua</a>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                <tr>
                    <th>Produk</th>
                    <th>Kategori</th>
                    <th>Supplier</th>
                    <th class="text-right">Harga</th>
                    <th>Stok</th>
                    <th class="text-right">Aksi</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($produkList as $produk): ?>
                    <?php
                    $stok = (int)$produk['stok'];
                    $status = status_stok($stok);
                    $meter = min(100, max(4, (int)round(($stok / 40) * 100)));
                    ?>
                    <tr>
                        <td>
                            <div class="product-cell">
                                <span class="product-thumb product-thumb--<?= e($status['key']) ?>"><?= icon('box') ?></span>
                                <span>
                                    <strong><?= e($produk['nama']) ?></strong>
                                    <small><?= e($produk['kode']) ?> · <?= e(ucfirst($produk['satuan'])) ?></small>
                                </span>
                            </div>
                        </td>
                        <td><span class="chip"><?= e($produk['kategori']) ?></span></td>
                        <td>
                            <span class="supplier-inline">
                                <span class="avatar avatar--mini"><?= e(inisial($produk['supplier'])) ?></span>
                                <?= e($produk['supplier']) ?>
                            </span>
                        </td>
                        <td class="text-right price"><?= e(rupiah($produk['harga'])) ?></td>
                        <td>
                            <div class="stock-cell">
                                <div class="stock-cell__meta">
                                    <strong><?= angka_ribuan($stok) ?></strong>
                                    <span class="badge badge--<?= e($status['key']) ?>"><?= e($status['label']) ?></span>
                                </div>
                                <span class="stock-meter stock-meter--<?= e($status['key']) ?>">
                                    <i style="width: <?= $meter ?>%"></i>
                                </span>
                            </div>
                        </td>
                        <td class="text-right">
                            <div class="row-actions">
                                <a class="icon-btn" href="<?= e(base_url('pages/produk/form.php?id=' . (int)$produk['id'])) ?>" aria-label="Ubah <?= e($produk['nama']) ?>" title="Ubah">
                                    <?= icon('edit') ?>
                                </a>
                                <form method="post" action="<?= e(base_url('pages/produk/hapus.php')) ?>" data-confirm="Hapus “<?= e($produk['nama']) ?>” dari daftar produk? Data yang sudah dihapus tidak bisa dikembalikan.">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int)$produk['id'] ?>">
                                    <button class="icon-btn icon-btn--danger" type="submit" aria-label="Hapus <?= e($produk['nama']) ?>" title="Hapus">
                                        <?= icon('trash') ?>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <footer class="table-footer">
            <span>Menampilkan <strong><?= $dari ?>–<?= $sampai ?></strong> dari <strong><?= angka_ribuan($totalData) ?></strong> produk</span>
            <?php if ($totalHalaman > 1): ?>
                <nav class="pagination" aria-label="Navigasi halaman produk">
                    <a class="page-btn <?= $halaman <= 1 ? 'is-disabled' : '' ?>" href="<?= e($halamanUrl(max(1, $halaman - 1))) ?>" aria-label="Halaman sebelumnya">
                        <?= icon('chevron-left') ?>
                    </a>
                    <?php
                    $mulai = max(1, $halaman - 2);
                    $selesai = min($totalHalaman, $halaman + 2);
                    ?>
                    <?php if ($mulai > 1): ?>
                        <a class="page-btn" href="<?= e($halamanUrl(1)) ?>">1</a>
                        <?php if ($mulai > 2): ?><span class="page-ellipsis">…</span><?php endif; ?>
                    <?php endif; ?>
                    <?php for ($i = $mulai; $i <= $selesai; $i++): ?>
                        <a class="page-btn <?= $i === $halaman ? 'is-active' : '' ?>" href="<?= e($halamanUrl($i)) ?>" <?= $i === $halaman ? 'aria-current="page"' : '' ?>><?= $i ?></a>
                    <?php endfor; ?>
                    <?php if ($selesai < $totalHalaman): ?>
                        <?php if ($selesai < $totalHalaman - 1): ?><span class="page-ellipsis">…</span><?php endif; ?>
                        <a class="page-btn" href="<?= e($halamanUrl($totalHalaman)) ?>"><?= $totalHalaman ?></a>
                    <?php endif; ?>
                    <a class="page-btn <?= $halaman >= $totalHalaman ? 'is-disabled' : '' ?>" href="<?= e($halamanUrl(min($totalHalaman, $halaman + 1))) ?>" aria-label="Halaman berikutnya">
                        <?= icon('chevron-right') ?>
                    </a>
                </nav>
            <?php endif; ?>
        </footer>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/../../partials/footer.php'; ?>
