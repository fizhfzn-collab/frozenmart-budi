<?php
declare(strict_types=1);

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';

$pdo = db();

$totalProduk = (int)$pdo->query('SELECT COUNT(*) FROM produk')->fetchColumn();
$totalKategori = (int)$pdo->query('SELECT COUNT(*) FROM kategori')->fetchColumn();
$totalSupplier = (int)$pdo->query('SELECT COUNT(*) FROM supplier')->fetchColumn();
$totalStok = (int)$pdo->query('SELECT COALESCE(SUM(stok), 0) FROM produk')->fetchColumn();
$nilaiStok = (float)$pdo->query('SELECT COALESCE(SUM(harga * stok), 0) FROM produk')->fetchColumn();
$jumlahMenipis = (int)$pdo->query('SELECT COUNT(*) FROM produk WHERE stok < 10')->fetchColumn();

$produkTerbaru = $pdo->query(
    'SELECT p.id, p.kode, p.nama, p.harga, p.stok, p.satuan, k.nama AS kategori, s.nama AS supplier
     FROM produk p
     INNER JOIN kategori k ON k.id = p.kategori_id
     INNER JOIN supplier s ON s.id = p.supplier_id
     ORDER BY p.id DESC
     LIMIT 5'
)->fetchAll();

$stokMenipis = $pdo->query(
    'SELECT p.id, p.kode, p.nama, p.stok, p.satuan, k.nama AS kategori
     FROM produk p
     INNER JOIN kategori k ON k.id = p.kategori_id
     WHERE p.stok < 10
     ORDER BY p.stok ASC, p.nama ASC
     LIMIT 5'
)->fetchAll();

$sebaranKategori = $pdo->query(
    'SELECT k.id, k.nama, COUNT(p.id) AS jumlah_produk, COALESCE(SUM(p.stok), 0) AS total_stok
     FROM kategori k
     LEFT JOIN produk p ON p.kategori_id = k.id
     GROUP BY k.id, k.nama
     ORDER BY jumlah_produk DESC, k.nama ASC'
)->fetchAll();

$pageTitle = 'Ringkasan Gudang';
$pageSubtitle = 'Semua stok frozen food Pak Budi dalam satu tampilan.';
$activeNav = 'dashboard';

require __DIR__ . '/partials/header.php';
?>

<section class="hero">
    <div class="hero__content">
        <span class="pill pill--hero"><?= icon('spark') ?> Dashboard gudang</span>
        <h2>Stok beku rapi,<br><em>jualan lancar.</em></h2>
        <p>Catat produk, kelompokkan per kategori, dan pantau supplier langganan Pak Budi tanpa buku catatan manual.</p>
        <div class="hero__actions">
            <a class="btn btn--light" href="<?= e(base_url('pages/produk/form.php')) ?>">
                <?= icon('plus') ?> Tambah produk
            </a>
            <a class="btn btn--outline-light" href="<?= e(base_url('pages/produk/index.php')) ?>">
                Lihat semua stok <?= icon('arrow-right') ?>
            </a>
        </div>
    </div>

    <div class="hero__visual" aria-hidden="true">
        <div class="hero__ring hero__ring--one"></div>
        <div class="hero__ring hero__ring--two"></div>
        <span class="hero__snow"><?= icon('snowflake') ?></span>
        <div class="float-card float-card--temp">
            <span class="float-card__icon"><?= icon('snowflake') ?></span>
            <span>
                <small>Suhu gudang</small>
                <strong>-18°C</strong>
            </span>
        </div>
        <div class="float-card float-card--stock">
            <span class="float-card__icon float-card__icon--sun"><?= icon('box') ?></span>
            <span>
                <small>Total stok</small>
                <strong><?= angka_ribuan($totalStok) ?> unit</strong>
            </span>
        </div>
        <div class="float-card float-card--sku">
            <span class="float-card__icon float-card__icon--mint"><?= icon('tag') ?></span>
            <span>
                <small>SKU aktif</small>
                <strong><?= $totalProduk ?> produk</strong>
            </span>
        </div>
    </div>
</section>

<section class="stats-grid" aria-label="Statistik gudang">
    <article class="stat-card stat-card--coral">
        <span class="stat-card__icon"><?= icon('box') ?></span>
        <div class="stat-card__body">
            <span class="stat-card__label">Total produk</span>
            <strong><?= angka_ribuan($totalProduk) ?></strong>
            <small><?= $totalKategori ?> kategori terdaftar</small>
        </div>
        <span class="stat-card__trend"><?= icon('arrow-up-right') ?></span>
    </article>

    <article class="stat-card stat-card--sun">
        <span class="stat-card__icon"><?= icon('clipboard') ?></span>
        <div class="stat-card__body">
            <span class="stat-card__label">Total stok</span>
            <strong><?= angka_ribuan($totalStok) ?></strong>
            <small>unit siap dijual</small>
        </div>
        <span class="stat-card__trend"><?= icon('check') ?></span>
    </article>

    <article class="stat-card stat-card--mint">
        <span class="stat-card__icon"><?= icon('wallet') ?></span>
        <div class="stat-card__body">
            <span class="stat-card__label">Nilai persediaan</span>
            <strong class="stat-card__money"><?= e(rupiah($nilaiStok)) ?></strong>
            <small>harga jual × stok</small>
        </div>
        <span class="stat-card__trend"><?= icon('arrow-up-right') ?></span>
    </article>

    <article class="stat-card stat-card--berry">
        <span class="stat-card__icon"><?= icon('alert') ?></span>
        <div class="stat-card__body">
            <span class="stat-card__label">Perlu restock</span>
            <strong><?= angka_ribuan($jumlahMenipis) ?></strong>
            <small>produk dengan stok &lt; 10</small>
        </div>
        <a class="stat-card__trend" href="<?= e(base_url('pages/produk/index.php?status=menipis')) ?>" aria-label="Lihat produk yang perlu restock">
            <?= icon('arrow-right') ?>
        </a>
    </article>
