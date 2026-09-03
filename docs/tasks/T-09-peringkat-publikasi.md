# T-09 Peringkat dan Publikasi

Modul yang mengubah kumpulan catatan waktu menjadi juara. Aturan yang paling sering disalahpahami ada di sini: peringkat dihitung lintas seluruh seri, bukan per seri.

Acuan: [06-catatan-waktu.md](../06-catatan-waktu.md) bagian Perhitungan Peringkat

---

### T-09-01 Layanan perhitungan peringkat

Prasyarat : T-08-01
Acuan : [../06-catatan-waktu.md](../06-catatan-waktu.md)
Perkiraan : M

Berkas yang disentuh:
- `app/Services/RankingCalculator.php`

Kriteria selesai:
- [ ] Peringkat dihitung per kombinasi nomor lomba dan kelompok umur, menggabungkan peserta dari seluruh seri
- [ ] Hanya hasil berstatus `ok` yang memperoleh peringkat
- [ ] Peserta berstatus DNS, DNF, dan DSQ tetap muncul di daftar, ditempatkan setelah seluruh peserta berperingkat
- [ ] Waktu yang sama menghasilkan peringkat yang sama, dan peringkat berikutnya dilewati
- [ ] Peringkat tidak disimpan sebagai kolom, melainkan dihitung saat kueri

Uji:
- Unit test peserta di seri kedua dengan waktu terbaik memperoleh peringkat satu
- Unit test dua peserta berwaktu sama menempati peringkat satu, dan peserta berikutnya peringkat tiga
- Unit test peserta DSQ tidak memperoleh peringkat tetapi tetap tampil di daftar

Peringkat sengaja tidak disimpan. Satu koreksi waktu mengubah peringkat banyak peserta sekaligus, dan nilai turunan semacam itu mudah menjadi tidak sinkron dengan sumbernya.

---

### T-09-02 Halaman hasil per nomor lomba

Prasyarat : T-09-01
Acuan : [../02-fitur.md](../02-fitur.md) F-PUB-06
Perkiraan : M

Berkas yang disentuh:
- `app/Http/Controllers/ResultController.php`
- `resources/views/results/*`

Kriteria selesai:
- [ ] Menampilkan peringkat, nama, klub, kabupaten atau kota, catatan waktu, seri, dan lintasan
- [ ] Tiga peringkat teratas ditandai secara visual
- [ ] Selisih waktu terhadap peringkat satu ditampilkan
- [ ] Peserta yang memperbaiki seed time-nya ditandai sebagai rekor pribadi
- [ ] Halaman hanya dapat diakses publik setelah kejuaraan berstatus `published`
- [ ] Panitia dapat melihat pratinjaunya sebelum publikasi

Uji:
- Feature test tamu menerima 404 pada kejuaraan berstatus `finished`
- Feature test panitia berhasil membuka pratinjau pada kejuaraan berstatus `finished`

---

### T-09-03 Verifikasi hasil sebelum publikasi

Prasyarat : T-09-01
Acuan : [../02-fitur.md](../02-fitur.md) F-PAN-16
Perkiraan : M

Berkas yang disentuh:
- `app/Http/Controllers/Admin/ResultVerificationController.php`
- `app/Services/ResultAnomalyDetector.php`

Kriteria selesai:
- [ ] Panitia melihat daftar seri yang hasilnya belum diverifikasi
- [ ] Sistem menandai kejanggalan berupa waktu yang jauh lebih cepat daripada seed time
- [ ] Sistem menandai waktu di luar batas kewajaran per jarak
- [ ] Sistem menandai seri yang seluruh pesertanya berstatus DNS
- [ ] Verifikasi dapat dilakukan per seri atau seluruh nomor lomba sekaligus
- [ ] Kejuaraan tidak dapat berpindah ke status `published` selama masih ada hasil yang belum diverifikasi

Uji:
- Feature test waktu sepuluh detik lebih cepat daripada seed time muncul sebagai kejanggalan
- Feature test perpindahan status ke `published` ditolak saat masih ada seri belum terverifikasi

