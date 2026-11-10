# Skenario Demo UAS - Aplikasi Perpustakaan

Dokumen ini adalah urutan langkah demo untuk skenario UAS pada `master-outline.md` bagian I (Pertemuan 11): login, dashboard, kategori, buku, anggota, peminjaman, pengembalian, laporan, dan API di Postman.

---

## Persiapan Sebelum Demo

### 1. Siapkan database

Gunakan data seed agar demo langsung memiliki data awal. Perintah berikut **menghapus seluruh data lama** di database SQLite lokal:

```bash
php artisan migrate:fresh --seed
```

Jika tidak ingin menghapus data yang sudah ada, cukup jalankan:

```bash
php artisan migrate --seed
```

### 2. Jalankan dua server

Aplikasi web dan API internal harus berjalan bersamaan karena Dashboard dan halaman Laporan mengambil data lewat HTTP ke API internal di port `8011`.

Terminal 1 - aplikasi web:

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

Terminal 2 - API internal:

```bash
php artisan serve --host=127.0.0.1 --port=8011
```

Buka aplikasi web di `http://127.0.0.1:8000/login`.

> Catatan konfigurasi: `config/services.php` membaca `INTERNAL_API_URL` dengan nilai default `http://127.0.0.1:8011`. Jika ingin mengganti alamat API internal, sesuaikan nilai `INTERNAL_API_URL` di `.env`, lalu jalankan `php artisan config:clear`.

### 3. Akun demo

| Nama | Email | Password | Role |
|---|---|---|---|
| Bambang Sutrisno | `admin@pens.ac.id` | `password` | admin |
| Siti Rahmawati | `petugas1@pens.ac.id` | `password` | petugas |
| Ahmad Fauzi | `petugas2@pens.ac.id` | `password` | petugas |

Gunakan akun **admin** untuk demo ini karena langkah tambah kategori hanya tersedia untuk admin.

### 4. Siapkan Postman

Import collection berikut:

```text
postman/app-perpustakaan.postman_collection.json
```

Pastikan collection variable `baseUrl` bernilai:

```text
http://127.0.0.1:8000/api
```

---

## Alur Demo

### Langkah 1 - Login sebagai admin/petugas

1. Buka `http://127.0.0.1:8000/login`.
2. Masukkan email `admin@pens.ac.id` dan password `password`.
3. Klik **Login**.

**Yang ditunjukkan:**
- Login berhasil dan browser diarahkan ke Dashboard (`/`).
- Muncul flash message sukses.
- Navbar menampilkan nama dan role user: `Bambang Sutrisno (Admin)`.
- Menu **Kategori** terlihat karena user memiliki role admin.

**Poin penjelasan:**
- Autentikasi memakai `Auth::attempt()` dan session Laravel.
- Setelah login, session di-regenerate untuk mencegah session fixation.
- Route di dalam grup middleware `auth` hanya bisa diakses setelah login.

---

### Langkah 2 - Tampilkan dashboard statistik

1. Setelah login, berada di halaman Dashboard.
2. Tunjukkan tiga kartu statistik:
   - **Total Buku**
   - **Total Anggota**
   - **Peminjaman Aktif**
3. Catat angka **Peminjaman Aktif** sebelum membuat transaksi baru, untuk dibandingkan nanti.

**Yang ditunjukkan:**
- Ketiga angka tampil sesuai data hasil seeder.
- Angka **Peminjaman Aktif** hanya menghitung transaksi berstatus `dipinjam`.

**Poin penjelasan:**
- `DashboardController@index` mengambil data dengan `Http::get(...'/api/stats')`, bukan query Eloquent langsung ke database.
- API `GET /api/stats` mengembalikan `total_buku`, `total_anggota`, dan `peminjaman_aktif`.
- Jika server API internal (port `8011`) mati, dashboard tetap tampil dengan nilai `0` karena ada penanganan `ConnectionException`.

---

### Langkah 3 - Tambah kategori baru

1. Klik menu **Kategori** di navbar.
2. Klik **+ Tambah Kategori**.
3. Isi form:
   - **Nama Kategori:** `Filsafat`
   - **Deskripsi:** `Buku filsafat dan pemikiran.`
