# 04 - Alur Pendaftaran (MVP, tanpa pembayaran)

## Tiga Jalur Masuk

Semua bermuara ke tabel `registrations`.

```mermaid
flowchart TD
    A["Form publik tanpa akun"] --> Sub["registration_submissions"]
    Sub --> R["registrations status=pending"]
    B["Panitia import Excel"] --> R
    C["Panitia input manual"] --> R
    R --> V{"Panitia verifikasi"}
    V -->|OK| OK["status = verified"]
    V -->|Salah| Fix["status = rejected"]
    OK --> Seed["Siap seeding"]
```

**Hanya status `verified` yang ikut seeding.** Tidak ada syarat lunas/tagihan.

## Form Publik

1. Kontak pendaftar (nama, telepon, email opsional) + data atlet (nama, gender, tahun lahir) + **klub diketik sendiri** (nama klub + kabupaten/kota).
2. Pilih nomor lomba yang layak + seed time opsional (kosong = NT).
3. Kirim → kode `REG-…`. Pendaftar tidak mengedit lagi; koreksi lewat panitia.

Klub baru dari form masuk berstatus `pending` sampai panitia verifikasi. Nama klub yang sama dipakai ulang otomatis.

Proteksi: rate limit IP, honeypot.

## Import Excel

Template + validasi baris + pratinjau + commit. Rincian di [07-import-excel.md](07-import-excel.md).

## Input Manual Panitia

Panitia menambah atau mengubah pasangan atlet × nomor lomba dari admin tanpa lewat wizard publik. Dari form ini (atau dari antrean/detail entri) panitia juga dapat **menaikkan kelas** satu nomor: `registrations.age_group_id` diganti ke grup lebih tua, dengan alasan wajib, sementara tahun lahir atlet tetap. Form publik tidak menawarkan pilihan ini.

## Verifikasi

Panitia menyetujui atau menolak entri. Setelah `verified`, entri masuk antrean seeding.

## Validasi Inti

| Kode | Aturan |
| --- | --- |
| V-01 | Tahun lahir masuk salah satu kelompok umur |
| V-02 | Nomor sesuai gender atlet |
| V-03 | Nomor diizinkan matriks kelompok umur |
| V-04 | Jumlah nomor ≤ batas per atlet |
| V-05 | Tidak dobel atlet × nomor pada kejuaraan yang sama |
| V-06 | Format seed time valid atau kosong (NT) |

Naik kelas (hanya panitia): grup tujuan harus lebih tua, tetap ada di matriks nomor itu, alasan wajib (V-10), turun kelas ditolak (V-09). Excel: `9*` ke grup lebih tua terdekat. Jejak audit: `registration.age_group_override`. Menu: **Naik kelas**.
