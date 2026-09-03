# T-10 Halaman Publik

Wajah sistem bagi orang yang belum punya akun: calon peserta yang mencari informasi, orang tua yang mencari hasil anaknya, dan penyelenggara lain yang menilai kredibilitas acara.

Acuan: [02-fitur.md](../02-fitur.md) bagian A, struktur mengikuti pola [speedzone.id](https://speedzone.id/)

---

### T-10-01 Beranda kejuaraan aktif

Prasyarat : T-02-01, T-00-07
Acuan : [../02-fitur.md](../02-fitur.md) F-PUB-01
Perkiraan : M

Berkas yang disentuh:
- `app/Http/Controllers/Public/HomeController.php`
- `resources/views/public/home.blade.php`

Kriteria selesai:
- [ ] Menampilkan kejuaraan yang sedang membuka pendaftaran beserta tombol menuju form
- [ ] Menampilkan hitung mundur menuju penutupan pendaftaran
- [ ] Menampilkan tiga kejuaraan terakhir yang hasilnya sudah terbit
- [ ] Tidak ada kejuaraan berstatus `draft` yang bocor ke halaman publik
- [ ] Halaman tetap tampil wajar ketika tidak ada kejuaraan aktif sama sekali

Uji:
- Feature test kejuaraan berstatus `draft` tidak muncul pada beranda
- Feature test beranda tanpa kejuaraan aktif tetap menghasilkan respons 200

---

### T-10-02 Halaman pengenalan dan ketentuan

Prasyarat : T-10-01
Acuan : [../02-fitur.md](../02-fitur.md) F-PUB-02, F-PUB-10
Perkiraan : S

Berkas yang disentuh:
- `resources/views/public/about.blade.php`
- `resources/views/public/terms.blade.php`

Kriteria selesai:
- [ ] Halaman pengenalan memuat deskripsi penyelenggara dan kategori lomba
- [ ] Halaman syarat dan ketentuan memuat naskah lengkap termasuk kebijakan privasi
- [ ] Isi halaman dapat disunting panitia tanpa mengubah kode
- [ ] Kedua halaman tertaut dari footer seluruh halaman publik

Uji:
- Feature test kedua halaman menghasilkan respons 200

---

### T-10-03 Halaman jadwal

Prasyarat : T-10-01
Acuan : [../02-fitur.md](../02-fitur.md) F-PUB-03
Perkiraan : S

Berkas yang disentuh:
- `resources/views/public/schedule.blade.php`

Kriteria selesai:
- [ ] Menampilkan tiga tonggak waktu: masa pendaftaran, technical meeting, dan hari lomba
- [ ] Tonggak yang sudah lewat ditandai berbeda dari yang akan datang
- [ ] Susunan acara per sesi ditampilkan setelah nomor lomba dikonfigurasi
- [ ] Tanggal ditulis dalam bahasa Indonesia

Uji:
- Feature test halaman menampilkan seluruh tonggak kejuaraan aktif

---

### T-10-04 Halaman biaya

Prasyarat : T-02-01
Acuan : [../02-fitur.md](../02-fitur.md) F-PUB-04
Perkiraan : S

Berkas yang disentuh:
- `resources/views/public/fees.blade.php`

Kriteria selesai:
- [ ] Menampilkan biaya per nomor lomba dan biaya keterlambatan
- [ ] Menampilkan informasi rekening dan batas waktu pembayaran
- [ ] Menampilkan keterangan bahwa harga belum tersedia ketika biaya masih bernilai nol
- [ ] Nominal diformat sebagai rupiah dengan pemisah ribuan

Uji:
- Feature test kejuaraan dengan biaya nol menampilkan keterangan harga belum tersedia

---

### T-10-05 Arsip riwayat kejuaraan

Prasyarat : T-09-07
Acuan : [../02-fitur.md](../02-fitur.md) F-PUB-08
Perkiraan : M

Berkas yang disentuh:
- `app/Http/Controllers/Public/ArchiveController.php`
- `resources/views/public/archive/*`

Kriteria selesai:
- [ ] Menampilkan seluruh kejuaraan yang hasilnya sudah terbit, dikelompokkan per tahun
- [ ] Tiap entri memuat nama, tempat, tanggal, jenis Resmi atau Fun, dan tautan ke hasil
- [ ] Tersedia penomoran halaman dan penyaring berdasarkan tahun
- [ ] Kejuaraan yang belum terbit hasilnya tidak muncul

Uji:
- Feature test hanya kejuaraan berstatus `published` yang muncul di arsip

---

### T-10-06 Pencarian atlet

Prasyarat : T-09-06
Acuan : [../02-fitur.md](../02-fitur.md) F-PUB-09
Perkiraan : M

Berkas yang disentuh:
- `app/Http/Controllers/Public/AthleteSearchController.php`

Kriteria selesai:
- [ ] Pencarian berdasarkan nama menampilkan atlet beserta klubnya
- [ ] Halaman atlet menampilkan riwayat lomba dan catatan waktu terbaiknya per nomor
- [ ] Hanya hasil dari kejuaraan yang sudah terbit yang ditampilkan
- [ ] Data pribadi berupa tanggal lahir lengkap dan nomor identitas tidak pernah ditampilkan
- [ ] Pencarian dibatasi lajunya untuk menahan pengambilan data massal

Uji:
- Feature test halaman publik atlet tidak memuat `identity_number` maupun `birth_date`

Pembatasan pada dua kriteria terakhir penting karena peserta kejuaraan ini sebagian besar anak-anak. Tahun lahir sudah cukup untuk menjelaskan kelompok umur, sedangkan tanggal lengkap dan nomor identitas tidak punya alasan untuk tampil di halaman publik.

---

### T-10-07 Optimasi tampilan dan penemuan

Prasyarat : T-10-05
Acuan : [../02-fitur.md](../02-fitur.md)
Perkiraan : M

Berkas yang disentuh:
- `resources/views/layouts/public.blade.php`
- `app/Http/Middleware/CachePublicPages.php`

Kriteria selesai:
- [ ] Halaman hasil dan start list dapat dibagikan lewat tautan langsung yang memuat pratinjau
- [ ] Judul dan deskripsi halaman menyesuaikan isinya
- [ ] Halaman publik di-cache dan cache-nya dibersihkan saat hasil dipublikasikan atau seeding dikunci
- [ ] Halaman hasil satu nomor lomba selesai dimuat di bawah satu detik pada koneksi seluler
- [ ] Tersedia peta situs untuk halaman arsip

Uji:
- Uji manual membagikan tautan hasil ke aplikasi pesan dan memeriksa pratinjaunya
- Uji beban sederhana pada halaman hasil dengan seratus permintaan bersamaan

Pembersihan cache saat publikasi bukan detail kecil. Hasil lomba adalah informasi yang paling banyak diakses tepat setelah terbit, dan cache basi pada momen itu akan langsung terlihat oleh banyak orang.
