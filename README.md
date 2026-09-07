# SeaRIA

Sistem informasi kejuaraan renang: pendaftaran, seeding, buku acara, input hasil juri, publikasi, export, dan sertifikat.

## Pengembangan lokal

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install && npm run build
php artisan serve
php artisan queue:work
```

Gunakan PHP 8.3. Dokumentasi fitur ada di [`docs/`](docs/README.md). Panduan produksi: [`docs/deployment.md`](docs/deployment.md).

## Perintah penting

| Perintah | Fungsi |
| --- | --- |
| `competition:export` | Cadangan satu kejuaraan ke JSON |
| `competition:import` | Pulihkan dari JSON |
| `competition:backup-meet-day` | Cadangan otomatis sekitar hari lomba |
| `invoices:expire-unpaid` | Batalkan entri tagihan kedaluwarsa |

## Pemeriksaan

```bash
php artisan test
curl -s http://localhost/health
```
