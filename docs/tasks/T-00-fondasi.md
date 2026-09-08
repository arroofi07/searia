# T-00 Fondasi dan Autentikasi (MVP)

Acuan: [01-gambaran-umum.md](../01-gambaran-umum.md)

### T-00-01 Lingkungan dan migrasi dasar

Kriteria selesai:
- [x] Aplikasi Laravel berjalan dengan migrasi domain
- [x] Perintah `composer test` tersedia

### T-00-02 Peran internal

Kriteria selesai:
- [x] Enum `UserRole`: `super_admin`, `panitia`, `juri`
- [x] Tidak ada peran coach/peserta pada akun

### T-00-03 Auth dan layout

Kriteria selesai:
- [x] Login/logout berfungsi
- [x] Layout admin/juri/publik terpisah jelas

### T-00-04 SwimTime

Kriteria selesai:
- [x] Parser/format waktu milidetik (seed & hasil)
- [x] Unit test format `mm:ss.SS` dan ketikan singkat

### T-00-05 Policy dasar

Kriteria selesai:
- [x] Super admin bypass Gate
- [x] Panitia mengelola master; juri terbatas hasil
