# T-02 Konfigurasi Kejuaraan

Modul tempat panitia menyusun satu acara: identitas kejuaraan, kelompok umur berdasarkan tahun lahir, daftar nomor lomba, dan matriks yang menentukan grup mana boleh mengikuti nomor mana.

Acuan: [03-struktur-lomba.md](../03-struktur-lomba.md), [02-fitur.md](../02-fitur.md) F-PAN-01 sampai F-PAN-04

**Status modul: selesai.** Tujuh task di bawah sudah diimplementasikan beserta tes Pest-nya.

| Task | Judul | Status |
| --- | --- | --- |
| T-02-01 | Migrasi dan model kejuaraan | Selesai |
| T-02-02 | Mesin status kejuaraan | Selesai |
| T-02-03 | Formulir pengelolaan kejuaraan | Selesai |
| T-02-04 | Pengelolaan kelompok umur | Selesai |
| T-02-05 | Pengelolaan nomor lomba | Selesai |
| T-02-06 | Matriks kelayakan | Selesai |
| T-02-07 | Ringkasan kesiapan | Selesai |

---

### T-02-01 Migrasi dan model kejuaraan

Prasyarat : T-00-01
Acuan : [../08-database.md](../08-database.md) tabel `competitions`
Perkiraan : S
Status : Selesai

Berkas yang disentuh:
- `database/migrations/2026_01_01_000004_create_competitions_table.php`
- `app/Models/Competition.php`
- `app/Enums/CompetitionStatus.php`
- `app/Enums/CompetitionType.php`
- `app/Enums/SeedingMode.php`
- `database/factories/CompetitionFactory.php`
- `tests/Unit/CompetitionSlugTest.php`

Kriteria selesai:
- [x] Kolom `pool_lanes`, `max_events_per_athlete`, dan `seeding_mode` tersedia, karena ketiganya menjadi masukan algoritma seeding
- [x] Enum status memuat tujuh nilai sesuai diagram pada dokumen acuan
- [x] `slug` unik dan dihasilkan otomatis dari nama
- [x] Scope `active()` mengembalikan kejuaraan yang belum berstatus `published`

Uji:
- [x] Unit test pembuatan slug dari nama yang mengandung tanda baca

---

### T-02-02 Mesin status kejuaraan

Prasyarat : T-02-01, T-00-04
Acuan : [../01-gambaran-umum.md](../01-gambaran-umum.md) bagian Status Kejuaraan
Perkiraan : M
Status : Selesai

Perpindahan status tidak boleh sembarangan. Status yang melompat atau mundur tanpa kendali akan membuat buku acara yang sudah dicetak tidak cocok dengan data di sistem.

Berkas yang disentuh:
- `app/Services/CompetitionStatusTransition.php`
- `app/Http/Controllers/Admin/CompetitionStatusController.php`
- `app/Http/Requests/TransitionCompetitionRequest.php`
- `app/Exceptions/CannotTransitionCompetitionException.php`
- `tests/Unit/CompetitionStatusTransitionTest.php`
- `tests/Feature/CompetitionStatusTest.php`

Catatan implementasi: tabel `heats`, `heat_lanes`, dan `results` ikut dibuat agar penjagaan ke `seeded` dan `published` dapat diuji. UI seeding dan input hasil tetap menjadi T-06 dan T-08.

Kriteria selesai:
- [x] Hanya perpindahan yang tergambar pada diagram acuan yang diizinkan
- [x] Perpindahan mundur hanya dapat dilakukan Super Admin dan wajib disertai alasan
- [x] Perpindahan ke `seeded` ditolak bila masih ada nomor lomba yang belum diseeding
- [x] Perpindahan ke `published` ditolak bila masih ada hasil yang belum diverifikasi
- [x] Setiap perpindahan tercatat pada jejak audit

Uji:
- [x] Unit test seluruh pasangan perpindahan, yang sah maupun yang harus ditolak
- [x] Feature test panitia menerima 403 saat mencoba memundurkan status

---

