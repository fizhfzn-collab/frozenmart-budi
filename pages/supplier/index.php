<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';

$pdo = db();
$errors = [];
$editId = query_int('edit');

$form = [
    'id' => 0,
    'nama' => '',
    'kontak' => '',
    'telepon' => '',
    'alamat' => '',
];

if ($editId > 0) {
    $stmt = $pdo->prepare('SELECT id, nama, kontak, telepon, alamat FROM supplier WHERE id = ?');
    $stmt->execute([$editId]);
    $supplierEdit = $stmt->fetch();

    if ($supplierEdit) {
        $form = [
            'id' => (int)$supplierEdit['id'],
            'nama' => $supplierEdit['nama'],
            'kontak' => $supplierEdit['kontak'],
            'telepon' => $supplierEdit['telepon'],
            'alamat' => $supplierEdit['alamat'],
        ];
    } else {
        $editId = 0;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $form = [
        'id' => post_int('id'),
        'nama' => post('nama'),
        'kontak' => post('kontak'),
        'telepon' => post('telepon'),
        'alamat' => post('alamat'),
    ];

    if ($form['nama'] === '') {
        $errors['nama'] = 'Nama supplier wajib diisi.';
    } elseif (strlen($form['nama']) > 120) {
        $errors['nama'] = 'Nama supplier maksimal 120 karakter.';
    }

    if (strlen($form['kontak']) > 100) {
        $errors['kontak'] = 'Nama kontak maksimal 100 karakter.';
    }

    if ($form['telepon'] !== '' && !preg_match('/^[0-9+()\-\s]{7,30}$/', $form['telepon'])) {
        $errors['telepon'] = 'Format telepon tidak valid. Gunakan angka, spasi, +, -, atau tanda kurung.';
    }

    if (strlen($form['alamat']) > 255) {
        $errors['alamat'] = 'Alamat maksimal 255 karakter.';
    }

    if ($form['id'] > 0) {
        $cek = $pdo->prepare('SELECT COUNT(*) FROM supplier WHERE id = ?');
        $cek->execute([$form['id']]);
        if ((int)$cek->fetchColumn() === 0) {
            $errors['umum'] = 'Supplier yang ingin diubah tidak ditemukan.';
        }
    }

    if (!isset($errors['nama'])) {
        $cekNama = $pdo->prepare('SELECT COUNT(*) FROM supplier WHERE nama = ? AND id <> ?');
        $cekNama->execute([$form['nama'], $form['id']]);
        if ((int)$cekNama->fetchColumn() > 0) {
            $errors['nama'] = 'Nama supplier sudah ada. Gunakan nama lain.';
        }
    }

    if ($errors === []) {
        if ($form['id'] > 0) {
            $stmt = $pdo->prepare('UPDATE supplier SET nama = ?, kontak = ?, telepon = ?, alamat = ? WHERE id = ?');
            $stmt->execute([$form['nama'], $form['kontak'], $form['telepon'], $form['alamat'], $form['id']]);
            flash('success', 'Supplier “' . $form['nama'] . '” berhasil diperbarui.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO supplier (nama, kontak, telepon, alamat) VALUES (?, ?, ?, ?)');
            $stmt->execute([$form['nama'], $form['kontak'], $form['telepon'], $form['alamat']]);
            flash('success', 'Supplier “' . $form['nama'] . '” berhasil ditambahkan.');
        }

        redirect('pages/supplier/index.php');
    }
}

$supplierList = $pdo->query(
    'SELECT s.id, s.nama, s.kontak, s.telepon, s.alamat,
            COUNT(p.id) AS jumlah_produk,
            COALESCE(SUM(p.stok), 0) AS total_stok
     FROM supplier s
     LEFT JOIN produk p ON p.supplier_id = s.id
     GROUP BY s.id, s.nama, s.kontak, s.telepon, s.alamat
     ORDER BY s.nama ASC'
)->fetchAll();

$totalSupplier = count($supplierList);
$supplierAktif = 0;
$totalProdukSupplier = 0;
foreach ($supplierList as $supplier) {
    if ((int)$supplier['jumlah_produk'] > 0) {
        $supplierAktif++;
    }
    $totalProdukSupplier += (int)$supplier['jumlah_produk'];
}

$pageTitle = 'Data Supplier';
$pageSubtitle = 'Simpan kontak pemasok dan lihat produk yang mereka suplai.';
$activeNav = 'supplier';

require __DIR__ . '/../../partials/header.php';
?>

<div class="page-intro page-intro--compact">
    <div class="breadcrumb">
        <a href="<?= e(base_url('index.php')) ?>">Ringkasan</a>
        <?= icon('chevron-right') ?>
        <span>Supplier</span>
    </div>
    <div class="page-intro__row">
        <div>
            <h2><?= $totalSupplier ?> supplier terdaftar</h2>
            <p><?= $totalProdukSupplier ?> produk sudah terhubung ke supplier. Supplier yang masih dipakai tidak bisa dihapus.</p>
        </div>
    </div>
</div>

<?php if (isset($errors['umum'])): ?>
    <div class="toast toast--error" role="alert" style="position: static; margin-bottom: 18px;">
        <span class="toast__icon"><?= icon('alert') ?></span>
        <p><?= e($errors['umum']) ?></p>
    </div>
<?php endif; ?>

<div class="split-layout">
    <section class="card card--pad form-card form-card--sticky">
        <header class="section-head section-head--bordered">
            <div>
                <span class="eyebrow"><?= $form['id'] > 0 ? 'Perbarui supplier' : 'Supplier baru' ?></span>
                <h3><?= $form['id'] > 0 ? 'Ubah supplier' : 'Tambah supplier' ?></h3>
            </div>
            <span class="mode-pill mode-pill--sun"><?= icon('truck') ?> <?= $form['id'] > 0 ? 'Edit' : 'Baru' ?></span>
        </header>

        <form class="form" method="post" action="<?= e(base_url('pages/supplier/index.php')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$form['id'] ?>">

            <div class="field <?= isset($errors['nama']) ? 'has-error' : '' ?>">
                <label for="nama">Nama supplier <span class="required">*</span></label>
                <input id="nama" name="nama" type="text" maxlength="120" value="<?= e($form['nama']) ?>" placeholder="Contoh: CV Bahari Seafood" required autocomplete="off">
                <?php if (isset($errors['nama'])): ?>
                    <small class="field-error"><?= e($errors['nama']) ?></small>
                <?php endif; ?>
            </div>

            <div class="form-grid form-grid--two">
                <div class="field <?= isset($errors['kontak']) ? 'has-error' : '' ?>">
                    <label for="kontak">Nama kontak <span class="optional">(opsional)</span></label>
                    <input id="kontak" name="kontak" type="text" maxlength="100" value="<?= e($form['kontak']) ?>" placeholder="Ibu Sari" autocomplete="off">
                    <?php if (isset($errors['kontak'])): ?>
                        <small class="field-error"><?= e($errors['kontak']) ?></small>
                    <?php endif; ?>
                </div>

                <div class="field <?= isset($errors['telepon']) ? 'has-error' : '' ?>">
                    <label for="telepon">Telepon <span class="optional">(opsional)</span></label>
                    <input id="telepon" name="telepon" type="tel" maxlength="30" value="<?= e($form['telepon']) ?>" placeholder="0812-3456-7890" autocomplete="off">
                    <?php if (isset($errors['telepon'])): ?>
                        <small class="field-error"><?= e($errors['telepon']) ?></small>
                    <?php endif; ?>
                </div>
            </div>

            <div class="field <?= isset($errors['alamat']) ? 'has-error' : '' ?>">
                <label for="alamat">Alamat <span class="optional">(opsional)</span></label>
                <textarea id="alamat" name="alamat" rows="3" maxlength="255" placeholder="Alamat gudang atau toko supplier…"><?= e($form['alamat']) ?></textarea>
                <?php if (isset($errors['alamat'])): ?>
                    <small class="field-error"><?= e($errors['alamat']) ?></small>
                <?php endif; ?>
            </div>

            <div class="form-actions form-actions--stack">
                <button class="btn btn--primary btn--full" type="submit">
                    <?= icon('check') ?> <?= $form['id'] > 0 ? 'Simpan perubahan' : 'Tambah supplier' ?>
                </button>
                <?php if ($form['id'] > 0): ?>
                    <a class="btn btn--ghost btn--full" href="<?= e(base_url('pages/supplier/index.php')) ?>"><?= icon('close') ?> Batal ubah</a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <section class="stack">
        <div class="mini-stats">
            <div class="mini-stat">
                <span class="mini-stat__icon mini-stat__icon--coral"><?= icon('truck') ?></span>
                <span><strong><?= $totalSupplier ?></strong><small>Total supplier</small></span>
            </div>
            <div class="mini-stat">
                <span class="mini-stat__icon mini-stat__icon--mint"><?= icon('check') ?></span>
                <span><strong><?= $supplierAktif ?></strong><small>Supplier aktif</small></span>
            </div>
            <div class="mini-stat">
                <span class="mini-stat__icon mini-stat__icon--sun"><?= icon('box') ?></span>
                <span><strong><?= $totalProdukSupplier ?></strong><small>Produk terhubung</small></span>
            </div>
        </div>

        <div class="supplier-grid">
            <?php if ($supplierList === []): ?>
                <div class="card card--pad">
                    <div class="empty-state empty-state--compact">
                        <?= icon('truck') ?>
                        <h4>Belum ada supplier</h4>
                        <p>Tambahkan supplier pertama lewat formulir di samping.</p>
                    </div>
                </div>
            <?php endif; ?>

            <?php foreach ($supplierList as $index => $supplier): ?>
                <article class="supplier-card">
                    <header class="supplier-card__head">
                        <span class="avatar avatar--lg avatar--<?= ['coral', 'mint', 'sun'][$index % 3] ?>">
                            <?= e(inisial($supplier['nama'])) ?>
                        </span>
                        <div class="supplier-card__title">
                            <h4><?= e($supplier['nama']) ?></h4>
                            <span><?= e($supplier['kontak'] !== '' ? $supplier['kontak'] : 'Kontak belum diisi') ?></span>
                        </div>
                        <span class="count-pill count-pill--<?= (int)$supplier['jumlah_produk'] > 0 ? 'active' : 'empty' ?>">
                            <?= (int)$supplier['jumlah_produk'] ?> produk
                        </span>
                    </header>

                    <ul class="supplier-meta">
                        <li>
                            <span class="supplier-meta__icon"><?= icon('phone') ?></span>
                            <?= e($supplier['telepon'] !== '' ? $supplier['telepon'] : 'Telepon belum diisi') ?>
                        </li>
                        <li>
                            <span class="supplier-meta__icon"><?= icon('pin') ?></span>
                            <?= e($supplier['alamat'] !== '' ? $supplier['alamat'] : 'Alamat belum diisi') ?>
                        </li>
                    </ul>

                    <footer class="supplier-card__footer">
                        <span class="supplier-card__stock">
                            <?= icon('box') ?>
                            <?= angka_ribuan($supplier['total_stok']) ?> unit stok
                        </span>
                        <div class="row-actions">
                            <a class="icon-btn" href="<?= e(base_url('pages/supplier/index.php?edit=' . (int)$supplier['id'])) ?>" aria-label="Ubah supplier <?= e($supplier['nama']) ?>" title="Ubah">
                                <?= icon('edit') ?>
                            </a>
                            <?php if ((int)$supplier['jumlah_produk'] > 0): ?>
                                <button class="icon-btn" type="button" disabled title="Masih menyuplai <?= (int)$supplier['jumlah_produk'] ?> produk" aria-label="Tidak bisa dihapus, masih menyuplai produk">
                                    <?= icon('trash') ?>
                                </button>
                            <?php else: ?>
                                <form method="post" action="<?= e(base_url('pages/supplier/hapus.php')) ?>" data-confirm="Hapus supplier “<?= e($supplier['nama']) ?>”? Supplier ini belum terhubung ke produk.">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int)$supplier['id'] ?>">
                                    <button class="icon-btn icon-btn--danger" type="submit" aria-label="Hapus supplier <?= e($supplier['nama']) ?>" title="Hapus">
                                        <?= icon('trash') ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </footer>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</div>

<?php require __DIR__ . '/../../partials/footer.php'; ?>
