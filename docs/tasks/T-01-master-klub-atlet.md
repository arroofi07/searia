# T-01 Master Klub dan Atlet

Data induk yang dipakai berulang kali lintas kejuaraan. Klub dan atlet tidak terikat pada satu acara, sehingga atlet yang sama dapat diikutkan pada kejuaraan tahun berikutnya tanpa diketik ulang.

Acuan: [02-fitur.md](../02-fitur.md) F-PEL-02 sampai F-PEL-04, [08-database.md](../08-database.md)

**Status modul: selesai.** Enam task di bawah sudah diimplementasikan beserta tes Pest-nya.

| Task | Judul | Status |
| --- | --- | --- |
| T-01-01 | Migrasi dan model klub | Selesai |
| T-01-02 | Pengelolaan klub oleh panitia | Selesai |
| T-01-03 | Profil klub oleh pelatih | Selesai |
| T-01-04 | Migrasi dan model atlet | Selesai |
| T-01-05 | Pengelolaan atlet | Selesai |
| T-01-06 | Deteksi atlet ganda | Selesai |

---

### T-01-01 Migrasi dan model klub

Prasyarat : T-00-01
Acuan : [../08-database.md](../08-database.md) tabel `clubs`
Perkiraan : S
Status : Selesai

Berkas yang disentuh:
- `database/migrations/2026_01_01_000001_create_clubs_table.php`
- `app/Models/Club.php`
- `app/Enums/ClubType.php`
- `app/Enums/ClubStatus.php`
- `database/factories/ClubFactory.php`
- `tests/Unit/ClubUniqueNameTest.php`

Kriteria selesai:
- [x] Seluruh kolom pada dokumen acuan tersedia dengan tipe yang sesuai
- [x] Kolom `name` memiliki indeks unik
- [x] Enum `type` membedakan perkumpulan dan sekolah
- [x] Relasi `hasMany(Athlete)`, `hasMany(User)`, `hasMany(Invoice)` terdefinisi

Catatan implementasi: migrasi menambah `rejection_reason` dan `is_active` di luar definisi tabel acuan, dipakai T-01-02 untuk penolakan dan nonaktifkan klub.

Uji:
- [x] Unit test bahwa menyimpan dua klub bernama sama melempar pelanggaran indeks unik (`tests/Unit/ClubUniqueNameTest.php`)

---

### T-01-02 Pengelolaan klub oleh panitia

Prasyarat : T-01-01, T-00-04
Acuan : [../02-fitur.md](../02-fitur.md) F-PAN-05
Perkiraan : M
Status : Selesai

Panitia melihat seluruh klub, menyetujui pendaftaran klub baru, dan memperbaiki data yang salah.

Berkas yang disentuh:
- `app/Http/Controllers/Admin/ClubController.php`
- `app/Http/Requests/StoreClubRequest.php`
- `app/Http/Requests/UpdateClubRequest.php`
- `app/Http/Requests/RejectClubRequest.php`
- `app/Policies/ClubPolicy.php`
- `resources/views/admin/clubs/*`
- `tests/Feature/AdminClubManagementTest.php`

Kriteria selesai:
- [x] Daftar klub dapat dicari berdasarkan nama dan disaring berdasarkan status serta kota
- [x] Panitia dapat mengubah status klub menjadi terverifikasi atau ditolak
- [x] Penolakan wajib disertai alasan yang tersimpan
- [x] Klub yang sudah memiliki atlet tidak dapat dihapus, hanya dinonaktifkan

Uji:
- [x] Feature test penyetujuan klub mengubah status menjadi `verified`
- [x] Feature test penghapusan klub berisi atlet ditolak dengan pesan yang jelas

---

### T-01-03 Profil klub oleh pelatih

Prasyarat : T-01-01, T-00-04
Acuan : [../02-fitur.md](../02-fitur.md) F-PEL-02
Perkiraan : S
Status : Selesai

Berkas yang disentuh:
- `app/Http/Controllers/Coach/ClubProfileController.php`
- `app/Http/Requests/UpdateClubProfileRequest.php`
- `resources/views/coach/club/*`
- `tests/Feature/CoachClubProfileTest.php`

Kriteria selesai:
- [x] Pelatih hanya melihat dan mengubah klubnya sendiri
- [x] Logo dapat diunggah dengan batas ukuran dua megabita dan format JPG atau PNG
- [x] Perubahan nama klub setelah status terverifikasi memerlukan persetujuan panitia ulang
- [x] Pelatih tidak dapat mengubah status klubnya sendiri

