<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';

$pdo = db();

$id = query_int('id');
$mode = $id > 0 ? 'edit' : 'tambah';
$errors = [];

$kategoriList = $pdo->query('SELECT id, nama FROM kategori ORDER BY nama ASC')->fetchAll();
$supplierList = $pdo->query('SELECT id, nama FROM supplier ORDER BY nama ASC')->fetchAll();

$maxKode = (int)$pdo->query("SELECT COALESCE(MAX(CAST(SUBSTRING(kode, 5) AS UNSIGNED)), 0) FROM produk WHERE kode LIKE 'FRZ-%'")->fetchColumn();

$data = [
    'kode' => 'FRZ-' . str_pad((string)($maxKode + 1), 3, '0', STR_PAD_LEFT),
    'nama' => '',
    'kategori_id' => 0,
    'supplier_id' => 0,
    'harga' => '',
    'stok' => '',
    'satuan' => 'pack',
];

$produkLama = null;

if ($mode === 'edit') {
    $stmt = $pdo->prepare('SELECT * FROM produk WHERE id = ?');
    $stmt->execute([$id]);
    $produkLama = $stmt->fetch();

    if (!$produkLama) {
        http_response_code(404);
        $pageTitle = 'Produk tidak ditemukan';
        $pageSubtitle = 'Data yang dicari sudah dihapus atau tidak tersedia.';
        $activeNav = 'produk';
        require __DIR__ . '/../../partials/header.php';
        ?>
        <section class="card">
            <div class="empty-state">
                <?= icon('box') ?>
                <h4>Produk tidak ditemukan</h4>
                <p>Produk dengan ID tersebut tidak ada di database.</p>
                <a class="btn btn--primary" href="<?= e(base_url('pages/produk/index.php')) ?>"><?= icon('chevron-left') ?> Kembali ke daftar produk</a>
            </div>
        </section>
        <?php
        require __DIR__ . '/../../partials/footer.php';
        exit;
    }

    $data = [
        'kode' => $produkLama['kode'],
        'nama' => $produkLama['nama'],
        'kategori_id' => (int)$produkLama['kategori_id'],
        'supplier_id' => (int)$produkLama['supplier_id'],
        'harga' => $produkLama['harga'],
        'stok' => (string)$produkLama['stok'],
        'satuan' => $produkLama['satuan'],
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $data = [
        'kode' => strtoupper(post('kode')),
        'nama' => post('nama'),
        'kategori_id' => post_int('kategori_id'),
        'supplier_id' => post_int('supplier_id'),
        'harga' => post('harga'),
        'stok' => post('stok'),
        'satuan' => post('satuan'),
    ];

    $harga = rupiah_ke_angka($data['harga']);
    $stokRaw = $data['stok'];
    $satuanValid = ['pcs', 'pack', 'kg', 'gram', 'box', 'liter', 'ikat'];

    if ($data['kode'] === '') {
        $errors['kode'] = 'Kode produk wajib diisi.';
    } elseif (strlen($data['kode']) > 20) {
        $errors['kode'] = 'Kode produk maksimal 20 karakter.';
    } elseif (!preg_match('/^[A-Z0-9-]+$/', $data['kode'])) {
        $errors['kode'] = 'Kode hanya boleh berisi huruf, angka, dan tanda minus.';
    }

    if ($data['nama'] === '') {
        $errors['nama'] = 'Nama produk wajib diisi.';
    } elseif (strlen($data['nama']) > 150) {
        $errors['nama'] = 'Nama produk maksimal 150 karakter.';
    }

    if ($data['kategori_id'] <= 0) {
        $errors['kategori_id'] = 'Pilih kategori produk.';
    } else {
        $cekKategori = $pdo->prepare('SELECT COUNT(*) FROM kategori WHERE id = ?');
        $cekKategori->execute([$data['kategori_id']]);
        if ((int)$cekKategori->fetchColumn() === 0) {
            $errors['kategori_id'] = 'Kategori yang dipilih tidak ditemukan.';
        }
    }

    if ($data['supplier_id'] <= 0) {
        $errors['supplier_id'] = 'Pilih supplier produk.';
    } else {
        $cekSupplier = $pdo->prepare('SELECT COUNT(*) FROM supplier WHERE id = ?');
        $cekSupplier->execute([$data['supplier_id']]);
        if ((int)$cekSupplier->fetchColumn() === 0) {
            $errors['supplier_id'] = 'Supplier yang dipilih tidak ditemukan.';
        }
    }

    if ($data['harga'] === '' || $harga === null) {
        $errors['harga'] = 'Harga produk wajib diisi.';
    } elseif ($harga <= 0) {
        $errors['harga'] = 'Harga harus lebih dari 0.';
    } elseif ($harga > 9999999999) {
        $errors['harga'] = 'Harga terlalu besar.';
    }

    if ($stokRaw === '') {
        $errors['stok'] = 'Stok wajib diisi.';
    } elseif (!ctype_digit($stokRaw)) {
        $errors['stok'] = 'Stok harus berupa angka bulat 0 atau lebih.';
    } elseif ((int)$stokRaw > 1000000) {
        $errors['stok'] = 'Jumlah stok terlalu besar.';
    }

    if (!in_array($data['satuan'], $satuanValid, true)) {
        $errors['satuan'] = 'Pilih satuan yang tersedia.';
    }

    if (!isset($errors['kode'])) {
        $cekKode = $pdo->prepare('SELECT COUNT(*) FROM produk WHERE kode = ? AND id <> ?');
        $cekKode->execute([$data['kode'], $id]);
        if ((int)$cekKode->fetchColumn() > 0) {
            $errors['kode'] = 'Kode produk sudah dipakai. Gunakan kode lain.';
        }
    }

    if ($errors === []) {
        $payload = [
            $data['kode'],
            $data['nama'],
            $data['kategori_id'],
            $data['supplier_id'],
            $harga,
            (int)$stokRaw,
            $data['satuan'],
        ];

        if ($mode === 'edit') {
            $stmt = $pdo->prepare(
                'UPDATE produk
                 SET kode = ?, nama = ?, kategori_id = ?, supplier_id = ?, harga = ?, stok = ?, satuan = ?
                 WHERE id = ?'
            );
            $stmt->execute([...$payload, $id]);
            flash('success', 'Produk “' . $data['nama'] . '” berhasil diperbarui.');
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO produk (kode, nama, kategori_id, supplier_id, harga, stok, satuan)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute($payload);
            flash('success', 'Produk “' . $data['nama'] . '” berhasil ditambahkan.');
        }

        redirect('pages/produk/index.php');
    }
}

$hargaTampil = '';
if ((string)$data['harga'] !== '') {
    $hargaTampil = is_numeric($data['harga']) ? angka_ribuan($data['harga']) : $data['harga'];
}

$statusLama = $produkLama ? status_stok((int)$produkLama['stok']) : null;

$pageTitle = $mode === 'edit' ? 'Ubah Produk' : 'Tambah Produk';
$pageSubtitle = $mode === 'edit' ? 'Perbarui informasi produk frozen food.' : 'Catat produk baru ke dalam gudang Pak Budi.';
$activeNav = 'produk';

require __DIR__ . '/../../partials/header.php';
?>

<div class="page-intro page-intro--compact">
    <div class="breadcrumb">
        <a href="<?= e(base_url('index.php')) ?>">Ringkasan</a>
        <?= icon('chevron-right') ?>
        <a href="<?= e(base_url('pages/produk/index.php')) ?>">Produk</a>
        <?= icon('chevron-right') ?>
        <span><?= $mode === 'edit' ? 'Ubah' : 'Tambah' ?></span>
    </div>
</div>

<div class="form-layout">
    <section class="card card--pad form-card">
        <header class="section-head section-head--bordered">
            <div>
                <span class="eyebrow"><?= $mode === 'edit' ? 'Perbarui data' : 'Data baru' ?></span>
                <h3><?= $mode === 'edit' ? 'Informasi produk' : 'Produk frozen food' ?></h3>
            </div>
            <span class="mode-pill"><?= $mode === 'edit' ? icon('edit') . ' Mode ubah' : icon('plus') . ' Mode tambah' ?></span>
        </header>

        <?php if ($errors !== []): ?>
            <div class="form-alert" role="alert">
                <?= icon('alert') ?>
                <div>
                    <strong>Ada <?= count($errors) ?> bagian yang perlu diperiksa.</strong>
                    <p>Perbaiki kolom bertanda merah di bawah, lalu simpan kembali.</p>
                </div>
            </div>
        <?php endif; ?>

        <form class="form" method="post" action="<?= e(base_url('pages/produk/form.php' . ($mode === 'edit' ? '?id=' . $id : ''))) ?>">
            <?= csrf_field() ?>

            <div class="form-grid">
                <div class="field <?= isset($errors['kode']) ? 'has-error' : '' ?>">
                    <label for="kode">Kode produk <span class="required">*</span></label>
                    <input id="kode" name="kode" type="text" maxlength="20" value="<?= e($data['kode']) ?>" placeholder="FRZ-015" required autocomplete="off">
                    <?php if (isset($errors['kode'])): ?>
                        <small class="field-error"><?= e($errors['kode']) ?></small>
                    <?php else: ?>
                        <small class="field-hint">Huruf kapital, angka, dan tanda minus. Contoh: FRZ-015.</small>
                    <?php endif; ?>
                </div>

                <div class="field <?= isset($errors['nama']) ? 'has-error' : '' ?>">
                    <label for="nama">Nama produk <span class="required">*</span></label>
                    <input id="nama" name="nama" type="text" maxlength="150" value="<?= e($data['nama']) ?>" placeholder="Contoh: Ayam Fillet Frozen" required autocomplete="off">
                    <?php if (isset($errors['nama'])): ?>
                        <small class="field-error"><?= e($errors['nama']) ?></small>
                    <?php endif; ?>
                </div>

                <div class="field <?= isset($errors['kategori_id']) ? 'has-error' : '' ?>">
                    <label for="kategori_id">Kategori <span class="required">*</span></label>
                    <select id="kategori_id" name="kategori_id" required>
                        <option value="">Pilih kategori</option>
                        <?php foreach ($kategoriList as $kategori): ?>
                            <option value="<?= (int)$kategori['id'] ?>" <?= $data['kategori_id'] === (int)$kategori['id'] ? 'selected' : '' ?>>
                                <?= e($kategori['nama']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['kategori_id'])): ?>
                        <small class="field-error"><?= e($errors['kategori_id']) ?></small>
                    <?php endif; ?>
                </div>

                <div class="field <?= isset($errors['supplier_id']) ? 'has-error' : '' ?>">
                    <label for="supplier_id">Supplier <span class="required">*</span></label>
                    <select id="supplier_id" name="supplier_id" required>
                        <option value="">Pilih supplier</option>
                        <?php foreach ($supplierList as $supplier): ?>
                            <option value="<?= (int)$supplier['id'] ?>" <?= $data['supplier_id'] === (int)$supplier['id'] ? 'selected' : '' ?>>
                                <?= e($supplier['nama']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['supplier_id'])): ?>
                        <small class="field-error"><?= e($errors['supplier_id']) ?></small>
                    <?php endif; ?>
                </div>

                <div class="field <?= isset($errors['harga']) ? 'has-error' : '' ?>">
                    <label for="harga">Harga jual <span class="required">*</span></label>
                    <div class="input-prefix">
                        <span>Rp</span>
                        <input id="harga" name="harga" type="text" inputmode="numeric" value="<?= e($hargaTampil) ?>" placeholder="38.000" required autocomplete="off">
                    </div>
                    <?php if (isset($errors['harga'])): ?>
                        <small class="field-error"><?= e($errors['harga']) ?></small>
                    <?php else: ?>
                        <small class="field-hint">Boleh memakai titik ribuan, contoh 38.000.</small>
                    <?php endif; ?>
                </div>

                <div class="form-grid form-grid--two">
                    <div class="field <?= isset($errors['stok']) ? 'has-error' : '' ?>">
                        <label for="stok">Stok <span class="required">*</span></label>
                        <input id="stok" name="stok" type="number" min="0" step="1" max="1000000" value="<?= e($data['stok']) ?>" placeholder="0" required>
                        <?php if (isset($errors['stok'])): ?>
                            <small class="field-error"><?= e($errors['stok']) ?></small>
                        <?php endif; ?>
                    </div>

                    <div class="field <?= isset($errors['satuan']) ? 'has-error' : '' ?>">
                        <label for="satuan">Satuan <span class="required">*</span></label>
                        <select id="satuan" name="satuan" required>
                            <?php foreach (['pcs', 'pack', 'kg', 'gram', 'box', 'liter', 'ikat'] as $satuan): ?>
                                <option value="<?= e($satuan) ?>" <?= $data['satuan'] === $satuan ? 'selected' : '' ?>><?= e(ucfirst($satuan)) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['satuan'])): ?>
                            <small class="field-error"><?= e($errors['satuan']) ?></small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <a class="btn btn--ghost" href="<?= e(base_url('pages/produk/index.php')) ?>"><?= icon('chevron-left') ?> Batal</a>
                <button class="btn btn--primary" type="submit">
                    <?= icon('check') ?> <?= $mode === 'edit' ? 'Simpan perubahan' : 'Simpan produk' ?>
                </button>
            </div>
        </form>
    </section>

    <aside class="form-aside">
        <?php if ($mode === 'edit' && $produkLama !== null): ?>
            <div class="card card--pad summary-card">
                <span class="eyebrow">Ringkasan saat ini</span>
                <h3><?= e($produkLama['nama']) ?></h3>
                <p class="summary-card__code"><?= e($produkLama['kode']) ?></p>
                <div class="summary-row">
                    <span>Harga</span>
                    <strong><?= e(rupiah($produkLama['harga'])) ?></strong>
                </div>
                <div class="summary-row">
                    <span>Stok</span>
                    <strong><?= (int)$produkLama['stok'] ?> <?= e($produkLama['satuan']) ?></strong>
                </div>
                <div class="summary-row">
                    <span>Status</span>
                    <span class="badge badge--<?= e($statusLama['key']) ?>"><?= e($statusLama['label']) ?></span>
                </div>
            </div>
        <?php endif; ?>

        <div class="card card--pad tip-card">
            <span class="tip-card__icon"><?= icon('snowflake') ?></span>
            <h3>Tips pencatatan</h3>
            <ul class="check-list">
                <li><?= icon('check') ?> Pakai kode unik agar produk mudah dicari.</li>
                <li><?= icon('check') ?> Isi harga jual, bukan harga beli.</li>
                <li><?= icon('check') ?> Perbarui stok setiap barang masuk/keluar.</li>
                <li><?= icon('check') ?> Produk dengan stok &lt; 10 masuk daftar restock.</li>
            </ul>
        </div>

        <div class="card card--pad tip-card tip-card--warm">
            <span class="tip-card__icon"><?= icon('tag') ?></span>
            <h3>Butuh kategori baru?</h3>
            <p>Tambahkan kategori atau supplier terlebih dahulu agar produk bisa dikelompokkan dengan tepat.</p>
            <div class="tip-card__links">
                <a class="text-link" href="<?= e(base_url('pages/kategori/index.php')) ?>">Kelola kategori <?= icon('arrow-right') ?></a>
                <a class="text-link" href="<?= e(base_url('pages/supplier/index.php')) ?>">Kelola supplier <?= icon('arrow-right') ?></a>
            </div>
        </div>
    </aside>
</div>

<?php require __DIR__ . '/../../partials/footer.php'; ?>
