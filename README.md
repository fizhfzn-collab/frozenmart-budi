# FrostMart — Pencatatan Produk Frozen Food Pak Budi

Aplikasi web **PHP Native** untuk mencatat produk frozen food milik Pak Budi. Versi ini dibuat sebagai varian kedua dengan **desain dan struktur file yang berbeda** dari proyek frozen food sebelumnya, serta memakai **folder dan database sendiri** agar tidak bentrok saat dijalankan di localhost.

> **Repo:** <https://github.com/fizhfzn-collab/frozenmart-budi>

---

## Perbedaan dengan proyek sebelumnya

| Pembeda | Proyek sebelumnya | FrostMart (repo ini) |
|---|---|---|
| Folder | `C:\xampp\htdocs\projek2pahapis` | `C:\xampp\htdocs\frozenmart-budi` |
| URL localhost | `http://localhost/projek2pahapis/` | **`http://localhost/frozenmart-budi/`** |
| Database | `frozen_food` | **`frozenmart_budi`** |
| Tampilan | Topbar horizontal, tema hijau sederhana | Sidebar hijau gelap, latar krem, aksen coral & kuning |
| Struktur | File PHP di root | `config/`, `partials/`, `pages/`, `assets/` |
| Gaya data | Tabel standar | Dashboard, kartu supplier, filter chips, dialog konfirmasi kustom |

Karena folder dan nama database berbeda, kedua aplikasi bisa dijalankan bersamaan tanpa saling menimpa data.

---

## Fitur

1. **Dashboard gudang**
   - Total produk, total stok, nilai persediaan, dan jumlah produk yang perlu restock.
   - Sebaran produk per kategori dalam bentuk progress bar.
   - Daftar produk stok menipis dan produk terbaru.
2. **CRUD Produk**
   - Tambah, lihat, ubah, dan hapus produk.
   - Data: kode produk, nama, kategori, supplier, harga, stok, dan satuan.
   - Pencarian berdasarkan nama/kode.
   - Filter berdasarkan **kategori**, **supplier**, dan **status stok** (aman/menipis/habis).
   - Pagination dan validasi server-side.
3. **CRUD Kategori**
   - Tambah, lihat, ubah, dan hapus kategori.
   - Menampilkan jumlah produk dan total stok per kategori.
   - Kategori yang masih dipakai produk otomatis tidak bisa dihapus.
4. **CRUD Supplier**
   - Tambah, lihat, ubah, dan hapus supplier.
   - Data: nama, kontak, telepon, alamat.
   - Menampilkan jumlah produk dan total stok yang disuplai.
   - Supplier yang masih menyuplai produk otomatis tidak bisa dihapus.
5. **Relasi data**
   - Setiap produk wajib terhubung ke **satu kategori** dan **satu supplier**.
   - Foreign key `ON DELETE RESTRICT` menjaga integritas data.

---

## Teknologi

- PHP 8.2 (PHP Native, tanpa framework)
- MySQL / MariaDB (XAMPP)
- PDO + prepared statements
- HTML5, CSS3 custom (tanpa Bootstrap)
- JavaScript vanilla (sidebar, toast, dialog konfirmasi, format harga)
- Google Fonts: Bricolage Grotesque & DM Sans
- Ikon SVG inline (tetap tampil tanpa koneksi internet)

---

## Struktur Folder

```
frozenmart-budi/
├── index.php                     # Dashboard
├── database.sql                  # Buat database + tabel + data contoh
├── config/
│   ├── app.php                   # Session, helper, CSRF, base URL
│   ├── database.php              # Koneksi PDO
│   └── icons.php                 # Kumpulan ikon SVG
├── partials/
│   ├── header.php                # Head, topbar, toast
│   ├── sidebar.php               # Navigasi + status gudang
│   └── footer.php                # Footer, dialog hapus, script
├── pages/
│   ├── produk/
│   │   ├── index.php             # Daftar + cari + filter + pagination
│   │   ├── form.php              # Form tambah/ubah produk
│   │   └── hapus.php             # Proses hapus produk (POST)
│   ├── kategori/
│   │   ├── index.php             # Form + daftar kategori
│   │   └── hapus.php             # Hapus kategori + cek relasi
│   └── supplier/
│       ├── index.php             # Form + kartu supplier
│       └── hapus.php             # Hapus supplier + cek relasi
├── assets/
│   ├── css/app.css               # Seluruh desain FrostMart
│   └── js/app.js                 # Interaksi UI
└── README.md
```

