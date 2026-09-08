# 09 - Glosarium

## Istilah Perlombaan Renang

| Istilah | Padanan | Penjelasan |
| --- | --- | --- |
| Seri | Heat | Satu gelombang perenang yang berlomba bersamaan. Diperlukan ketika peserta lebih banyak daripada lintasan yang tersedia |
| Lintasan | Lane | Jalur renang bernomor. Kolam standar memiliki 6 atau 8 lintasan |
| Nomor lomba | Event | Kombinasi jarak, gaya, alat bantu, dan jenis kelamin, misalnya 50 M Gaya Dada Putra |
| Nomor acara | Event number | Angka urut nomor lomba dalam susunan acara. Konvensi umum: ganjil untuk putra, genap untuk putri |
| Kelompok umur | Age group | Pengelompokan peserta berdasarkan tahun lahir, ditulis Group 1 sampai Group 6 |
| Catatan waktu | Time | Waktu tempuh perenang. Dalam sistem ini dipecah menjadi seed time dan hasil lomba |
| Seed time | Waktu unggulan | Catatan waktu terbaik yang dilaporkan saat mendaftar. Dipakai hanya untuk mengurutkan peserta ke seri dan lintasan |
| Seeding | Pengunggulan | Proses mengurutkan peserta berdasarkan seed time lalu menempatkannya ke seri dan lintasan |
| NT | No Time | Peserta belum memiliki catatan waktu. Ditampilkan `NT` atau `99:99:99` |
| Buku acara | Start list, program book | Daftar cetak berisi seluruh nomor lomba beserta susunan seri dan lintasannya |
| Timed finals | Final berdasarkan waktu | Format lomba tanpa babak penyisihan. Setiap peserta berenang sekali, juara ditentukan dari waktu terbaik lintas seluruh seri |
| PB | Personal Best | Rekor pribadi. Ditandai ketika hasil lomba lebih cepat daripada seed time |
| DNS | Did Not Start | Peserta tidak hadir di balok start |
| DNF | Did Not Finish | Peserta start tetapi tidak menyelesaikan lomba |
| DSQ | Disqualified | Peserta didiskualifikasi karena pelanggaran teknik |
| Technical meeting | Rapat teknis | Pertemuan panitia dan official klub sebelum lomba untuk membahas susunan acara dan peraturan |
| PA / PI | Putra / Putri | Penanda jenis kelamin pada susunan acara |
| YOB | Year of Birth | Tahun lahir. Judul kolom yang lazim dipakai pada start list |

## Gaya dan Alat Bantu

| Istilah | Penjelasan |
| --- | --- |
| Gaya Bebas | Freestyle. Peserta bebas memilih teknik, umumnya gaya crawl |
| Gaya Dada | Breaststroke |
| Gaya Punggung | Backstroke. Start dilakukan dari dalam air |
| Gaya Kupu-Kupu | Butterfly |
| Gaya Ganti | Individual medley. Gabungan empat gaya dalam satu nomor |
| Fins | Kaki katak. Nomor dengan alat ini menghasilkan waktu jauh lebih cepat dan diperingkat terpisah |
| Kickboard | Papan luncur. Dipegang dengan tangan sehingga hanya kaki yang bekerja. Umum dipakai pada nomor usia dini |

## Istilah Sistem

| Istilah | Penjelasan |
| --- | --- |
| Kejuaraan | Satu acara lengkap. Satu instalasi sistem menampung banyak kejuaraan |
| Klub | Perkumpulan renang atau sekolah yang menaungi atlet |
| Pendaftar | Orang yang mengisi form pendaftaran publik: pelatih, orang tua, atau atlet sendiri. Tidak punya akun |
| Kode pendaftaran | Kode unik satu pengiriman form, misalnya `REG-7QK4M2`. Dipakai pendaftar saat menghubungi panitia |
| Pengiriman | Satu baris `registration_submissions`: satu kali pengisian form untuk satu atlet, bisa mencakup beberapa nomor lomba |
| Pendaftaran / entri | Satu baris yang menghubungkan satu atlet dengan satu nomor lomba |
| Matriks kelayakan | Tabel yang menentukan kelompok umur mana boleh mengikuti nomor lomba mana |
| Mode `balanced` | Pembagian seri yang meratakan jumlah peserta antar seri |
| Mode `fill_from_last` | Pembagian seri yang mengisi seri terakhir sampai penuh lebih dulu |
| Penguncian seeding | Penandaan bahwa susunan seri dan lintasan sudah final dan buku acara boleh dicetak |
| Batch import | Satu kali proses unggah Excel. Seluruh baris di dalamnya bisa dibatalkan sekaligus |
| Sentinel | Nilai khusus yang mewakili keadaan tertentu. Di sini `99:99:99` mewakili tidak ada catatan waktu, dan hanya dipakai pada tampilan |
| Jejak audit | Catatan perubahan berisi pelaku, waktu, nilai sebelum, dan nilai sesudah |

## Singkatan Kode Diskualifikasi

| Kode | Arti |
| --- | --- |
| `SF` | Start mendahului aba-aba |
| `ST` | Gerakan tidak sesuai gaya |
| `TN` | Pembalikan tidak sah |
| `FN` | Sentuhan finis tidak sah |
| `NA` | Tidak mencapai dinding pada pembalikan |
| `OT` | Alasan lain, dijelaskan pada keterangan |

## Kode Referensi Dokumen

| Awalan | Arti | Contoh |
| --- | --- | --- |
| `F-PUB-` | Fitur halaman publik | F-PUB-05 buku acara publik |
| `F-DAF-` | Fitur pendaftar tanpa akun | F-DAF-04 pilih nomor lomba |
| `F-PAN-` | Fitur panitia | F-PAN-10 jalankan seeding |
| `F-JUR-` | Fitur juri | F-JUR-02 layar input per seri |
| `F-ADM-` | Fitur super admin | F-ADM-04 mundurkan status kejuaraan |
| `V-` | Aturan validasi pendaftaran | V-06 batas nomor per atlet |
| `E-` | Kesalahan import yang menggagalkan baris | E-10 duplikat dalam berkas |
| `W-` | Peringatan import yang tidak menggagalkan baris | W-02 nama klub mirip |