4. Klik **Simpan**.

**Yang ditunjukkan:**
- Redirect kembali ke daftar kategori.
- Flash message sukses: `Kategori "Filsafat" berhasil ditambahkan.`
- Data `Filsafat` muncul di tabel index dengan ID baru.

**Poin penjelasan:**
- Validasi dilakukan oleh `StoreCategoryRequest` (`nama_kategori` wajib, maksimal 100 karakter).
- Data disimpan melalui model `Category`.
- Menu dan route kategori dibatasi middleware `admin`; jika login sebagai petugas, menu tidak muncul dan akses langsung akan menghasilkan `403`.

---

### Langkah 4 - Tambah buku dengan kategori

1. Klik menu **Buku**.
2. Klik **+ Tambah Buku**.
3. Isi form dengan data contoh:
   - **Judul:** `Filsafat Ilmu`
   - **Penulis:** `Jujun S. Suriasumantri`
   - **Penerbit:** `Pustaka Sinar Harapan`
   - **Tahun Terbit:** `2016`
   - **ISBN:** `9789794161234`
   - **Stok:** `5`
   - **Kategori:** pilih `Filsafat` (kategori yang baru dibuat pada langkah 3)
4. Klik **Simpan**.

**Yang ditunjukkan:**
- Redirect ke daftar buku dengan flash message: `Buku "Filsafat Ilmu" berhasil ditambahkan.`
- Baris buku baru tampil di index.
- Kolom **Kategori** menampilkan teks `Filsafat`, bukan `category_id` mentah.

**Poin penjelasan:**
- `BookController@index` memakai eager loading `Book::with('category')`.
- Relasi `Book belongsTo Category` membuat nama kategori bisa ditampilkan langsung.
- Validasi `category_id` memastikan kategori yang dipilih benar-benar ada di tabel `categories`.

---

### Langkah 5 - Tambah anggota baru

1. Klik menu **Anggota**.
2. Klik **+ Tambah Anggota**.
3. Isi form dengan data contoh:
   - **Nama:** `Rizky Pratama`
   - **NIM:** `240512345678` (8-12 digit angka)
   - **Email:** `rizky.pratama@example.com`
   - **Nomor Telepon:** `081234567890`
   - **Alamat:** `Jl. Teknik Elektro No. 1, Surabaya`
   - **Status:** `Aktif`
4. Klik **Simpan**.
5. Uji validasi dengan mencoba menambahkan anggota baru memakai **NIM atau email yang sama**.

**Yang ditunjukkan:**
- Anggota baru tersimpan dan muncul di daftar anggota dengan flash message sukses.
- Saat data duplikat dikirim, form ditolak dan muncul pesan error validasi pada field NIM/email.
- Data lama tidak berubah.

**Poin penjelasan:**
- `StoreMemberRequest` memakai aturan `unique:members,nim` dan `unique:members,email`.
- NIM dibatasi `digits_between:8,12`.
- Status hanya boleh `aktif` atau `nonaktif`.

---

### Langkah 6 - Buat transaksi peminjaman

1. Klik menu **Peminjaman**.
2. Klik **+ Tambah Peminjaman**.
3. Isi form:
   - **Anggota:** pilih `Rizky Pratama (240512345678)` yang baru dibuat.
   - **Petugas:** biarkan apa adanya; field ini `readonly` dan otomatis berisi `Bambang Sutrisno`.
   - **Tanggal Pinjam:** tanggal hari ini.
   - **Tanggal Kembali:** hari ini + 7 hari.
   - **Buku yang Dipinjam:** centang `Filsafat Ilmu` (buku yang baru dibuat).
4. Klik **Simpan**.

**Yang ditunjukkan:**
- Redirect ke daftar peminjaman dengan flash message: `Transaksi peminjaman berhasil dibuat.`
- Transaksi baru tampil di bagian atas daftar.
- Field **Petugas** pada form sebelumnya tidak diinput manual, melainkan diambil dari user yang sedang login.

**Poin penjelasan:**
- `LoanController@store` mengisi `user_id` dengan `auth()->id()`.
- Tanggal kembali divalidasi `after_or_equal:tanggal_pinjam`.
- Buku yang dipinjam disimpan sebagai item pada tabel `loan_items` melalui relasi `loanItems()`.