### T-02-03 Formulir pengelolaan kejuaraan

Prasyarat : T-02-01
Acuan : [../02-fitur.md](../02-fitur.md) F-PAN-01
Perkiraan : M
Status : Selesai

Berkas yang disentuh:
- `app/Http/Controllers/Admin/CompetitionController.php`
- `app/Http/Requests/StoreCompetitionRequest.php`
- `app/Http/Requests/UpdateCompetitionRequest.php`
- `app/Services/CompetitionDuplicator.php`
- `app/Policies/CompetitionPolicy.php`
- `resources/views/admin/competitions/*`
- `tests/Feature/CompetitionManagementTest.php`

Kriteria selesai:
- [x] Panitia dapat membuat, mengubah, dan menggandakan kejuaraan
- [x] Penggandaan menyalin kelompok umur, nomor lomba, dan matriks kelayakan, tetapi tidak menyalin pendaftaran
- [x] Tanggal penutupan pendaftaran wajib berada sebelum tanggal lomba
- [x] `pool_lanes` dibatasi pada nilai yang masuk akal, yaitu 4 sampai 10
- [x] Kejuaraan berstatus selain `draft` tidak dapat mengubah `pool_lanes`

Uji:
- [x] Feature test penggandaan menghasilkan jumlah nomor lomba yang sama dan nol pendaftaran
- [x] Feature test perubahan `pool_lanes` pada kejuaraan berstatus `seeded` ditolak

Larangan mengubah jumlah lintasan setelah keluar dari status `draft` bukan pembatasan berlebihan. Nilai itu menentukan berapa seri terbentuk, sehingga mengubahnya setelah ada pendaftaran akan membatalkan seluruh susunan yang mungkin sudah dicetak.

---

### T-02-04 Pengelolaan kelompok umur

Prasyarat : T-02-01
Acuan : [../03-struktur-lomba.md](../03-struktur-lomba.md) bagian Kelompok Umur
Perkiraan : M
Status : Selesai

Berkas yang disentuh:
- `database/migrations/2026_01_01_000005_create_age_groups_table.php`
- `app/Models/AgeGroup.php`
- `app/Http/Controllers/Admin/AgeGroupController.php`
- `app/Http/Requests/StoreAgeGroupRequest.php`
- `app/Rules/NonOverlappingBirthYearRange.php`
- `resources/views/admin/competitions/age-groups/index.blade.php`
- `tests/Unit/AgeGroupTest.php`

Kriteria selesai:
- [x] Panitia dapat menambah kelompok umur dengan kode, nama, rentang tahun lahir, dan label cetak
- [x] Rentang tahun lahir antar grup dalam satu kejuaraan tidak boleh tumpang tindih, divalidasi saat menyimpan
- [x] Tersedia tombol isi cepat yang membuat enam grup baku berdasarkan tahun penyelenggaraan
- [x] Kelompok umur yang sudah memiliki pendaftaran tidak dapat dihapus
- [x] Kolom `display_code` menampung label Romawi yang tercetak pada kolom AGE di buku acara

Uji:
- [x] Unit test bahwa rentang 2015-2016 dan 2016-2017 ditolak karena tumpang tindih
- [x] Unit test pencarian kelompok umur dari tahun lahir mengembalikan tepat satu hasil

---

### T-02-05 Pengelolaan nomor lomba

Prasyarat : T-02-01
Acuan : [../03-struktur-lomba.md](../03-struktur-lomba.md) bagian Nomor Lomba
Perkiraan : M
Status : Selesai

Berkas yang disentuh:
- `database/migrations/2026_01_01_000006_create_events_table.php`
- `app/Models/Event.php`
- `app/Enums/Stroke.php`
- `app/Enums/Equipment.php`
- `app/Enums/EventGender.php`
- `app/Http/Controllers/Admin/EventController.php`
- `app/Http/Requests/StoreEventRequest.php`
- `resources/views/admin/competitions/events/index.blade.php`
- `tests/Unit/EventNameTest.php`
- `tests/Feature/EventManagementTest.php`

