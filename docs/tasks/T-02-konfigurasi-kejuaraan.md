# T-02 Konfigurasi Kejuaraan

Modul tempat panitia menyusun satu acara: identitas kejuaraan, kelompok umur berdasarkan tahun lahir, daftar nomor lomba, dan matriks yang menentukan grup mana boleh mengikuti nomor mana.

Acuan: [03-struktur-lomba.md](../03-struktur-lomba.md), [02-fitur.md](../02-fitur.md) F-PAN-01 sampai F-PAN-04

---

### T-02-01 Migrasi dan model kejuaraan

Prasyarat : T-00-01
Acuan : [../08-database.md](../08-database.md) tabel `competitions`
Perkiraan : S

Berkas yang disentuh:
- `database/migrations/*_create_competitions_table.php`
- `app/Models/Competition.php`
- `app/Enums/CompetitionStatus.php`
- `app/Enums/CompetitionType.php`

Kriteria selesai:
- [ ] Kolom `pool_lanes`, `max_events_per_athlete`, dan `seeding_mode` tersedia, karena ketiganya menjadi masukan algoritma seeding
- [ ] Enum status memuat tujuh nilai sesuai diagram pada dokumen acuan
- [ ] `slug` unik dan dihasilkan otomatis dari nama
- [ ] Scope `active()` mengembalikan kejuaraan yang belum berstatus `published`

Uji:
- Unit test pembuatan slug dari nama yang mengandung tanda baca

---

### T-02-02 Mesin status kejuaraan

Prasyarat : T-02-01, T-00-04
Acuan : [../01-gambaran-umum.md](../01-gambaran-umum.md) bagian Status Kejuaraan
Perkiraan : M

Perpindahan status tidak boleh sembarangan. Status yang melompat atau mundur tanpa kendali akan membuat buku acara yang sudah dicetak tidak cocok dengan data di sistem.

Berkas yang disentuh:
- `app/Services/CompetitionStatusTransition.php`
- `app/Http/Controllers/Admin/CompetitionStatusController.php`

Kriteria selesai:
- [ ] Hanya perpindahan yang tergambar pada diagram acuan yang diizinkan
- [ ] Perpindahan mundur hanya dapat dilakukan Super Admin dan wajib disertai alasan
- [ ] Perpindahan ke `seeded` ditolak bila masih ada nomor lomba yang belum diseeding
- [ ] Perpindahan ke `published` ditolak bila masih ada hasil yang belum diverifikasi
- [ ] Setiap perpindahan tercatat pada jejak audit

Uji:
- Unit test seluruh pasangan perpindahan, yang sah maupun yang harus ditolak
- Feature test panitia menerima 403 saat mencoba memundurkan status

---

### T-02-03 Formulir pengelolaan kejuaraan

Prasyarat : T-02-01
Acuan : [../02-fitur.md](../02-fitur.md) F-PAN-01
Perkiraan : M

Berkas yang disentuh:
- `app/Http/Controllers/Admin/CompetitionController.php`
- `app/Http/Requests/StoreCompetitionRequest.php`
- `resources/views/admin/competitions/*`

Kriteria selesai:
- [ ] Panitia dapat membuat, mengubah, dan menggandakan kejuaraan
- [ ] Penggandaan menyalin kelompok umur, nomor lomba, dan matriks kelayakan, tetapi tidak menyalin pendaftaran
- [ ] Tanggal penutupan pendaftaran wajib berada sebelum tanggal lomba
- [ ] `pool_lanes` dibatasi pada nilai yang masuk akal, yaitu 4 sampai 10
- [ ] Kejuaraan berstatus selain `draft` tidak dapat mengubah `pool_lanes`

Uji:
- Feature test penggandaan menghasilkan jumlah nomor lomba yang sama dan nol pendaftaran
- Feature test perubahan `pool_lanes` pada kejuaraan berstatus `seeded` ditolak

Larangan mengubah jumlah lintasan setelah keluar dari status `draft` bukan pembatasan berlebihan. Nilai itu menentukan berapa seri terbentuk, sehingga mengubahnya setelah ada pendaftaran akan membatalkan seluruh susunan yang mungkin sudah dicetak.

---

### T-02-04 Pengelolaan kelompok umur

Prasyarat : T-02-01
Acuan : [../03-struktur-lomba.md](../03-struktur-lomba.md) bagian Kelompok Umur
Perkiraan : M

Berkas yang disentuh:
- `database/migrations/*_create_age_groups_table.php`
- `app/Models/AgeGroup.php`
- `app/Http/Controllers/Admin/AgeGroupController.php`
- `app/Rules/NonOverlappingBirthYearRange.php`

