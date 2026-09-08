# 01 - Gambaran Umum (MVP)

## Masalah yang Diselesaikan

Kejuaraan renang klub/pelajar masih sering dikelola lewat Excel bolak-balik. Sistem ini menyatukan pendaftaran, pembagian seri, cetak buku acara, input hasil, dan publikasi hasil dalam satu aplikasi web.

## Ruang Lingkup MVP

Termasuk:

- Informasi kejuaraan singkat (beranda).
- Pendaftaran lewat form publik tanpa akun.
- Input peserta oleh panitia (manual atau Excel).
- Verifikasi data peserta (tanpa pembayaran).
- Seeding seri dan lintasan otomatis.
- Unduh PDF buku acara (start list).
- Input hasil oleh juri dan panitia.
- Peringkat sederhana dan publikasi.
- Unduh PDF hasil lomba.

Di luar MVP (lihat [deferred](deferred/README.md)):

- Tagihan, bukti transfer, payment gateway.
- Sertifikat dan verifikasi QR.
- Mode offline juri, CMS arsip berat, anomaly detector.

## Peran Pengguna

| Peran | Akun? | Tugas |
| --- | --- | --- |
| Super Admin | Ya | Pengguna, multi-kejuaraan, mundurkan status |
| Panitia | Ya | Setup, daftar/verifikasi, seeding, cetak, koreksi hasil, publish |
| Juri / Timer | Ya | Input waktu per seri yang ditugaskan |
| Pendaftar | Tidak | Form publik → kode `REG-…` |
| Publik | Tidak | Lihat/unduh acara (setelah seeded) dan hasil (setelah published) |

Peran internal: `super_admin`, `panitia`, `juri`.

## Matriks Hak Akses (inti)

| Kemampuan | Super Admin | Panitia | Juri | Pendaftar | Publik |
| --- | --- | --- | --- | --- | --- |
| Kelola kejuaraan / grup / nomor | V | V | - | - | - |
| Master klub & atlet | V | V | - | - | - |
| Form pendaftaran publik | V | V | V | V | V |
| Input manual / Excel | V | V | - | - | - |
| Verifikasi pendaftaran | V | V | - | - | - |
| Seeding & kunci | V | V | - | - | - |
| Unduh PDF buku acara | V | V | V | - | V* |
| Input hasil | V | V | V | - | - |
| Publish hasil | V | V | - | - | - |
| Unduh PDF hasil | V | V | V | - | V** |

\* setelah status `seeded`  
\*\* setelah status `published`

## Status Kejuaraan

`draft → registration → closed → seeded → running → finished → published`

Mundur status hanya Super Admin.