Kriteria selesai:
- [x] Nomor lomba dibentuk dari jarak, gaya, alat bantu, dan gender
- [x] Nama nomor lomba dihasilkan dari keempat unsur tersebut, tidak disimpan sebagai teks
- [x] `event_number` unik dalam satu kejuaraan dan boleh berupa angka bebas, termasuk tiga digit
- [x] Tersedia pembuatan berpasangan yang menghasilkan nomor putra dan putri sekaligus
- [x] Nomor lomba dapat diurutkan ulang dengan menyeret, tersimpan pada `sort_order`

Uji:
- [x] Unit test bahwa nomor dengan jarak 50, gaya dada, tanpa alat, gender PA menghasilkan nama `50 M Gaya Dada - Putra`
- [x] Feature test bahwa dua nomor dengan `event_number` sama dalam satu kejuaraan ditolak

---

### T-02-06 Matriks kelayakan grup terhadap nomor lomba

Prasyarat : T-02-04, T-02-05
Acuan : [../03-struktur-lomba.md](../03-struktur-lomba.md) bagian Matriks Kelayakan
Perkiraan : M
Status : Selesai

Layar berbentuk kisi dengan baris kelompok umur dan kolom nomor lomba, sama seperti tabel bertanda V pada brosur cetak. Hasilnya menjadi penyaring pada form pendaftaran, sehingga peserta tidak pernah dapat memilih nomor yang salah.

Berkas yang disentuh:
- `database/migrations/2026_01_01_000007_create_event_age_group_table.php`
- `app/Http/Controllers/Admin/EligibilityMatrixController.php`
- `app/Http/Requests/UpdateEligibilityMatrixRequest.php`
- `resources/views/admin/competitions/eligibility.blade.php`
- `tests/Feature/EligibilityMatrixTest.php`

Kriteria selesai:
- [x] Kisi menampilkan seluruh kelompok umur dan nomor lomba kejuaraan tersebut
- [x] Kotak centang dapat diaktifkan per sel, per baris, dan per kolom sekaligus
- [x] Perubahan tersimpan tanpa memuat ulang halaman
- [x] Menghapus centang pada sel yang sudah memiliki pendaftaran memunculkan konfirmasi beserta jumlah pendaftaran yang terdampak
- [x] Kisi tetap terbaca pada tiga puluh empat kolom, misalnya dengan kolom pertama yang membeku saat digulir

Uji:
- [x] Feature test penyimpanan kisi menghasilkan jumlah baris `event_age_group` yang sesuai
- [x] Feature test pencabutan kelayakan yang masih dipakai memerlukan konfirmasi

---

### T-02-07 Halaman ringkasan kesiapan kejuaraan

Prasyarat : T-02-06
Acuan : [../01-gambaran-umum.md](../01-gambaran-umum.md)
Perkiraan : S
Status : Selesai

Satu layar yang menunjukkan apakah kejuaraan siap dibuka pendaftarannya. Kesalahan konfigurasi jauh lebih murah diperbaiki sebelum pendaftaran dibuka daripada sesudahnya.

Berkas yang disentuh:
- `app/Services/CompetitionReadinessCheck.php`
- `app/Http/Controllers/Admin/CompetitionReadinessController.php`
- `resources/views/admin/competitions/readiness.blade.php`
- `tests/Feature/CompetitionReadinessTest.php`

Kriteria selesai:
- [x] Menandai kejuaraan tanpa kelompok umur
- [x] Menandai kejuaraan tanpa nomor lomba
- [x] Menandai nomor lomba yang tidak memiliki satu pun kelompok umur pada matriks kelayakan
- [x] Menandai kelompok umur yang tidak dapat mengikuti satu nomor pun
- [x] Menandai biaya per nomor yang masih bernilai nol
- [x] Tombol buka pendaftaran nonaktif selama masih ada temuan yang menggagalkan

Uji:
- [x] Feature test bahwa nomor lomba tanpa kelompok umur muncul sebagai temuan