Penandaan kejanggalan bukan penolakan. Perbaikan besar memang terjadi pada atlet usia dini, tetapi salah ketik jauh lebih sering, sehingga keduanya perlu dilihat manusia sebelum diumumkan.

---

### T-09-04 Rekap medali

Prasyarat : T-09-01
Acuan : [../06-catatan-waktu.md](../06-catatan-waktu.md) bagian Rekap medali
Perkiraan : M

Berkas yang disentuh:
- `app/Services/MedalTally.php`
- `resources/views/results/medals.blade.php`

Kriteria selesai:
- [ ] Peringkat satu, dua, dan tiga pada tiap kombinasi nomor lomba dan kelompok umur menghasilkan emas, perak, dan perunggu
- [ ] Waktu yang sama pada peringkat satu menghasilkan dua emas dan meniadakan perak
- [ ] Kelompok umur yang diikuti kurang dari tiga peserta ditandai pada laporan
- [ ] Rekap dapat dilihat per klub dan per kelompok umur

Uji:
- Unit test dua peserta berwaktu sama di peringkat satu menghasilkan dua emas dan nol perak
- Unit test kelompok umur berisi dua peserta muncul dengan penanda

---

### T-09-05 Klasemen klub

Prasyarat : T-09-04
Acuan : [../02-fitur.md](../02-fitur.md) F-PUB-07
Perkiraan : S

Berkas yang disentuh:
- `app/Services/ClubStanding.php`
- `resources/views/results/standings.blade.php`

Kriteria selesai:
- [ ] Klub diurutkan berdasarkan jumlah emas, lalu perak, lalu perunggu
- [ ] Jumlah total medali ditampilkan tetapi tidak dipakai sebagai penentu urutan
- [ ] Klub tanpa medali tetap tercantum di bagian bawah beserta jumlah pesertanya
- [ ] Jumlah peserta tiap klub ditampilkan sebagai konteks

Uji:
- Unit test klub dengan satu emas berada di atas klub dengan lima perak

Klub kecil yang mengirim tiga atlet dan klub besar yang mengirim lima puluh atlet tidak sebanding bila hanya dilihat dari jumlah medali. Menampilkan jumlah peserta memberi konteks itu tanpa perlu mengubah aturan pengurutannya.

---

### T-09-06 Halaman hasil per atlet

Prasyarat : T-09-01
Acuan : [../02-fitur.md](../02-fitur.md) F-PES-06, F-PEL-10
Perkiraan : S

Berkas yang disentuh:
- `app/Http/Controllers/AthleteResultController.php`

Kriteria selesai:
- [ ] Menampilkan seluruh nomor lomba yang diikuti atlet pada satu kejuaraan
- [ ] Menampilkan seed time, hasil, selisih, dan peringkat
- [ ] Menampilkan riwayat lintas kejuaraan bila atlet pernah berlomba sebelumnya
- [ ] Perbaikan catatan waktu ditandai sebagai rekor pribadi

Uji:
- Feature test halaman menampilkan seluruh nomor yang diikuti atlet

---

### T-09-07 Publikasi hasil

Prasyarat : T-09-03
Acuan : [../02-fitur.md](../02-fitur.md) F-PAN-17
Perkiraan : S

Berkas yang disentuh:
- `app/Actions/PublishResults.php`

Kriteria selesai:
- [ ] Perpindahan status ke `published` membuka halaman hasil untuk publik
- [ ] Publikasi ditolak bila masih ada seri yang belum terkunci atau belum terverifikasi
- [ ] Pelatih menerima pemberitahuan bahwa hasil sudah terbit
- [ ] Waktu publikasi tercatat dan ditampilkan pada halaman hasil
- [ ] Koreksi setelah publikasi tetap dimungkinkan, tetapi tercatat dan terlihat

Uji:
- Feature test publikasi dengan seri belum terkunci ditolak
- Feature test publikasi berhasil membuat halaman hasil dapat diakses tamu
