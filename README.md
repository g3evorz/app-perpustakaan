# Sistem Perpustakaan Digital Kampus

Aplikasi web perpustakaan berbasis Laravel 12 dengan fitur autentikasi petugas/admin, pengelolaan kategori, buku, anggota, peminjaman, pengembalian, laporan, serta REST API.

Dashboard dan halaman laporan mengambil data dari API internal aplikasi melalui HTTP, sehingga aplikasi perlu dijalankan pada **dua port**:

- `8000` untuk aplikasi web.
- `8011` untuk API internal yang dikonsumsi Dashboard dan Laporan.

## Fitur

- Login dan logout dengan role `admin` dan `petugas`.
- Dashboard statistik: total buku, total anggota, dan peminjaman aktif.
- CRUD kategori (khusus admin).
- CRUD buku dengan relasi kategori.
- CRUD anggota dengan validasi NIM dan email unik.
- Transaksi peminjaman multi-buku.
- Pengembalian buku dan perubahan status transaksi.
- Laporan peminjaman dari `GET /api/loans`.
- REST API buku, anggota, peminjaman, dan statistik.

## Kebutuhan

- PHP `>= 8.2`
- Composer
- Ekstensi PHP `pdo_sqlite`
- Git
- Postman (opsional, untuk demo API)

## Instalasi

### 1. Clone repository

```bash
git clone https://github.com/g3evorz/app-perpustakaan.git
cd app-perpustakaan
```

### 2. Install dependency

```bash
composer install
```

Jika ingin menjalankan frontend asset bawaan Laravel, jalankan juga:

```bash
npm install
npm run build
```

> Frontend asset tidak wajib untuk demo karena tampilan aplikasi menggunakan CSS inline.

### 3. Setup file `.env`

Salin file contoh environment:

```bash
cp .env.example .env
```

Buat file database SQLite:

```bash
touch database/database.sqlite
```

Generate application key:

```bash
php artisan key:generate
```

Sesuaikan bagian penting berikut di `.env`:

```dotenv
APP_NAME="Perpustakaan Digital Kampus"
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=sqlite

INTERNAL_API_URL=http://127.0.0.1:8011
```

Keterangan:

- `APP_URL` adalah alamat aplikasi web.
- `DB_CONNECTION=sqlite` memakai database `database/database.sqlite`.
- `INTERNAL_API_URL` adalah alamat API internal yang dipanggil Dashboard melalui `GET /api/stats` dan halaman Laporan melalui `GET /api/loans`.

Jika `.env` diubah, bersihkan cache konfigurasi:

```bash
php artisan config:clear
```

### 4. Migrate dan seed database

Untuk membuat ulang seluruh tabel sekaligus mengisi data contoh:

```bash
php artisan migrate:fresh --seed
```

Perintah ini **menghapus seluruh data lama** di database SQLite.

Jika hanya ingin menjalankan migration dan seeder tanpa menghapus data yang sudah ada:

```bash
php artisan migrate --seed
```

Seeder akan membuat akun, kategori, buku, anggota, dan transaksi peminjaman contoh.

### 5. Jalankan dua server

Aplikasi harus berjalan di dua terminal berbeda.

**Terminal 1 - aplikasi web (port 8000):**

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

**Terminal 2 - API internal (port 8011):**

```bash
php artisan serve --host=127.0.0.1 --port=8011
```

Kedua server menjalankan aplikasi dan database yang sama. Server port `8011` dibutuhkan karena Dashboard dan Laporan memanggil API internal lewat `Http::get()`.

Jika hanya server `8000` yang berjalan:

- Statistik pada Dashboard akan tampil `0`.
- Halaman `/loans/report` akan tampil kosong.

### 6. Akses aplikasi

Buka:

```text
http://127.0.0.1:8000/login
```

Gunakan salah satu akun berikut:

| Nama | Email | Password | Role |
|---|---|---|---|
| Bambang Sutrisno | `admin@pens.ac.id` | `password` | admin |
| Siti Rahmawati | `petugas1@pens.ac.id` | `password` | petugas |
| Ahmad Fauzi | `petugas2@pens.ac.id` | `password` | petugas |

Gunakan akun **admin** jika ingin mendemokan menu Kategori.

## Endpoint API

Base URL API:

```text
http://127.0.0.1:8000/api
```

| Metode | Endpoint | Keterangan |
|---|---|---|
| GET | `/api/books` | Daftar buku dengan pagination dan relasi kategori |
| GET | `/api/books/{id}` | Detail buku |
| POST | `/api/books` | Tambah buku |
| PUT | `/api/books/{id}` | Ubah buku |
| DELETE | `/api/books/{id}` | Hapus buku |
| GET | `/api/members` | Daftar anggota dengan pagination |
| GET | `/api/members/{id}` | Detail anggota |
| GET | `/api/loans` | Daftar peminjaman beserta anggota, petugas, dan buku |
| POST | `/api/loans` | Buat peminjaman |
| PUT | `/api/loans/{id}/kembalikan` | Proses pengembalian |
| GET | `/api/stats` | Statistik dashboard |

## Postman

Import collection berikut ke Postman:

```text
postman/app-perpustakaan.postman_collection.json
```

Pastikan collection variable `baseUrl` bernilai:

```text
http://127.0.0.1:8000/api
```

## Pengujian

Jalankan test suite:

```bash
php artisan test
```

## Troubleshooting

| Gejala | Penyebab | Solusi |
|---|---|---|
| Statistik Dashboard `0` | Server API internal port `8011` tidak berjalan | Jalankan `php artisan serve --host=127.0.0.1 --port=8011` di terminal kedua |
| Halaman laporan kosong | Server API internal port `8011` tidak berjalan | Jalankan server port `8011`, lalu refresh halaman |
| `could not find driver` saat migrate | Ekstensi `pdo_sqlite` belum aktif | Aktifkan ekstensi `pdo_sqlite` di PHP |
| `Database file at path ... does not exist` | File SQLite belum dibuat | Jalankan `touch database/database.sqlite` |
| Perubahan `.env` tidak terbaca | Cache konfigurasi masih lama | Jalankan `php artisan config:clear` |
| Menu Kategori tidak muncul atau `403` | Login sebagai petugas | Login dengan akun admin |
| Postman gagal connect | Server port `8000` tidak berjalan atau `baseUrl` salah | Pastikan server berjalan dan `baseUrl` = `http://127.0.0.1:8000/api` |

## Skenario Demo

Urutan demo UAS tersedia di:

```text
DEMO.md
```

## Lisensi

MIT
