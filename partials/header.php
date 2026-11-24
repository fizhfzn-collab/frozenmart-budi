<?php
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../config/app.php';
}

$pageTitle = $pageTitle ?? 'Ringkasan Gudang';
$pageSubtitle = $pageSubtitle ?? APP_TAGLINE;
$activeNav = $activeNav ?? 'dashboard';
$flash = pull_flash();

$flashIcon = match ($flash['type'] ?? '') {
    'success' => 'check',
    'error' => 'alert',
    'warning' => 'alert',
    default => 'info',
};
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="FrostMart — aplikasi pencatatan produk frozen food Pak Budi.">
    <title><?= e($pageTitle) ?> · <?= e(APP_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,600;12..96,700;12..96,800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='24' fill='%23ff6b4a'/><text x='50' y='69' font-size='58' text-anchor='middle' fill='%23fffdf9' font-family='Arial'>F</text></svg>">
</head>
<body>
<div class="app-shell">
    <?php require __DIR__ . '/sidebar.php'; ?>

    <div class="app-main">
        <header class="topbar">
            <button class="menu-btn" type="button" data-sidebar-toggle aria-label="Buka menu navigasi" aria-expanded="false">
                <?= icon('menu') ?>
            </button>

            <div class="topbar__heading">
                <span class="topbar__eyebrow">
                    <?= icon('snowflake') ?>
                    FrostMart · <?= e(APP_OWNER) ?>
                </span>
                <h1><?= e($pageTitle) ?></h1>
                <p><?= e($pageSubtitle) ?></p>
            </div>

            <div class="topbar__actions">
                <span class="date-pill">
                    <?= icon('calendar') ?>
                    <span><?= e(tanggal_indonesia()) ?></span>
                </span>
                <a class="btn btn--primary btn--sm hide-mobile" href="<?= e(base_url('pages/produk/form.php')) ?>">
                    <?= icon('plus') ?>
                    Produk baru
                </a>
            </div>
        </header>

        <main class="page">
            <?php if ($flash !== null): ?>
                <div class="toast toast--<?= e($flash['type'] ?? 'info') ?>" data-toast role="status">
                    <span class="toast__icon"><?= icon($flashIcon) ?></span>
                    <p><?= e($flash['message'] ?? '') ?></p>
                    <button class="toast__close" type="button" data-toast-close aria-label="Tutup notifikasi">
                        <?= icon('close') ?>
                    </button>
                </div>
            <?php endif; ?>
