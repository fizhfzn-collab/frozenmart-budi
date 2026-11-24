<?php
$navUtama = [
    ['key' => 'dashboard', 'label' => 'Ringkasan', 'href' => 'index.php', 'icon' => 'dashboard'],
    ['key' => 'produk', 'label' => 'Produk', 'href' => 'pages/produk/index.php', 'icon' => 'box'],
];

$navMaster = [
    ['key' => 'kategori', 'label' => 'Kategori', 'href' => 'pages/kategori/index.php', 'icon' => 'tag'],
    ['key' => 'supplier', 'label' => 'Supplier', 'href' => 'pages/supplier/index.php', 'icon' => 'truck'],
];
?>
<aside class="sidebar" data-sidebar aria-label="Navigasi aplikasi">
    <div class="sidebar__top">
        <a class="sidebar__brand" href="<?= e(base_url('index.php')) ?>">
            <span class="brand-mark"><?= icon('snowflake') ?></span>
            <span class="brand-copy">
                <strong>FrostMart</strong>
                <small>Frozen Food Pak Budi</small>
            </span>
        </a>

        <button class="sidebar__close" type="button" data-sidebar-close aria-label="Tutup menu">
            <?= icon('close') ?>
        </button>
    </div>

    <div class="sidebar__scroll">
        <p class="nav-label">Menu utama</p>
        <nav class="side-nav" aria-label="Menu utama">
            <?php foreach ($navUtama as $item): ?>
                <a class="side-nav__link <?= $activeNav === $item['key'] ? 'is-active' : '' ?>"
                   href="<?= e(base_url($item['href'])) ?>"
                   <?= $activeNav === $item['key'] ? 'aria-current="page"' : '' ?>>
                    <span class="side-nav__icon"><?= icon($item['icon']) ?></span>
                    <span><?= e($item['label']) ?></span>
                    <?php if ($item['key'] === 'produk'): ?>
                        <span class="side-nav__dot" aria-hidden="true"></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <p class="nav-label nav-label--spaced">Master data</p>
        <nav class="side-nav" aria-label="Menu master data">
            <?php foreach ($navMaster as $item): ?>
                <a class="side-nav__link <?= $activeNav === $item['key'] ? 'is-active' : '' ?>"
                   href="<?= e(base_url($item['href'])) ?>"
                   <?= $activeNav === $item['key'] ? 'aria-current="page"' : '' ?>>
                    <span class="side-nav__icon"><?= icon($item['icon']) ?></span>
                    <span><?= e($item['label']) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="freezer-card">
            <span class="freezer-card__snow"><?= icon('snowflake') ?></span>
            <span class="freezer-card__status"><i></i> Gudang aktif</span>
            <strong>-18°C</strong>
            <p>Suhu ideal penyimpanan<br>produk frozen food.</p>
        </div>
    </div>

    <div class="sidebar__user">
        <span class="avatar avatar--coral">PB</span>
        <span class="sidebar__user-copy">
            <strong>Pak Budi</strong>
            <small>Admin gudang</small>
        </span>
        <span class="sidebar__user-badge" title="Akun lokal"><?= icon('check') ?></span>
    </div>
</aside>

<button class="sidebar-overlay" type="button" data-sidebar-overlay aria-label="Tutup menu" tabindex="-1"></button>
