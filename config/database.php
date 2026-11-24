<?php
declare(strict_types=1);

/**
 * Koneksi database aplikasi FrostMart.
 * Database sengaja dibedakan dari proyek frozen food lain agar tidak bentrok.
 */

const DB_HOST = '127.0.0.1';
const DB_PORT = 3306;
const DB_NAME = 'frozenmart_budi';
const DB_USER = 'root';
const DB_PASS = '';

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $exception) {
        error_log('Koneksi database FrostMart gagal: ' . $exception->getMessage());
        http_response_code(500);
        ?>
        <!DOCTYPE html>
        <html lang="id">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Database belum siap</title>
            <style>
                body{margin:0;min-height:100vh;display:grid;place-items:center;background:#f7f2e8;color:#133a32;font-family:system-ui,sans-serif;padding:24px}
                .box{max-width:560px;background:#fffdf9;border:1px solid #e6ded0;border-radius:24px;padding:32px;box-shadow:0 18px 45px rgba(18,51,44,.08)}
                h1{font-size:1.5rem;margin:0 0 12px}p{line-height:1.65;color:#5f655e}
                code{background:#f0e9dc;padding:3px 7px;border-radius:7px;color:#b8452b}
                a{display:inline-block;margin-top:16px;background:#ff6b4a;color:#fff;text-decoration:none;padding:11px 18px;border-radius:12px;font-weight:700}
            </style>
        </head>
        <body>
            <div class="box">
                <h1>Database belum tersambung</h1>
                <p>Pastikan MySQL/MariaDB XAMPP sudah berjalan, lalu impor file <code>database.sql</code> melalui phpMyAdmin. Nama database yang dibutuhkan: <code><?= htmlspecialchars(DB_NAME) ?></code>.</p>
                <a href="<?= htmlspecialchars(base_url('database.sql')) ?>">Lihat file database.sql</a>
            </div>
        </body>
        </html>
        <?php
        exit;
    }

    return $pdo;
}