Uji:
- [x] Feature test pelatih klub A menerima 403 saat membuka profil klub B

---

### T-01-04 Migrasi dan model atlet

Prasyarat : T-01-01
Acuan : [../08-database.md](../08-database.md) tabel `athletes`
Perkiraan : S
Status : Selesai

Berkas yang disentuh:
- `database/migrations/2026_01_01_000003_create_athletes_table.php`
- `app/Models/Athlete.php`
- `app/Enums/Gender.php`
- `database/factories/AthleteFactory.php`
- `tests/Unit/AthleteUniqueConstraintTest.php`

Kriteria selesai:
- [x] Indeks unik pada kombinasi `club_id`, `full_name`, `birth_year`
- [x] Kolom `birth_year` bertipe smallint, bukan tanggal lengkap
- [x] Tersedia scope `byBirthYearRange()` yang dipakai penentuan kelompok umur
- [x] Relasi `belongsTo(Club)` dan `hasMany(Registration)` terdefinisi

Uji:
- [x] Unit test bahwa dua atlet bernama sama di klub berbeda dapat disimpan
- [x] Unit test bahwa dua atlet bernama sama dengan tahun lahir sama di klub sama ditolak

---

### T-01-05 Pengelolaan atlet

Prasyarat : T-01-04, T-00-04
Acuan : [../02-fitur.md](../02-fitur.md) F-PEL-03
Perkiraan : M
Status : Selesai

Layar tambah, ubah, dan arsipkan atlet. Dipakai pelatih untuk atlet klubnya, dan panitia untuk seluruh atlet.

Berkas yang disentuh:
- `app/Http/Controllers/AthleteController.php`
- `app/Http/Requests/StoreAthleteRequest.php`
- `app/Http/Requests/UpdateAthleteRequest.php`
- `app/Policies/AthletePolicy.php`
- `resources/views/athletes/*`
- `tests/Feature/AthleteManagementTest.php`

Kriteria selesai:
- [x] Daftar atlet menampilkan nama, jenis kelamin, tahun lahir, dan klub
- [x] Daftar dapat disaring berdasarkan tahun lahir dan jenis kelamin
- [x] Atlet yang pernah didaftarkan pada kejuaraan tidak dapat dihapus, hanya dinonaktifkan
- [x] Pelatih hanya dapat membuat atlet pada klubnya sendiri, kolom klub terisi otomatis dan terkunci
- [x] Foto atlet bersifat opsional

Uji:
- [x] Feature test pelatih membuat atlet menghasilkan `club_id` klubnya sendiri walaupun permintaan HTTP memuat `club_id` klub lain

---

### T-01-06 Deteksi atlet ganda

Prasyarat : T-01-05
Acuan : [../07-import-excel.md](../07-import-excel.md) peringatan W-03
Perkiraan : M
Status : Selesai

Nama atlet sering ditulis dengan ejaan sedikit berbeda antar kejuaraan. Sistem menawarkan penggabungan, tetapi tidak pernah melakukannya secara otomatis karena membalikkan penggabungan yang salah jauh lebih sulit daripada membiarkan data ganda.

Berkas yang disentuh:
- `app/Services/AthleteMatcher.php`
- `app/Services/AthleteMerger.php`
- `app/Http/Controllers/Admin/AthleteMergeController.php`
- `app/Http/Requests/MergeAthleteRequest.php`
- `app/Exceptions/CannotMergeAthletesException.php`
- `config/searia.php` (`athlete_name_similarity_threshold`)
- `resources/views/admin/athletes/merge.blade.php`
- `tests/Unit/AthleteMatcherTest.php`
- `tests/Feature/AthleteMergeTest.php`

Kriteria selesai:
- [x] Saat menyimpan atlet baru, sistem menampilkan atlet mirip di klub yang sama dengan tahun lahir sama
- [x] Kemiripan dihitung dengan jarak teks, ambangnya dapat dikonfigurasi
- [x] Panitia dapat menggabungkan dua atlet, seluruh pendaftaran berpindah ke atlet yang dipertahankan
- [x] Penggabungan tercatat pada jejak audit beserta identitas atlet yang dihapus
- [x] Penggabungan ditolak bila kedua atlet terdaftar pada nomor lomba yang sama, karena akan melanggar indeks unik pendaftaran

Uji:
- [x] Unit test bahwa `SEARIA AQUATIC PADANG` dan `Searia Aquatic Pdg` terdeteksi mirip
- [x] Feature test penggabungan memindahkan seluruh baris `registrations`
