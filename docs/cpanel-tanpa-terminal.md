# cPanel tanpa terminal (SSH)

Panduan ini untuk panitia yang **hanya punya File Manager + browser**, tanpa akses `php artisan`.

## Logo kejuaraan tidak tampil di situs publik

### 1. Unggah logo lewat admin (paling mudah)

1. Masuk **Masuk panitia** di situs.
2. Buka kejuaraan → **Data acara** (edit).
3. Bagian **Logo kejuaraan** → pilih berkas JPG/PNG/WEBP (maks. 2 MB) → **Simpan**.
4. Di halaman ringkasan acara, pastikan thumbnail logo terlihat.

Jika di admin sudah terlihat tapi di landing page tidak:

### 2. Tes URL logo di browser

Buka tab baru (ganti domain dan ID kejuaraan):

`https://domain-anda.com/media/competitions/1/logo`

- **Gambar muncul** → lanjut langkah 3 (cache).
- **404 / kosong** → logo belum ada di server; ulangi unggah di admin (langkah 1).

### 3. Bersihkan cache (tanpa terminal)

Setelah kode terbaru terpasang di server:

1. Masuk sebagai Panitia/Super Admin.
2. Menu **Pilih acara** (daftar kejuaraan).
3. Klik **Bersihkan cache aplikasi**.

Refresh beranda dengan **Ctrl+F5**.

### 4. Bersihkan cache manual (File Manager)

Jika tombol di atas belum ada (kode lama), hapus berkas ini lewat **File Manager** (folder aplikasi Laravel):

| Hapus file ini jika ada |
| --- |
| `bootstrap/cache/config.php` |
| `bootstrap/cache/routes-v7.php` |
| `bootstrap/cache/packages.php` |
| `bootstrap/cache/services.php` |

Kosongkan isi (bukan hapus folder):

- `storage/framework/cache/data/`
- `storage/framework/views/`

Jangan hapus `.gitignore` di folder tersebut.

### 5. Salin logo manual (jika pernah upload di komputer lain)

Di komputer yang punya logo, lokasi berkas:

`storage/app/public/competitions/logos/`

Di cPanel, buat folder yang sama di server dan **unggah berkas logo** ke sana. Nama file harus sama dengan kolom `logo_path` di database (lihat phpMyAdmin tabel `competitions`, kolom `logo_path`), atau cukup **unggah ulang lewat admin**.

Tidak perlu symlink `public/storage` untuk logo: situs memakai `/media/competitions/{id}/logo`.

## Deploy kode baru tanpa terminal

1. Di komputer: build `npm run build` (jika ada perubahan CSS/JS), lalu unggah lewat FTP/File Manager:
   - folder `app/`, `resources/views/`, `routes/`, `public/css/`, `public/build/` sesuai perubahan.
2. **`public/css/home.css`** dan **`public/css/public.css`** — wajib ikut diunggah jika tampilan beranda diubah.
3. Lakukan langkah **bersihkan cache** di atas.
4. Pastikan `.env` di server: `APP_URL=https://domain-publik-anda` (HTTPS).

## Pratinjau logo tidak wajib pakai terminal

| Masalah | Solusi tanpa terminal |
| --- | --- |
| Logo di admin OK, publik kosong | Bersihkan cache + tes URL `/media/.../logo` |
| 404 pada URL logo | Unggah ulang logo di admin |
| CSS beranda berantakan | Unggah `public/css/home.css` + cache |
