# T-06 Seeding Seri dan Lintasan

Inti sistem. Modul ini mengubah daftar pendaftaran menjadi susunan seri dan lintasan yang siap dicetak. Kesalahan di sini berdampak pada seluruh hari lomba, sehingga cakupan pengujiannya paling ketat di antara semua modul.

Acuan: [05-seri-dan-lintasan.md](../05-seri-dan-lintasan.md)

---

### T-06-01 Migrasi dan model seri serta lintasan

Prasyarat : T-03-01
Acuan : [../08-database.md](../08-database.md) tabel `heats` dan `heat_lanes`
Perkiraan : S

Berkas yang disentuh:
- `database/migrations/*_create_heats_table.php`
- `database/migrations/*_create_heat_lanes_table.php`
- `app/Models/Heat.php`
- `app/Models/HeatLane.php`

Kriteria selesai:
- [ ] Indeks unik pada `event_id`, `age_group_id`, `round`, `heat_number`
- [ ] Indeks unik pada `heat_id` dan `lane_number` sehingga satu lintasan mustahil terisi dua orang
- [ ] Indeks unik pada `registration_id` sehingga satu peserta mustahil muncul di dua lintasan
- [ ] Kolom `round` bernilai `final` sebagai bawaan, disiapkan untuk babak penyisihan di kemudian hari
- [ ] Kolom `locked_at` menandai susunan yang sudah final

Uji:
- Unit test bahwa menyisipkan dua peserta pada lintasan sama melanggar indeks unik
- Unit test bahwa satu pendaftaran tidak dapat ditempatkan pada dua lintasan

Kedua indeks unik itu adalah pengaman terpenting pada seluruh skema. Kesalahan seeding yang lolos ke buku acara jauh lebih mahal daripada kegagalan penyimpanan yang terdeteksi lebih awal.

---

### T-06-02 Pengurutan peserta

Prasyarat : T-06-01
Acuan : [../05-seri-dan-lintasan.md](../05-seri-dan-lintasan.md) Langkah 1
Perkiraan : M

Berkas yang disentuh:
- `app/Services/Seeding/EntrantSorter.php`

Kriteria selesai:
- [ ] Peserta diurutkan naik berdasarkan `seed_time_ms`
- [ ] Peserta tanpa catatan waktu selalu berada setelah seluruh peserta yang punya waktu
- [ ] Urutan di antara sesama peserta NT diacak, bukan mengikuti urutan pendaftaran
- [ ] Pengacakan memakai benih tetap `hash(competition_id, event_id, age_group_id)`
- [ ] Peserta dengan seed time identik diurutkan berdasarkan nama klub lalu nama atlet
- [ ] Menjalankan pengurutan dua kali pada data yang sama menghasilkan urutan yang identik

Uji:
- Unit test sepuluh peserta NT menghasilkan urutan yang sama pada seratus kali eksekusi
- Unit test dua peserta berwaktu sama diurutkan alfabetis

Urutan pendaftaran sengaja tidak dipakai sebagai penentu bagi peserta NT karena akan memberi keuntungan kepada klub yang mendaftar paling awal, padahal keunggulan itu tidak ada hubungannya dengan kemampuan berenang.

---

### T-06-03 Pembagian ke seri

Prasyarat : T-06-02
Acuan : [../05-seri-dan-lintasan.md](../05-seri-dan-lintasan.md) Langkah 2 dan 3
Perkiraan : M

Berkas yang disentuh:
- `app/Services/Seeding/HeatDistributor.php`
- `app/Enums/SeedingMode.php`

Kriteria selesai:
- [ ] Jumlah seri dihitung sebagai `ceil(jumlah_peserta / jumlah_lintasan)`
- [ ] Mode `balanced` membagi peserta semerata mungkin, dengan sisa pembagian diberikan mulai dari seri terakhir
- [ ] Mode `fill_from_last` mengisi seri terakhir sampai penuh lebih dulu
- [ ] Peserta tercepat selalu berada di seri bernomor terbesar
- [ ] Kelompok umur tanpa peserta tidak menghasilkan seri sama sekali

Uji:
- Unit test 15 peserta dan 6 lintasan menghasilkan tiga seri berisi 5, 5, 5
- Unit test 17 peserta dan 6 lintasan menghasilkan tiga seri berisi 5, 6, 6
- Unit test 15 peserta dan 8 lintasan menghasilkan dua seri berisi 7 dan 8
- Unit test 20 peserta dan 8 lintasan menghasilkan tiga seri berisi 6, 7, 7
- Unit test mode `fill_from_last` atas 15 peserta dan 6 lintasan menghasilkan 3, 6, 6

---

### T-06-04 Penempatan lintasan

Prasyarat : T-06-03
Acuan : [../05-seri-dan-lintasan.md](../05-seri-dan-lintasan.md) Langkah 4
Perkiraan : S

Berkas yang disentuh:
- `app/Services/Seeding/LaneAssigner.php`

Kriteria selesai:
- [ ] Urutan lintasan tersedia untuk kolam 4, 5, 6, 8, dan 10 lintasan
- [ ] Peserta tercepat dalam satu seri mendapat lintasan tengah
- [ ] Seri yang tidak penuh memakai urutan yang dipotong, menyisakan lintasan tepi kosong
- [ ] Jumlah lintasan yang tidak terdaftar melempar pengecualian, bukan diam-diam memakai urutan berurut