---

### Langkah 7 - Lihat daftar peminjaman aktif

1. Masih di halaman **Peminjaman** (`/loans`).
2. Tunjukkan transaksi yang baru dibuat.

**Yang ditunjukkan:**
- Baris transaksi baru menampilkan:
  - **Anggota:** `Rizky Pratama`
  - **Petugas:** `Bambang Sutrisno`
  - **Buku:** `Filsafat Ilmu`
  - **Status:** badge `Dipinjam`
- Status `dipinjam` berasal dari nilai default kolom status saat transaksi dibuat.

**Poin penjelasan:**
- `LoanController@index` memakai eager loading `Loan::with(['member', 'user', 'loanItems.book'])`.
- Nama anggota, nama petugas, dan daftar buku tampil dari relasi, bukan ID mentah.
- Ini menghindari masalah N+1 query.

---

### Langkah 8 - Proses pengembalian buku

1. Pada baris transaksi yang baru dibuat di `/loans`, klik tombol **Kembalikan**.
2. Amati perubahan status.

**Yang ditunjukkan:**
- Redirect kembali ke daftar peminjaman.
- Flash message: `Buku berhasil dikembalikan.`
- Status transaksi berubah dari `Dipinjam` menjadi `Dikembalikan`.
- Jika membuka **Detail** transaksi, kolom **Tanggal Dikembalikan** terisi tanggal hari ini.

**Poin penjelasan:**
- `LoanController@kembalikan` mengubah `status` menjadi `dikembalikan` dan mengisi `tanggal_dikembalikan`.
- Form pengembalian memakai method `POST` dengan `@method('PUT')` ke route `loans.kembalikan`.
- Jika fitur web ini belum tersedia pada versi demo, pengembalian bisa dilakukan lewat Postman: `PUT {{baseUrl}}/loans/{id}/kembalikan` (lihat langkah 10).

---

### Langkah 9 - Tampilkan halaman laporan

1. Klik menu **Laporan** atau buka `/loans/report`.
2. Tunjukkan seluruh transaksi, termasuk transaksi yang baru diproses pada langkah 8.
3. Perhatikan status transaksi baru sudah menjadi `Dikembalikan`.

**Yang ditunjukkan:**
- Tabel laporan menampilkan ID, anggota, petugas, daftar buku, tanggal pinjam, tanggal kembali, dan status.
- Transaksi yang baru dibuat dan dikembalikan ikut tampil.
- Jika data lebih dari 10, muncul navigasi halaman (pagination).

**Poin penjelasan:**
- `LoanController@report` mengambil data dengan `Http::get(...'/api/loans')`, bukan query Eloquent langsung dari controller web.
- Data laporan berasal dari response JSON API, lalu ditampilkan oleh Blade.
- Pagination laporan mengikuti meta dari API (`current_page`, `last_page`).

---

### Langkah 10 - Demo API di Postman

Jalankan request berikut langsung dari Postman collection `app-perpustakaan`.

#### 10a. `GET /api/books`

1. Buka folder **Books** > **Get All Books**.
2. Pastikan URL: `http://127.0.0.1:8000/api/books?page=1`.
3. Klik **Send**.

**Yang ditunjukkan:**
- Status `200 OK`.
- Response JSON terstruktur dari `BookResource`.
- Setiap item memiliki field seperti `id`, `judul`, `penulis`, `penerbit`, `tahun_terbit`, `isbn`, `stok`, `sampul`, dan objek `category` berisi `id` dan `nama_kategori`.
- Terdapat `links` dan `meta` untuk pagination (10 data per halaman), misalnya `current_page`, `last_page`, `per_page`, dan `total`.
- Buku `Filsafat Ilmu` yang baru dibuat muncul di response, dengan `category.nama_kategori` bernilai `Filsafat`.

Contoh bentuk response:

```json
{
  "data": [
    {
      "id": 21,
      "judul": "Filsafat Ilmu",
      "penulis": "Jujun S. Suriasumantri",
      "penerbit": "Pustaka Sinar Harapan",
      "tahun_terbit": 2016,
      "isbn": "9789794161234",
      "stok": 5,
      "sampul": null,
      "category": {
        "id": 6,
        "nama_kategori": "Filsafat"
      }
    }
  ],
  "links": {},
  "meta": {
    "current_page": 1,
    "last_page": 3,
    "per_page": 10,
    "total": 21
  }
}
```

