# 02 - Katalog Fitur

Setiap fitur diberi kode `F-<peran>-<nomor>` agar bisa dirujuk dari tiket pekerjaan dan test.

## A. Halaman Publik (tanpa akun)

| Kode | Fitur | Rincian |
| --- | --- | --- |
| F-PUB-01 | Beranda kejuaraan | Ringkasan acara aktif, tombol menuju pendaftaran, hitung mundur penutupan pendaftaran |
| F-PUB-02 | Halaman pengenalan | Deskripsi penyelenggara, kategori lomba, dan ketentuan umum |
| F-PUB-03 | Jadwal acara | Tiga tonggak waktu: masa pendaftaran, technical meeting, hari lomba |
| F-PUB-04 | Rincian biaya | Biaya per nomor lomba, biaya pendaftaran terlambat, informasi rekening |
| F-PUB-05 | Buku acara publik | Start list per nomor lomba: seri, lintasan, nama, tahun lahir, kelompok umur, klub, kota, seed time |
| F-PUB-06 | Hasil lomba | Peringkat per nomor lomba dan kelompok umur setelah status `published` |
| F-PUB-07 | Rekap medali dan klasemen klub | Perolehan emas, perak, perunggu per klub |
| F-PUB-08 | Riwayat kejuaraan | Arsip acara terdahulu beserta tautan hasilnya |
| F-PUB-09 | Pencarian atlet | Cari nama atlet untuk melihat nomor lomba yang diikuti dan waktunya |
| F-PUB-10 | Syarat dan ketentuan | Halaman statis |

## B. Pelatih / Official Klub

| Kode | Fitur | Rincian |
| --- | --- | --- |
| F-PEL-01 | Registrasi akun klub | Isi nama klub atau sekolah, kabupaten/kota, kontak. Menunggu verifikasi panitia |
| F-PEL-02 | Kelola profil klub | Nama, singkatan, kota, logo, kontak official |
| F-PEL-03 | Kelola daftar atlet | Tambah, ubah, arsipkan atlet. Data: nama lengkap, jenis kelamin, tahun lahir, foto |
| F-PEL-04 | Import atlet dari Excel | Unggah daftar atlet klub sendiri memakai template yang sama dengan panitia |
| F-PEL-05 | Pendaftaran nomor lomba | Pilih atlet, pilih nomor lomba yang sesuai kelompok umurnya, isi catatan waktu |
| F-PEL-06 | Ringkasan pendaftaran | Daftar seluruh entri klub beserta statusnya dan total biaya |
| F-PEL-07 | Ubah dan batalkan entri | Diizinkan selama status kejuaraan masih `registration` |
| F-PEL-08 | Unggah bukti pembayaran | Satu tagihan per klub, mencakup seluruh entri yang diverifikasi |
| F-PEL-09 | Unduh start list klub | PDF berisi entri klub sendiri lengkap dengan seri dan lintasan |
| F-PEL-10 | Lihat hasil atlet | Rekap catatan waktu atlet klub setelah lomba, termasuk perbandingan dengan seed time |
| F-PEL-11 | Unduh sertifikat | Sertifikat peserta dan sertifikat juara untuk atlet klub |

## C. Peserta / Atlet (pendaftaran mandiri)

| Kode | Fitur | Rincian |
| --- | --- | --- |
| F-PES-01 | Registrasi akun | Untuk atlet perorangan yang tidak diwakili pelatih |
| F-PES-02 | Lengkapi profil | Nama, jenis kelamin, tahun lahir, klub atau sekolah, kabupaten/kota |
| F-PES-03 | Daftar nomor lomba | Sama dengan F-PEL-05, dibatasi untuk dirinya sendiri |
| F-PES-04 | Lihat status pendaftaran | Menunggu verifikasi, diterima, atau ditolak beserta alasannya |
| F-PES-05 | Lihat penempatan | Nomor acara, seri, dan lintasan setelah seeding dikunci |
| F-PES-06 | Lihat hasil pribadi | Catatan waktu, peringkat, dan selisih terhadap seed time |

## D. Panitia