Kriteria selesai:
- [ ] Panitia dapat menambah kelompok umur dengan kode, nama, rentang tahun lahir, dan label cetak
- [ ] Rentang tahun lahir antar grup dalam satu kejuaraan tidak boleh tumpang tindih, divalidasi saat menyimpan
- [ ] Tersedia tombol isi cepat yang membuat enam grup baku berdasarkan tahun penyelenggaraan
- [ ] Kelompok umur yang sudah memiliki pendaftaran tidak dapat dihapus
- [ ] Kolom `display_code` menampung label Romawi yang tercetak pada kolom AGE di buku acara

Uji:
- Unit test bahwa rentang 2015-2016 dan 2016-2017 ditolak karena tumpang tindih
- Unit test pencarian kelompok umur dari tahun lahir mengembalikan tepat satu hasil

---

### T-02-05 Pengelolaan nomor lomba

Prasyarat : T-02-01
Acuan : [../03-struktur-lomba.md](../03-struktur-lomba.md) bagian Nomor Lomba
Perkiraan : M

Berkas yang disentuh:
- `database/migrations/*_create_events_table.php`
- `app/Models/Event.php`
- `app/Enums/Stroke.php`
- `app/Enums/Equipment.php`
- `app/Http/Controllers/Admin/EventController.php`

Kriteria selesai:
- [ ] Nomor lomba dibentuk dari jarak, gaya, alat bantu, dan gender
- [ ] Nama nomor lomba dihasilkan dari keempat unsur tersebut, tidak disimpan sebagai teks
- [ ] `event_number` unik dalam satu kejuaraan dan boleh berupa angka bebas, termasuk tiga digit
- [ ] Tersedia pembuatan berpasangan yang menghasilkan nomor putra dan putri sekaligus
- [ ] Nomor lomba dapat diurutkan ulang dengan menyeret, tersimpan pada `sort_order`

Uji:
- Unit test bahwa nomor dengan jarak 50, gaya dada, tanpa alat, gender PA menghasilkan nama `50 M Gaya Dada - Putra`
- Feature test bahwa dua nomor dengan `event_number` sama dalam satu kejuaraan ditolak

---

### T-02-06 Matriks kelayakan grup terhadap nomor lomba

Prasyarat : T-02-04, T-02-05
Acuan : [../03-struktur-lomba.md](../03-struktur-lomba.md) bagian Matriks Kelayakan
Perkiraan : M

Layar berbentuk kisi dengan baris kelompok umur dan kolom nomor lomba, sama seperti tabel bertanda V pada brosur cetak. Hasilnya menjadi penyaring pada form pendaftaran, sehingga peserta tidak pernah dapat memilih nomor yang salah.

Berkas yang disentuh:
- `database/migrations/*_create_event_age_group_table.php`
- `app/Http/Controllers/Admin/EligibilityMatrixController.php`
- `resources/views/admin/competitions/eligibility.blade.php`

Kriteria selesai:
- [ ] Kisi menampilkan seluruh kelompok umur dan nomor lomba kejuaraan tersebut
- [ ] Kotak centang dapat diaktifkan per sel, per baris, dan per kolom sekaligus
- [ ] Perubahan tersimpan tanpa memuat ulang halaman
- [ ] Menghapus centang pada sel yang sudah memiliki pendaftaran memunculkan konfirmasi beserta jumlah pendaftaran yang terdampak
- [ ] Kisi tetap terbaca pada tiga puluh empat kolom, misalnya dengan kolom pertama yang membeku saat digulir

Uji:
- Feature test penyimpanan kisi menghasilkan jumlah baris `event_age_group` yang sesuai
- Feature test pencabutan kelayakan yang masih dipakai memerlukan konfirmasi

---

### T-02-07 Halaman ringkasan kesiapan kejuaraan

Prasyarat : T-02-06
Acuan : [../01-gambaran-umum.md](../01-gambaran-umum.md)
Perkiraan : S

Satu layar yang menunjukkan apakah kejuaraan siap dibuka pendaftarannya. Kesalahan konfigurasi jauh lebih murah diperbaiki sebelum pendaftaran dibuka daripada sesudahnya.

Berkas yang disentuh:
- `app/Services/CompetitionReadinessCheck.php`
- `resources/views/admin/competitions/readiness.blade.php`

Kriteria selesai:
- [ ] Menandai kejuaraan tanpa kelompok umur
- [ ] Menandai kejuaraan tanpa nomor lomba
- [ ] Menandai nomor lomba yang tidak memiliki satu pun kelompok umur pada matriks kelayakan
- [ ] Menandai kelompok umur yang tidak dapat mengikuti satu nomor pun
- [ ] Menandai biaya per nomor yang masih bernilai nol
- [ ] Tombol buka pendaftaran nonaktif selama masih ada temuan yang menggagalkan

Uji:
- Feature test bahwa nomor lomba tanpa kelompok umur muncul sebagai temuan
