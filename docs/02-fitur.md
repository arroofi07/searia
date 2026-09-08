# 02 - Katalog Fitur (MVP)

Kode fitur: `F-<peran>-<nomor>`.

## A. Publik (tanpa akun)

| Kode | Fitur |
| --- | --- |
| F-PUB-01 | Beranda kejuaraan aktif + tautan daftar |
| F-PUB-02 | Buku acara (layar + unduh PDF) setelah `seeded` |
| F-PUB-03 | Hasil lomba (layar + unduh PDF) setelah `published` |
| F-PUB-04 | Peringkat per nomor × kelompok umur |

## B. Pendaftar (tanpa akun)

| Kode | Fitur |
| --- | --- |
| F-DAF-01 | Pilih kejuaraan yang membuka pendaftaran |
| F-DAF-02 | Isi kontak + data atlet; **klub diketik sendiri** (nama + kota) |
| F-DAF-03 | Pilih nomor lomba + seed time (disaring gender & grup) |
| F-DAF-04 | Kirim → kode `REG-…` (tanpa tagihan) |

## C. Yang sengaja tidak ada di MVP

| Item | Alasan |
| --- | --- |
| Akun pelatih/peserta | Gesekan tanpa manfaat |
| Tagihan & bukti transfer | MVP tanpa pembayaran |
| Sertifikat | Ditunda |
| Mode offline juri | Ditunda |

## D. Panitia

| Kode | Fitur |
| --- | --- |
| F-PAN-01 | Kelola kejuaraan |
| F-PAN-02 | Kelola kelompok umur (Group 1–6 by tahun lahir) |
| F-PAN-03 | Kelola nomor lomba (PA/PI, jarak, gaya, alat) |
| F-PAN-04 | Matriks kelayakan grup × nomor |
| F-PAN-05 | Verifikasi klub baru |
| F-PAN-06 | Import Excel |
| F-PAN-07 | Input/edit/hapus entri manual |
| F-PAN-08 | Verifikasi pendaftaran |
| F-PAN-09 | Jalankan & kunci seeding |
| F-PAN-10 | Unduh PDF buku acara |
| F-PAN-11 | Unduh PDF hasil lomba |
| F-PAN-12 | Input/koreksi hasil |
| F-PAN-13 | Publikasikan hasil |
| F-PAN-14 | Export Excel peserta/start list/hasil (opsional) |

## E. Juri / Timer

| Kode | Fitur |
| --- | --- |
| F-JUR-01 | Daftar tugas nomor lomba |
| F-JUR-02 | Input waktu per seri + DNS/DNF/DSQ |
| F-JUR-03 | Parser waktu cepat + autosave |
| F-JUR-04 | Kunci seri |
| F-JUR-05 | Unduh PDF acara & hasil |

## F. Super Admin

| Kode | Fitur |
| --- | --- |
| F-ADM-01 | Kelola pengguna |
| F-ADM-02 | Multi-kejuaraan |
| F-ADM-03 | Mundurkan status kejuaraan |
| F-ADM-04 | Cadangan JSON kejuaraan (opsional) |
