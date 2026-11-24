<?php
declare(strict_types=1);

/**
 * Konfigurasi umum aplikasi FrostMart.
 * Semua halaman memuat file ini lebih dahulu sebelum mengakses database.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Jakarta');

ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

const APP_NAME = 'FrostMart';
const APP_OWNER = 'Frozen Food Pak Budi';
const APP_TAGLINE = 'Catatan stok frozen food, rapi dan gampang.';

require_once __DIR__ . '/icons.php';

/** Escape seluruh output yang berasal dari database / input pengguna. */
function e($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** URL dasar aplikasi, otomatis menyesuaikan sub-folder di htdocs. */
function base_url(string $path = ''): string
{
    static $base = null;

    if ($base === null) {
        $root = realpath(__DIR__ . '/..');
        $scriptFile = realpath((string)($_SERVER['SCRIPT_FILENAME'] ?? ''));
        $scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '/'));
        $relative = '';

        if ($root !== false && $scriptFile !== false && str_starts_with($scriptFile, $root . DIRECTORY_SEPARATOR)) {
            $relative = ltrim(str_replace('\\', '/', substr($scriptFile, strlen($root))), '/');
        }

        if ($relative !== '' && str_ends_with($scriptName, $relative)) {
            $base = substr($scriptName, 0, strlen($scriptName) - strlen($relative));
        } else {
            $segments = explode('/', trim(str_replace('\\', '/', dirname($scriptName)), '/'));
            foreach (['supplier', 'kategori', 'produk', 'pages'] as $segment) {
                if (end($segments) === $segment) {
                    array_pop($segments);
                }
            }
            $segments = array_values(array_filter($segments, static fn($item) => $item !== '' && $item !== '.'));
            $base = $segments ? '/' . implode('/', $segments) . '/' : '/';
        }

        if ($base === '') {
            $base = '/';
        }
        if (!str_ends_with($base, '/')) {
            $base .= '/';
        }
    }

    return $base . ltrim($path, '/');
}

/** URL aset statis (CSS/JS). */
function asset(string $path): string
{
    return base_url('assets/' . ltrim($path, '/'));
}

/** Redirect internal dengan aman. */
function redirect(string $path): void
{
    header('Location: ' . base_url($path));
    exit;
}

function rupiah($amount): string
{
    return 'Rp ' . number_format((float)$amount, 0, ',', '.');
}

/** Ubah "Rp 38.000" / "38000" menjadi 38000. Null bila mengandung karakter selain angka, titik, koma, atau spasi. */
function rupiah_ke_angka(string $raw): ?float
{
    $clean = trim($raw);
    $clean = preg_replace('/^rp\.?\s*/i', '', $clean);

    if ($clean === null || $clean === '' || !preg_match('/^[0-9.,\s]+$/', $clean)) {
        return null;
    }

    $digits = preg_replace('/[^0-9]/', '', $clean);

    return ($digits === null || $digits === '') ? null : (float)$digits;
}

function angka_ribuan($value): string
{
    return number_format((float)$value, 0, ',', '.');
}

/** Simpan pesan sekali tampil ke session (pola PRG). */
function flash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function pull_flash(): ?array
{
    if (!isset($_SESSION['flash']) || !is_array($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';

    if (!is_string($token) || $token === '' || !hash_equals(csrf_token(), $token)) {
        http_response_code(419);
        exit('Sesi formulir sudah kedaluwarsa. Muat ulang halaman lalu coba lagi.');
    }
}

/** Ambil input POST sebagai string yang sudah di-trim. */
function post(string $key): string
{
    $value = $_POST[$key] ?? '';

    return is_scalar($value) ? trim((string)$value) : '';
}

/** Ambil input GET sebagai string yang sudah di-trim. */
function query(string $key): string
{
    $value = $_GET[$key] ?? '';

    return is_scalar($value) ? trim((string)$value) : '';
}

function query_int(string $key): int
{
    $value = filter_input(INPUT_GET, $key, FILTER_VALIDATE_INT);

    return $value === false || $value === null ? 0 : (int)$value;
}

function post_int(string $key): int
{
    $value = filter_input(INPUT_POST, $key, FILTER_VALIDATE_INT);

    return $value === false || $value === null ? 0 : (int)$value;
}

/** Status stok dipakai untuk badge dan filter. */
function status_stok(int $stok): array
{
    if ($stok <= 0) {
        return ['key' => 'habis', 'label' => 'Habis'];
    }
    if ($stok < 10) {
        return ['key' => 'menipis', 'label' => 'Menipis'];
    }

    return ['key' => 'aman', 'label' => 'Aman'];
}

function tanggal_indonesia(?string $waktu = null): string
{
    $timestamp = $waktu === null ? time() : (strtotime($waktu) ?: time());
    $hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    $bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

    return $hari[(int)date('w', $timestamp)] . ', ' . date('j', $timestamp) . ' ' . $bulan[(int)date('n', $timestamp) - 1] . ' ' . date('Y', $timestamp);
}

/** Inisial aman untuk avatar supplier/kategori. */
function inisial(string $teks): string
{
    $teks = trim($teks);
    if ($teks === '') {
        return '?';
    }

    return function_exists('mb_substr') ? mb_strtoupper(mb_substr($teks, 0, 1, 'UTF-8')) : strtoupper(substr($teks, 0, 1));
}