**Poin penjelasan:**
- API buku memakai `BookResource::collection()`.
- Relasi kategori di-eager load dengan `Book::with('category')` agar response tidak melakukan query berulang.
- Query parameter `judul` dan `category_id` tersedia untuk filter, serta `page` untuk pagination.

#### 10b. `GET /api/stats`

1. Buka folder **Stats** > **Get Stats**.
2. Pastikan URL: `http://127.0.0.1:8000/api/stats`.
3. Klik **Send**.

**Yang ditunjukkan:**
- Status `200 OK`.
- Response JSON berisi:
  - `total_buku` sesuai jumlah buku terkini.
  - `total_anggota` sesuai jumlah anggota terkini.
  - `peminjaman_aktif` sesuai jumlah transaksi berstatus `dipinjam` terkini.
- Bandingkan dengan dashboard pada langkah 2: **Peminjaman Aktif** sekarang berkurang satu karena transaksi pada langkah 6 sudah dikembalikan pada langkah 8.

Contoh bentuk response:

```json
{
  "total_buku": 21,
  "total_anggota": 16,
  "peminjaman_aktif": 3
}
```

**Poin penjelasan:**
- `StatsController@index` menghitung data langsung dari model: `Book::count()`, `Member::count()`, dan `Loan::where('status', 'dipinjam')->count()`.
- Endpoint ini yang dikonsumsi oleh Dashboard melalui `Http::get()`.
- Endpoint `GET /api/loans` juga dapat ditunjukkan untuk memperlihatkan data yang sama dengan halaman laporan.

---

## Ringkasan Endpoint yang Didemokan

| Metode | Endpoint | Kegunaan |
|---|---|---|
| GET | `/api/books` | Daftar buku + pagination + relasi kategori |
| GET | `/api/stats` | Statistik dashboard |
| GET | `/api/loans` | Data laporan peminjaman |
| PUT | `/api/loans/{id}/kembalikan` | Pengembalian lewat API (alternatif langkah 8) |

---

## Troubleshooting Saat Demo

| Gejala | Penyebab | Solusi |
|---|---|---|
| Statistik dashboard semua `0` | Server API internal port `8011` tidak berjalan | Jalankan `php artisan serve --port=8011` di terminal kedua |
| Halaman laporan kosong | Server API internal port `8011` tidak berjalan | Jalankan server API internal, lalu refresh halaman |
| Menu Kategori tidak muncul / akses `403` | Login sebagai petugas, bukan admin | Login ulang dengan `admin@pens.ac.id` |
| Gagal menambah anggota | NIM bukan 8-12 digit atau NIM/email sudah dipakai | Gunakan NIM angka 8-12 digit dan email unik |
| Tombol Kembalikan tidak muncul | Status transaksi bukan `dipinjam` | Pilih transaksi berstatus `Dipinjam` |
| Postman gagal connect | Server web port `8000` tidak berjalan atau `baseUrl` salah | Pastikan server berjalan dan `baseUrl` = `http://127.0.0.1:8000/api` |

---

## Checklist Demo Cepat

- [ ] Login admin berhasil dan navbar menampilkan nama + role
- [ ] Dashboard menampilkan 3 kartu statistik
- [ ] Kategori `Filsafat` berhasil ditambahkan
- [ ] Buku `Filsafat Ilmu` tersimpan dengan kategori `Filsafat`
- [ ] Anggota `Rizky Pratama` tersimpan dan validasi duplikat diuji
- [ ] Peminjaman dibuat dengan petugas otomatis dari sesi login
- [ ] Daftar peminjaman menampilkan nama anggota, petugas, dan buku
- [ ] Pengembalian mengubah status menjadi `Dikembalikan`
- [ ] Laporan menampilkan seluruh transaksi termasuk yang baru diproses
- [ ] Postman `GET /api/books` menampilkan JSON + pagination
- [ ] Postman `GET /api/stats` menampilkan statistik terkini