| Kode | Fitur | Rincian |
| --- | --- | --- |
| F-PAN-01 | Kelola kejuaraan | Nama, tempat, tanggal, jumlah lintasan kolam, panjang kolam, batas nomor per atlet |
| F-PAN-02 | Kelola kelompok umur | Definisi Group 1 sampai Group 6 berdasarkan rentang tahun lahir |
| F-PAN-03 | Kelola nomor lomba | Nomor acara, gender, jarak, gaya, alat bantu, urutan tampil |
| F-PAN-04 | Matriks kelayakan | Tentukan kelompok umur mana yang boleh mengikuti tiap nomor lomba |
| F-PAN-05 | Verifikasi klub | Setujui atau tolak pendaftaran akun klub |
| F-PAN-06 | Import peserta dari Excel | Unggah, pratinjau, perbaiki, lalu commit. Rincian di [Import Excel](07-import-excel.md) |
| F-PAN-07 | Verifikasi pendaftaran | Setujui, tolak dengan alasan, atau minta perbaikan data |
| F-PAN-08 | Koreksi kelompok umur | Perbaiki penempatan grup bila tahun lahir salah input |
| F-PAN-09 | Kelola tagihan | Terbitkan tagihan per klub, verifikasi bukti transfer |
| F-PAN-10 | Jalankan seeding | Bagi peserta ke seri dan lintasan otomatis per nomor lomba dan kelompok umur |
| F-PAN-11 | Penyesuaian manual | Tukar lintasan atau pindahkan peserta antar seri, dengan pencatatan audit |
| F-PAN-12 | Kunci seeding | Setelah dikunci, susunan tidak berubah kecuali lewat F-PAN-11 |
| F-PAN-13 | Cetak buku acara | PDF seluruh nomor lomba, dikelompokkan per sesi, per nomor acara, per seri |
| F-PAN-14 | Cetak lembar hasil kosong | Lembar untuk dicatat manual di pinggir kolam sebagai cadangan |
| F-PAN-15 | Kelola akun juri | Buat akun juri dan tetapkan nomor lomba yang menjadi tanggung jawabnya |
| F-PAN-16 | Verifikasi hasil | Periksa hasil yang diinput juri sebelum dipublikasikan |
| F-PAN-17 | Publikasikan hasil | Ubah status kejuaraan menjadi `published` |
| F-PAN-18 | Export data | Unduh daftar peserta, start list, dan hasil dalam format Excel |
| F-PAN-19 | Rekap medali | Perhitungan otomatis medali per klub dan per kelompok umur |
| F-PAN-20 | Jejak audit | Riwayat perubahan seeding, hasil, dan status pendaftaran |

## E. Juri / Timer

| Kode | Fitur | Rincian |
| --- | --- | --- |
| F-JUR-01 | Daftar tugas | Nomor lomba yang ditugaskan kepadanya beserta status pengisian |
| F-JUR-02 | Layar input per seri | Baris per lintasan, sudah terisi nama, klub, dan seed time. Kolom yang diisi hanya waktu dan status |
| F-JUR-03 | Input cepat catatan waktu | Menerima ketikan singkat seperti `3470` untuk `00:34.70`, tanpa perlu mengetik pemisah |
| F-JUR-04 | Tandai status khusus | DNS (tidak start), DNF (tidak selesai), DSQ (diskualifikasi) dengan kode dan alasan |
| F-JUR-05 | Simpan otomatis | Perubahan tersimpan per baris agar tidak hilang bila koneksi terputus |
| F-JUR-06 | Kunci seri | Tandai seri selesai. Setelah itu koreksi hanya bisa dilakukan panitia |
| F-JUR-07 | Mode luring terbatas | Layar seri tetap bisa diisi saat koneksi hilang, lalu disinkronkan saat tersambung kembali |

## F. Super Admin

| Kode | Fitur | Rincian |
| --- | --- | --- |
| F-ADM-01 | Kelola pengguna dan peran | Buat, nonaktifkan, dan ubah peran akun |
| F-ADM-02 | Kelola beberapa kejuaraan | Satu instalasi menangani banyak acara sekaligus |
| F-ADM-03 | Konfigurasi global | Mode seeding bawaan, format tampilan waktu, teks sertifikat |
| F-ADM-04 | Mundurkan status kejuaraan | Satu-satunya peran yang dapat mengembalikan status, misalnya dari `seeded` ke `closed` |
| F-ADM-05 | Cadangan dan pemulihan data | Export penuh satu kejuaraan dalam satu berkas |

## Prioritas Pengerjaan

Fitur dikelompokkan menjadi tiga gelombang agar sistem bisa dipakai pada kejuaraan pertama tanpa harus menunggu seluruhnya selesai.

| Gelombang | Isi | Alasan |
| --- | --- | --- |
| 1 - Wajib | F-PAN-01 sampai F-PAN-04, F-PAN-06, F-PAN-07, F-PAN-10, F-PAN-12, F-PAN-13, F-PEL-03, F-PEL-05, F-JUR-02, F-JUR-04, F-PUB-05, F-PUB-06 | Cukup untuk menjalankan satu kejuaraan dari pendaftaran sampai hasil |
| 2 - Penting | Pembayaran, verifikasi klub, rekap medali, export Excel, sertifikat, riwayat kejuaraan | Mengurangi pekerjaan manual panitia |
| 3 - Penyempurnaan | Mode luring juri, pencarian atlet, jejak audit terperinci, cadangan data | Peningkatan kenyamanan dan tata kelola |
