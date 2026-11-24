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
    'deskripsi' => '',
];

if ($editId > 0) {
    $stmt = $pdo->prepare('SELECT id, nama, deskripsi FROM kategori WHERE id = ?');
    $stmt->execute([$editId]);
    $kategoriEdit = $stmt->fetch();

    if ($kategoriEdit) {
        $form = [
            'id' => (int)$kategoriEdit['id'],
            'nama' => $kategoriEdit['nama'],
            'deskripsi' => $kategoriEdit['deskripsi'],
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
        'deskripsi' => post('deskripsi'),
    ];

    if ($form['nama'] === '') {
        $errors['nama'] = 'Nama kategori wajib diisi.';
    } elseif (strlen($form['nama']) > 100) {
        $errors['nama'] = 'Nama kategori maksimal 100 karakter.';
    }

    if (strlen($form['deskripsi']) > 255) {
        $errors['deskripsi'] = 'Deskripsi maksimal 255 karakter.';
    }

    if ($form['id'] > 0) {
        $cek = $pdo->prepare('SELECT COUNT(*) FROM kategori WHERE id = ?');
        $cek->execute([$form['id']]);
        if ((int)$cek->fetchColumn() === 0) {
            $errors['umum'] = 'Kategori yang ingin diubah tidak ditemukan.';
        }
    }

    if (!isset($errors['nama'])) {
        $cekNama = $pdo->prepare('SELECT COUNT(*) FROM kategori WHERE nama = ? AND id <> ?');
        $cekNama->execute([$form['nama'], $form['id']]);
        if ((int)$cekNama->fetchColumn() > 0) {
            $errors['nama'] = 'Nama kategori sudah ada. Gunakan nama lain.';
        }
    }

    if ($errors === []) {
        if ($form['id'] > 0) {
            $stmt = $pdo->prepare('UPDATE kategori SET nama = ?, deskripsi = ? WHERE id = ?');
            $stmt->execute([$form['nama'], $form['deskripsi'], $form['id']]);
            flash('success', 'Kategori “' . $form['nama'] . '” berhasil diperbarui.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO kategori (nama, deskripsi) VALUES (?, ?)');
            $stmt->execute([$form['nama'], $form['deskripsi']]);
            flash('success', 'Kategori “' . $form['nama'] . '” berhasil ditambahkan.');
        }

        redirect('pages/kategori/index.php');
    }
}

$kategoriList = $pdo->query(
    'SELECT k.id, k.nama, k.deskripsi, COUNT(p.id) AS jumlah_produk, COALESCE(SUM(p.stok), 0) AS total_stok
     FROM kategori k
     LEFT JOIN produk p ON p.kategori_id = k.id
     GROUP BY k.id, k.nama, k.deskripsi
     ORDER BY k.nama ASC'
)->fetchAll();

$totalKategori = count($kategoriList);
$kategoriTerpakai = 0;
foreach ($kategoriList as $kategori) {
    if ((int)$kategori['jumlah_produk'] > 0) {
        $kategoriTerpakai++;
    }
}

$pageTitle = 'Kategori Produk';
$pageSubtitle = 'Kelompokkan produk agar daftar stok lebih mudah dibaca.';
$activeNav = 'kategori';

require __DIR__ . '/../../partials/header.php';
?>

<div class="page-intro page-intro--compact">
    <div class="breadcrumb">
        <a href="<?= e(base_url('index.php')) ?>">Ringkasan</a>
        <?= icon('chevron-right') ?>
        <span>Kategori</span>
    </div>
    <div class="page-intro__row">
        <div>
            <h2><?= $totalKategori ?> kategori tersedia</h2>
            <p><?= $kategoriTerpakai ?> kategori sudah dipakai produk. Kategori yang masih dipakai tidak bisa dihapus.</p>
        </div>
    </div>
</div>

<?php if (isset($errors['umum'])): ?>
    <div class="toast toast--error toast--inline" role="alert">
        <span class="toast__icon"><?= icon('alert') ?></span>
        <p><?= e($errors['umum']) ?></p>
    </div>
<?php endif; ?>

<div class="split-layout">
    <section class="card card--pad form-card form-card--sticky">
        <header class="section-head section-head--bordered">
            <div>
                <span class="eyebrow"><?= $form['id'] > 0 ? 'Perbarui kategori' : 'Kategori baru' ?></span>
                <h3><?= $form['id'] > 0 ? 'Ubah kategori' : 'Tambah kategori' ?></h3>
            </div>
            <span class="mode-pill mode-pill--mint"><?= icon('tag') ?> <?= $form['id'] > 0 ? 'Edit' : 'Baru' ?></span>
        </header>

        <form class="form" method="post" action="<?= e(base_url('pages/kategori/index.php')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$form['id'] ?>">

            <div class="field <?= isset($errors['nama']) ? 'has-error' : '' ?>">
                <label for="nama">Nama kategori <span class="required">*</span></label>
                <input id="nama" name="nama" type="text" maxlength="100" value="<?= e($form['nama']) ?>" placeholder="Contoh: Daging & Unggas" required autocomplete="off">
                <?php if (isset($errors['nama'])): ?>
                    <small class="field-error"><?= e($errors['nama']) ?></small>
                <?php else: ?>
                    <small class="field-hint">Gunakan nama singkat yang mudah dikenali.</small>
                <?php endif; ?>
            </div>

            <div class="field <?= isset($errors['deskripsi']) ? 'has-error' : '' ?>">
                <label for="deskripsi">Deskripsi <span class="optional">(opsional)</span></label>
                <textarea id="deskripsi" name="deskripsi" rows="4" maxlength="255" placeholder="Jelaskan jenis produk dalam kategori ini…"><?= e($form['deskripsi']) ?></textarea>
                <?php if (isset($errors['deskripsi'])): ?>
                    <small class="field-error"><?= e($errors['deskripsi']) ?></small>
                <?php else: ?>
                    <small class="field-hint">Maksimal 255 karakter.</small>
                <?php endif; ?>
            </div>

            <div class="form-actions form-actions--stack">
                <button class="btn btn--primary btn--full" type="submit">
                    <?= icon('check') ?> <?= $form['id'] > 0 ? 'Simpan perubahan' : 'Tambah kategori' ?>
                </button>
                <?php if ($form['id'] > 0): ?>
                    <a class="btn btn--ghost btn--full" href="<?= e(base_url('pages/kategori/index.php')) ?>"><?= icon('close') ?> Batal ubah</a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <section class="stack">
        <div class="mini-stats">
            <div class="mini-stat">
                <span class="mini-stat__icon mini-stat__icon--coral"><?= icon('tag') ?></span>
                <span><strong><?= $totalKategori ?></strong><small>Total kategori</small></span>
            </div>
            <div class="mini-stat">
                <span class="mini-stat__icon mini-stat__icon--mint"><?= icon('check') ?></span>
                <span><strong><?= $kategoriTerpakai ?></strong><small>Dipakai produk</small></span>
            </div>
            <div class="mini-stat">
                <span class="mini-stat__icon mini-stat__icon--sun"><?= icon('box') ?></span>
                <span><strong><?= $totalKategori - $kategoriTerpakai ?></strong><small>Belum dipakai</small></span>
            </div>
        </div>

        <div class="card card--pad">
            <header class="section-head section-head--bordered">
                <div>
                    <span class="eyebrow">Master data</span>
                    <h3>Daftar kategori</h3>
                </div>
                <span class="count-pill"><?= $totalKategori ?> data</span>
            </header>

            <?php if ($kategoriList === []): ?>
                <div class="empty-state empty-state--compact">
                    <?= icon('tag') ?>
                    <h4>Belum ada kategori</h4>
                    <p>Tambahkan kategori pertama lewat formulir di samping.</p>
                </div>
            <?php else: ?>
                <div class="category-list">
                    <?php foreach ($kategoriList as $kategori): ?>
                        <article class="category-item">
                            <span class="category-icon"><?= icon('tag') ?></span>
                            <div class="category-item__copy">
                                <strong><?= e($kategori['nama']) ?></strong>
                                <p><?= e($kategori['deskripsi'] !== '' ? $kategori['deskripsi'] : 'Belum ada deskripsi.') ?></p>
                            </div>
                            <div class="category-item__stats">
                                <strong><?= (int)$kategori['jumlah_produk'] ?></strong>
                                <span>produk</span>
                                <small><?= angka_ribuan($kategori['total_stok']) ?> unit</small>
                            </div>
                            <div class="row-actions">
                                <a class="icon-btn" href="<?= e(base_url('pages/kategori/index.php?edit=' . (int)$kategori['id'])) ?>" aria-label="Ubah kategori <?= e($kategori['nama']) ?>" title="Ubah">
                                    <?= icon('edit') ?>
                                </a>
                                <?php if ((int)$kategori['jumlah_produk'] > 0): ?>
                                    <button class="icon-btn" type="button" disabled title="Masih dipakai <?= (int)$kategori['jumlah_produk'] ?> produk" aria-label="Tidak bisa dihapus, masih dipakai produk">
                                        <?= icon('trash') ?>
                                    </button>
                                <?php else: ?>
                                    <form method="post" action="<?= e(base_url('pages/kategori/hapus.php')) ?>" data-confirm="Hapus kategori “<?= e($kategori['nama']) ?>”? Kategori ini belum dipakai produk.">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int)$kategori['id'] ?>">
                                        <button class="icon-btn icon-btn--danger" type="submit" aria-label="Hapus kategori <?= e($kategori['nama']) ?>" title="Hapus">
                                            <?= icon('trash') ?>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>

<?php require __DIR__ . '/../../partials/footer.php'; ?>