Uji:
- Unit test 5 peserta di kolam 6 lintasan menempati lintasan 3, 4, 2, 5, 1
- Unit test 8 peserta di kolam 8 lintasan menempati lintasan 4, 5, 3, 6, 2, 7, 1, 8
- Unit test satu peserta di kolam 6 lintasan menempati lintasan 3

---

### T-06-05 Orkestrator seeding

Prasyarat : T-06-04
Acuan : [../05-seri-dan-lintasan.md](../05-seri-dan-lintasan.md)
Perkiraan : M

Kelas yang merangkai keempat langkah sebelumnya dan menyimpan hasilnya.

Berkas yang disentuh:
- `app/Actions/RunSeeding.php`

Kriteria selesai:
- [ ] Menerima kejuaraan, dan secara opsional satu nomor lomba atau satu kelompok umur saja
- [ ] Hanya mengikutkan pendaftaran berstatus `verified` yang tagihannya lunas atau belum ditagih
- [ ] Menjalankan ulang atas kombinasi yang sama menghapus susunan lama lalu membuat yang baru dalam satu transaksi
- [ ] Menolak berjalan atas seri yang sudah dikunci kecuali diberi penanda paksa
- [ ] Menjalankan ulang atas data yang tidak berubah menghasilkan susunan yang identik
- [ ] Seeding seluruh kejuaraan berisi seribu pendaftaran selesai di bawah sepuluh detik

Uji:
- Feature test seeding kejuaraan contoh dari seeder menghasilkan tiga seri pada kelompok umur berisi 15 peserta
- Feature test menjalankan seeding dua kali menghasilkan susunan yang identik
- Feature test seeding atas seri terkunci ditolak tanpa penanda paksa

---

### T-06-06 Layar pratinjau dan penguncian

Prasyarat : T-06-05
Acuan : [../02-fitur.md](../02-fitur.md) F-PAN-10, F-PAN-12
Perkiraan : M

Berkas yang disentuh:
- `app/Http/Controllers/Admin/SeedingController.php`
- `resources/views/admin/seeding/*`

Kriteria selesai:
- [ ] Panitia melihat susunan per nomor lomba dan kelompok umur sebelum mengunci
- [ ] Tampilan menyerupai buku acara agar kesalahan mudah terlihat
- [ ] Ringkasan menunjukkan nomor lomba yang belum diseeding dan yang belum dikunci
- [ ] Penguncian dapat dilakukan per nomor lomba atau seluruh kejuaraan sekaligus
- [ ] Penguncian menolak berjalan bila masih ada nomor lomba yang belum diseeding

Uji:
- Feature test penguncian seluruh kejuaraan mengisi `locked_at` pada seluruh seri
- Feature test penguncian ditolak saat masih ada nomor lomba tanpa seri

---

### T-06-07 Penyesuaian manual

Prasyarat : T-06-06
Acuan : [../05-seri-dan-lintasan.md](../05-seri-dan-lintasan.md) bagian Penyesuaian Manual
Perkiraan : M

Berkas yang disentuh:
- `app/Actions/SwapHeatLanes.php`
- `app/Actions/MoveEntrantToHeat.php`
- `app/Http/Controllers/Admin/HeatLaneController.php`

Kriteria selesai:
- [ ] Panitia dapat menukar dua peserta dalam seri yang sama
- [ ] Panitia dapat memindahkan peserta ke lintasan kosong pada seri mana pun dalam nomor dan grup yang sama
- [ ] Peserta yang mengundurkan diri dikeluarkan tanpa menggeser peserta lain
- [ ] Pemindahan ke lintasan yang sudah terisi ditolak
- [ ] Pemindahan lintas kelompok umur atau lintas nomor lomba ditolak
- [ ] Setiap tindakan tercatat pada jejak audit beserta susunan sebelum dan sesudahnya

Uji:
- Feature test penukaran dua peserta menghasilkan dua entri audit yang saling melengkapi
- Feature test pemindahan ke lintasan terisi ditolak

Peserta yang mundur tidak memicu penggeseran karena buku acara sudah tercetak dan panitia di pinggir kolam memanggil peserta berdasarkan nomor lintasan pada cetakan itu.

---

### T-06-08 Rangkaian uji regresi seeding

Prasyarat : T-06-05
Acuan : [../05-seri-dan-lintasan.md](../05-seri-dan-lintasan.md) bagian Acuan Uji
Perkiraan : M

Berkas yang disentuh:
- `tests/Feature/Seeding/*`
- `tests/Unit/Seeding/*`

Kriteria selesai:
- [ ] Kesembilan kasus pada tabel Acuan Uji dokumen sumber terimplementasi
- [ ] Contoh terhitung pada dokumen sumber diuji sebagai satu kasus utuh, memeriksa nama pada tiap lintasan di ketiga seri
- [ ] Tersedia uji properti yang memastikan seluruh peserta muncul tepat satu kali berapa pun jumlahnya
- [ ] Tersedia uji properti yang memastikan tidak ada seri melebihi jumlah lintasan
- [ ] Cakupan uji pada namespace `App\Services\Seeding` mencapai seratus persen baris

Uji:
- `php artisan test --filter=Seeding` lulus seluruhnya

Cakupan penuh diminta khusus untuk namespace ini, bukan untuk seluruh aplikasi. Logika seeding kecil, murni, dan konsekuensi kesalahannya besar, sehingga pengujian menyeluruh di sini murah dan sangat berharga.
