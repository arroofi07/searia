# T-07 Buku Acara dan Cetak

Buku acara adalah keluaran fisik yang dipegang panitia, pelatih, dan peserta sepanjang hari lomba. Ketika jaringan bermasalah di kolam, cetakan inilah yang menjadi acuan tunggal, sehingga isinya harus persis sama dengan data di sistem.

Acuan: [03-struktur-lomba.md](../03-struktur-lomba.md), [05-seri-dan-lintasan.md](../05-seri-dan-lintasan.md), [02-fitur.md](../02-fitur.md) F-PAN-13, F-PAN-14

---

### T-07-01 Pasang paket PDF dan siapkan template dasar

Prasyarat : T-00-01
Acuan : [../README.md](../README.md)
Perkiraan : S

Berkas yang disentuh:
- `composer.json`
- `config/dompdf.php`
- `resources/views/pdf/layout.blade.php`

Kriteria selesai:
- [ ] Paket `barryvdh/laravel-dompdf` terpasang
- [ ] Template dasar memuat kop berisi logo penyelenggara, nama kejuaraan, tempat, dan tanggal
- [ ] Setiap halaman memuat nomor halaman dan waktu cetak
- [ ] Ukuran kertas A4 dengan margin yang aman untuk penjilidan

Uji:
- Uji manual bahwa PDF sepuluh halaman menampilkan kop dan nomor halaman pada seluruh halaman

Pencantuman waktu cetak bukan hiasan. Saat beredar dua versi cetakan di lapangan, waktu cetak adalah satu-satunya cara cepat mengetahui mana yang terbaru.

---

### T-07-02 Kueri penyusun buku acara

Prasyarat : T-06-05
Acuan : [../03-struktur-lomba.md](../03-struktur-lomba.md) bagian Sesi dan Urutan Tampil
Perkiraan : M

Berkas yang disentuh:
- `app/Services/StartListBuilder.php`

Kriteria selesai:
- [ ] Mengembalikan struktur bersarang sesi, nomor acara, kelompok umur, seri, lintasan
- [ ] Urutan mengikuti aturan pada dokumen acuan, termasuk kelompok umur termuda lebih dahulu
- [ ] Lintasan kosong tetap muncul sebagai baris kosong agar nomor lintasan tetap berurut di kertas
- [ ] Seluruh data terambil tanpa masalah kueri N tambah satu
- [ ] Kejuaraan berisi seribu pendaftaran tersusun di bawah tiga detik

Uji:
- Unit test jumlah kueri basis data tidak bertambah seiring jumlah nomor lomba
- Unit test urutan keluaran sesuai aturan pada dokumen acuan

Lintasan kosong sengaja ditampilkan. Menghilangkannya membuat nomor lintasan pada kertas melompat, dan petugas pemanggil peserta akan kebingungan mencari perenang yang sebenarnya memang tidak ada.

---

### T-07-03 Buku acara di layar

Prasyarat : T-07-02
Acuan : [../02-fitur.md](../02-fitur.md) F-PUB-05
Perkiraan : M

Berkas yang disentuh:
- `app/Http/Controllers/StartListController.php`
- `resources/views/start-list/*`

Kriteria selesai:
- [ ] Kolom yang ditampilkan adalah lintasan, nama, tahun lahir, kelompok umur, klub, kabupaten atau kota, dan catatan waktu
- [ ] Peserta tanpa catatan waktu ditampilkan sesuai konfigurasi, `NT` atau `99:99:99`
- [ ] Tersedia penyaring per nomor acara, kelompok umur, dan klub
- [ ] Tersedia pencarian nama yang menyorot hasilnya
- [ ] Halaman dapat diakses publik tanpa akun setelah kejuaraan berstatus `seeded`
- [ ] Halaman tidak dapat diakses sebelum status `seeded`
- [ ] Tampilan terbaca pada layar ponsel

Uji:
- Feature test tamu menerima 404 saat membuka start list kejuaraan berstatus `closed`
- Feature test tamu berhasil membuka start list kejuaraan berstatus `seeded`

---

### T-07-04 Cetak buku acara lengkap

Prasyarat : T-07-01, T-07-02
Acuan : [../02-fitur.md](../02-fitur.md) F-PAN-13
Perkiraan : M

Berkas yang disentuh:
- `app/Http/Controllers/Admin/StartListPdfController.php`
- `resources/views/pdf/start-list.blade.php`

Kriteria selesai:
- [ ] Susunannya menyerupai brosur cetak yang sudah dikenal panitia, dengan judul nomor acara diikuti blok tiap seri
- [ ] Satu nomor acara tidak terpotong di tengah blok seri bila masih muat pada halaman berikutnya
- [ ] Tersedia halaman daftar isi berisi nomor acara dan halamannya
- [ ] Tersedia pilihan cetak seluruh kejuaraan, satu sesi, atau satu nomor acara
- [ ] Pembuatan PDF untuk tiga puluh empat nomor acara selesai di bawah tiga puluh detik

Uji:
- Uji manual atas kejuaraan contoh dari seeder, memeriksa tidak ada blok seri yang terpotong

---

### T-07-05 Cetak lembar hasil kosong

Prasyarat : T-07-04
Acuan : [../02-fitur.md](../02-fitur.md) F-PAN-14
Perkiraan : S

Berkas yang disentuh:
- `resources/views/pdf/result-sheet.blade.php`

Kriteria selesai:
- [ ] Isinya sama dengan buku acara ditambah kolom kosong untuk waktu dan status
- [ ] Satu halaman memuat satu seri agar mudah dibagikan ke petugas per lintasan
- [ ] Tersedia ruang tanda tangan juri di bawah tiap seri
- [ ] Kolom waktu cukup lebar untuk ditulis tangan

Uji:
- Uji manual mencetak satu nomor acara lalu memeriksa keterbacaannya

Lembar ini adalah cadangan ketika listrik atau jaringan bermasalah. Kejuaraan tetap harus bisa berjalan tanpa sistem, dan hasilnya diketik belakangan.

---

### T-07-06 Start list per klub

Prasyarat : T-07-03
Acuan : [../02-fitur.md](../02-fitur.md) F-PEL-09
Perkiraan : S

Berkas yang disentuh:
- `app/Http/Controllers/Coach/ClubStartListController.php`

Kriteria selesai:
- [ ] Pelatih mengunduh PDF berisi entri klubnya sendiri saja
- [ ] Diurutkan berdasarkan waktu tampil, bukan berdasarkan nama atlet, agar dapat dipakai sebagai jadwal pemanasan
- [ ] Memuat nomor acara, kelompok umur, seri, dan lintasan tiap atlet
- [ ] Pelatih tidak dapat mengunduh start list klub lain lewat manipulasi parameter URL

Uji:
- Feature test pelatih klub A menerima 403 saat meminta start list klub B

Pengurutan berdasarkan waktu tampil dipilih karena itulah yang dibutuhkan pelatih di lapangan: mengetahui atlet mana yang harus disiapkan berikutnya.