</section>

<div class="dashboard-grid">
    <section class="card card--pad">
        <header class="section-head">
            <div>
                <span class="eyebrow">Sebaran produk</span>
                <h3>Per kategori</h3>
            </div>
            <a class="text-link" href="<?= e(base_url('pages/kategori/index.php')) ?>">
                Kelola <?= icon('arrow-right') ?>
            </a>
        </header>

        <?php if (count($sebaranKategori) === 0): ?>
            <div class="empty-state empty-state--compact">
                <?= icon('tag') ?>
                <p>Belum ada kategori.</p>
            </div>
        <?php else: ?>
            <div class="category-bars">
                <?php foreach ($sebaranKategori as $index => $kategori): ?>
                    <?php
                    $jumlah = (int)$kategori['jumlah_produk'];
                    $persen = $totalProduk > 0 ? (int)round(($jumlah / $totalProduk) * 100) : 0;
                    ?>
                    <div class="bar-row">
                        <div class="bar-row__meta">
                            <span class="bar-row__name">
                                <i class="dot dot--<?= ($index % 6) + 1 ?>"></i>
                                <?= e($kategori['nama']) ?>
                            </span>
                            <span class="bar-row__value"><?= $jumlah ?> produk</span>
                        </div>
                        <div class="bar-track" role="img" aria-label="<?= e($kategori['nama']) ?>: <?= $jumlah ?> produk">
                            <span class="bar-fill bar-fill--<?= ($index % 6) + 1 ?>" style="width: <?= $persen ?>%"></span>
                        </div>
                        <span class="bar-row__note"><?= angka_ribuan($kategori['total_stok']) ?> unit tersedia · <?= $persen ?>%</span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="card card--pad">
        <header class="section-head">
            <div>
                <span class="eyebrow">Prioritas belanja</span>
                <h3>Stok menipis</h3>
            </div>
            <a class="text-link" href="<?= e(base_url('pages/produk/index.php?status=menipis')) ?>">
                Semua <?= icon('arrow-right') ?>
            </a>
        </header>

        <?php if (count($stokMenipis) === 0): ?>
            <div class="empty-state empty-state--compact">
                <?= icon('check') ?>
                <p>Semua stok dalam kondisi aman.</p>
            </div>
        <?php else: ?>
            <ul class="restock-list">
                <?php foreach ($stokMenipis as $index => $item): ?>
                    <?php $status = status_stok((int)$item['stok']); ?>
                    <li>
                        <span class="restock-rank"><?= str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT) ?></span>
                        <span class="restock-copy">
                            <strong><?= e($item['nama']) ?></strong>
                            <small><?= e($item['kode']) ?> · <?= e($item['kategori']) ?></small>
                        </span>
                        <span class="badge badge--<?= e($status['key']) ?>">
                            <?= (int)$item['stok'] ?> <?= e($item['satuan']) ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
            <a class="btn btn--soft btn--full" href="<?= e(base_url('pages/produk/index.php?status=menipis')) ?>">
                <?= icon('refresh') ?> Siapkan daftar restock
            </a>
        <?php endif; ?>
    </section>
</div>

<section class="card card--pad card--table">
    <header class="section-head">
        <div>
            <span class="eyebrow">Terakhir ditambahkan</span>
            <h3>Produk terbaru</h3>
        </div>
        <a class="btn btn--ghost btn--sm" href="<?= e(base_url('pages/produk/index.php')) ?>">
            Lihat semua <?= icon('arrow-right') ?>
        </a>
    </header>

    <?php if (count($produkTerbaru) === 0): ?>
        <div class="empty-state">
            <?= icon('box') ?>
            <h4>Belum ada produk</h4>
            <p>Mulai catat produk frozen food pertama Pak Budi.</p>
            <a class="btn btn--primary" href="<?= e(base_url('pages/produk/form.php')) ?>">
                <?= icon('plus') ?> Tambah produk
            </a>
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
                    <th class="text-right">Stok</th>
                    <th class="text-right">Aksi</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($produkTerbaru as $produk): ?>
                    <?php $status = status_stok((int)$produk['stok']); ?>
                    <tr>
                        <td>
                            <div class="product-cell">
                                <span class="product-thumb"><?= icon('box') ?></span>
                                <span>
                                    <strong><?= e($produk['nama']) ?></strong>
                                    <small><?= e($produk['kode']) ?></small>
                                </span>
                            </div>
                        </td>
                        <td><span class="chip"><?= e($produk['kategori']) ?></span></td>
                        <td class="text-muted"><?= e($produk['supplier']) ?></td>
                        <td class="text-right price"><?= e(rupiah($produk['harga'])) ?></td>
                        <td class="text-right">
                            <span class="badge badge--<?= e($status['key']) ?>">
                                <?= (int)$produk['stok'] ?> <?= e($produk['satuan']) ?>
                            </span>
                        </td>
                        <td class="text-right">
                            <a class="icon-btn" href="<?= e(base_url('pages/produk/form.php?id=' . (int)$produk['id'])) ?>" aria-label="Ubah <?= e($produk['nama']) ?>">
                                <?= icon('edit') ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
