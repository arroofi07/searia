# Penerapan Produksi SeaRIA

## Prasyarat

- PHP 8.3+, Composer, Node 20+, MySQL/MariaDB
- Proses pekerja antrean (`queue:work`) sebagai layanan yang dipantau
- Cron untuk `php artisan schedule:run` setiap menit

## Variabel lingkungan produksi

Salin `.env.example` lalu set minimal:

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domain-anda
LOG_CHANNEL=stack
LOG_STACK=daily
LOG_DAILY_DAYS=30
QUEUE_CONNECTION=database
FILESYSTEM_DISK=local
```

Jangan pernah menjalankan produksi dengan `APP_DEBUG=true`.

## CapRover / reverse proxy

Gambar logo memakai `asset()`, jadi URL-nya mengikuti skema HTTP yang dilihat PHP.

1. Di App Configs, set `APP_URL=https://domain-publik-anda` (bukan `http://localhost` dan bukan hostname internal `srv-captain--...`).
2. Opsional: set `ASSET_URL` ke URL HTTPS yang sama.
3. Document root container harus folder `public/` (bukan root repo). Cek `https://domain-anda/images/logo.png` — harus 200, bukan 404.
4. Setelah commit logo masuk git, klik **Force Rebuild** / deploy ulang. Menghubungkan repo saja tidak mengirim commit lama.
5. `public/build` tidak ada di git. Deploy harus menjalankan `npm ci && npm run build` atau halaman akan error Vite manifest.

Bila HTML halaman tampil tapi logo pecah, buka DevTools → Network pada `logo.png`: `blocked:mixed-content` berarti `APP_URL`/proxy HTTPS; 404 berarti document root atau file belum ter-deploy.

## Langkah penerapan

```bash
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
```

## Pengembalian versi

1. Kembalikan kode ke tag/commit sebelumnya.
2. Jalankan ulang `composer install --no-dev` dan `npm run build` bila aset berubah.
3. Bila ada migrasi mundur yang aman, jalankan `php artisan migrate:rollback` sesuai catatan rilis; jika tidak, pulihkan DB dari cadangan.
4. `php artisan config:cache && php artisan route:cache && php artisan view:cache`
5. Restart pekerja antrean.

## Cadangan dan pemulihan kejuaraan

```bash
php artisan competition:export {id|slug}
php artisan competition:import backups/competition-....json
```

Penjadwal `competition:backup-meet-day` mencadangkan kejuaraan sehari sebelum/sesudah hari lomba. Uji pemulihan di staging sebelum hari lomba.

## Pemeriksaan kesehatan

- `GET /up` — health bawaan Laravel
- `GET /health` — status basis data dan antrean (`jobs` / `failed_jobs`)

Job gagal: `php artisan queue:failed` lalu `php artisan queue:retry {id}`.

## Daftar periksa hari lomba

- [ ] Cadangan DB + `competition:export` untuk kejuaraan aktif
- [ ] Uji jaringan kolam (unggah bukti, input juri, autosave)
- [ ] Cetak cadangan: buku acara PDF + lembar hasil kosong Excel
- [ ] Pastikan pekerja antrean dan cron hidup
- [ ] Siapkan akun juri dan penugasan nomor lomba
- [ ] Mode offline: panitia siap impor lembar hasil Excel