---

## Cara Instalasi (XAMPP)

1. Pastikan folder aplikasi berada di:
   ```
   C:\xampp\htdocs\frozenmart-budi
   ```
2. Buka **XAMPP Control Panel**, lalu start **Apache** dan **MySQL**.
3. Buka phpMyAdmin: <http://localhost/phpmyadmin>
4. Klik tab **Import**, pilih file `database.sql`, lalu klik **Go/Impor**.
   - File SQL akan otomatis membuat database `frozenmart_budi`.
   - Berisi 6 kategori, 5 supplier, dan 14 produk contoh.
5. Buka aplikasi:
   ```
   http://localhost/frozenmart-budi/
   ```

Tidak perlu Composer, Node.js, atau proses build apa pun.

### Alternatif lewat terminal

```bash
cd C:\xampp\htdocs
C:\xampp\mysql\bin\mysql.exe -u root < frozenmart-budi\database.sql
```

Lalu jalankan Apache dan buka URL di atas.

---

## Cara Menjalankan Versi GitHub

```bash
cd C:\xampp\htdocs
git clone https://github.com/fizhfzn-collab/frozenmart-budi.git
```

Setelah clone:

1. Impor `frozenmart-budi/database.sql` melalui phpMyAdmin.
2. Buka <http://localhost/frozenmart-budi/>.

---

## Konfigurasi Database

Semua konfigurasi ada di `config/database.php`:

```php
const DB_HOST = '127.0.0.1';
const DB_PORT = 3306;
const DB_NAME = 'frozenmart_budi';
const DB_USER = 'root';
const DB_PASS = '';
```

Jika MySQL memakai password, ubah `DB_PASS` sesuai konfigurasi lokal.

> **Penting:** jangan mengarahkan `DB_NAME` ke `frozen_food` (database proyek sebelumnya) agar data kedua aplikasi tetap terpisah.

---

## Keamanan yang Diterapkan

- Semua query yang melibatkan input pengguna memakai **prepared statement** PDO.
- Seluruh output database di-escape dengan `htmlspecialchars()` melalui helper `e()`.
- Form ubah/hapus memakai **CSRF token**.
- Penghapusan data hanya lewat **POST**, bukan link GET.
- Validasi server-side: field wajib, panjang teks, format kode, harga, stok, telepon, keunikan nama/kode, dan keberadaan relasi.
- Foreign key `ON DELETE RESTRICT` menjadi perlindungan terakhir di level database.
- Pola **Post/Redirect/Get** mencegah pengiriman form berulang saat halaman di-refresh.

---

## Alur Singkat Presentasi

1. Buka dashboard → tunjukkan statistik, sebaran kategori, dan stok menipis.
2. Buka **Produk** → cari produk, filter per kategori/supplier, lalu tambah produk baru.
3. Buka **Kategori** → tambah kategori, coba hapus kategori yang masih dipakai (ditolak).
4. Buka **Supplier** → lihat kartu supplier, ubah kontak, coba hapus supplier yang masih dipakai (ditolak).
5. Hapus data uji coba → tunjukkan dialog konfirmasi dan pesan toast.

---

## Troubleshooting

| Masalah | Solusi |
|---|---|
| “Database belum tersambung” | Jalankan MySQL di XAMPP dan impor `database.sql`. |
| Halaman 404 | Pastikan nama folder tepat `frozenmart-budi` di `htdocs`. |
| Data tidak muncul | Pastikan database `frozenmart_budi` berisi tabel dan data contoh. |
| Port 80 dipakai aplikasi lain | Ubah port Apache (mis. 8080) lalu buka `http://localhost:8080/frozenmart-budi/`. |
| Ingin mengulang data contoh | Hapus database `frozenmart_budi`, lalu impor ulang `database.sql`. |

---

## Catatan

Aplikasi ini tidak memakai login karena dirancang sebagai aplikasi pencatatan lokal untuk satu admin gudang. Jika nanti dipublikasikan ke internet, tambahkan autentikasi dan ganti kredensial database sebelum digunakan.

Dibuat untuk keperluan pembelajaran **PHP Native + MySQL**.
