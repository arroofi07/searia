# T-01 Master Klub dan Atlet

Data induk yang dipakai berulang kali lintas kejuaraan. Klub dan atlet tidak terikat pada satu acara, sehingga atlet yang sama dapat diikutkan pada kejuaraan tahun berikutnya tanpa diketik ulang.

Acuan: [02-fitur.md](../02-fitur.md) F-PEL-02 sampai F-PEL-04, [08-database.md](../08-database.md)

---

### T-01-01 Migrasi dan model klub

Prasyarat : T-00-01
Acuan : [../08-database.md](../08-database.md) tabel `clubs`
Perkiraan : S

Berkas yang disentuh:
- `database/migrations/*_create_clubs_table.php`
- `app/Models/Club.php`
- `app/Enums/ClubType.php`
- `app/Enums/ClubStatus.php`

Kriteria selesai:
- [ ] Seluruh kolom pada dokumen acuan tersedia dengan tipe yang sesuai
- [ ] Kolom `name` memiliki indeks unik
- [ ] Enum `type` membedakan perkumpulan dan sekolah
- [ ] Relasi `hasMany(Athlete)`, `hasMany(User)`, `hasMany(Invoice)` terdefinisi

Uji:
- Unit test bahwa menyimpan dua klub bernama sama melempar pelanggaran indeks unik

---

### T-01-02 Pengelolaan klub oleh panitia

Prasyarat : T-01-01, T-00-04
Acuan : [../02-fitur.md](../02-fitur.md) F-PAN-05
Perkiraan : M

Panitia melihat seluruh klub, menyetujui pendaftaran klub baru, dan memperbaiki data yang salah.

Berkas yang disentuh:
- `app/Http/Controllers/Admin/ClubController.php`
- `app/Http/Requests/StoreClubRequest.php`
- `resources/views/admin/clubs/*`

Kriteria selesai:
- [ ] Daftar klub dapat dicari berdasarkan nama dan disaring berdasarkan status serta kota
- [ ] Panitia dapat mengubah status klub menjadi terverifikasi atau ditolak
- [ ] Penolakan wajib disertai alasan yang tersimpan
- [ ] Klub yang sudah memiliki atlet tidak dapat dihapus, hanya dinonaktifkan

Uji:
- Feature test penyetujuan klub mengubah status menjadi `verified`
- Feature test penghapusan klub berisi atlet ditolak dengan pesan yang jelas

---

### T-01-03 Profil klub oleh pelatih

Prasyarat : T-01-01, T-00-04
Acuan : [../02-fitur.md](../02-fitur.md) F-PEL-02
Perkiraan : S

Berkas yang disentuh:
- `app/Http/Controllers/Coach/ClubProfileController.php`
- `resources/views/coach/club/*`

Kriteria selesai:
- [ ] Pelatih hanya melihat dan mengubah klubnya sendiri
- [ ] Logo dapat diunggah dengan batas ukuran dua megabita dan format JPG atau PNG
- [ ] Perubahan nama klub setelah status terverifikasi memerlukan persetujuan panitia ulang
- [ ] Pelatih tidak dapat mengubah status klubnya sendiri

Uji:
- Feature test pelatih klub A menerima 403 saat membuka profil klub B

---

### T-01-04 Migrasi dan model atlet

Prasyarat : T-01-01
Acuan : [../08-database.md](../08-database.md) tabel `athletes`
Perkiraan : S

Berkas yang disentuh:
- `database/migrations/*_create_athletes_table.php`
- `app/Models/Athlete.php`
- `app/Enums/Gender.php`

Kriteria selesai:
- [ ] Indeks unik pada kombinasi `club_id`, `full_name`, `birth_year`
- [ ] Kolom `birth_year` bertipe smallint, bukan tanggal lengkap
- [ ] Tersedia scope `byBirthYearRange()` yang dipakai penentuan kelompok umur
- [ ] Relasi `belongsTo(Club)` dan `hasMany(Registration)` terdefinisi

Uji:
- Unit test bahwa dua atlet bernama sama di klub berbeda dapat disimpan
- Unit test bahwa dua atlet bernama sama dengan tahun lahir sama di klub sama ditolak

---

### T-01-05 Pengelolaan atlet

Prasyarat : T-01-04, T-00-04
Acuan : [../02-fitur.md](../02-fitur.md) F-PEL-03
Perkiraan : M

Layar tambah, ubah, dan arsipkan atlet. Dipakai pelatih untuk atlet klubnya, dan panitia untuk seluruh atlet.

Berkas yang disentuh:
- `app/Http/Controllers/AthleteController.php`
- `app/Http/Requests/StoreAthleteRequest.php`
- `resources/views/athletes/*`

Kriteria selesai:
- [ ] Daftar atlet menampilkan nama, jenis kelamin, tahun lahir, dan klub
- [ ] Daftar dapat disaring berdasarkan tahun lahir dan jenis kelamin
- [ ] Atlet yang pernah didaftarkan pada kejuaraan tidak dapat dihapus, hanya dinonaktifkan
- [ ] Pelatih hanya dapat membuat atlet pada klubnya sendiri, kolom klub terisi otomatis dan terkunci
- [ ] Foto atlet bersifat opsional

Uji:
- Feature test pelatih membuat atlet menghasilkan `club_id` klubnya sendiri walaupun permintaan HTTP memuat `club_id` klub lain

---

### T-01-06 Deteksi atlet ganda

Prasyarat : T-01-05
Acuan : [../07-import-excel.md](../07-import-excel.md) peringatan W-03
Perkiraan : M

Nama atlet sering ditulis dengan ejaan sedikit berbeda antar kejuaraan. Sistem menawarkan penggabungan, tetapi tidak pernah melakukannya secara otomatis karena membalikkan penggabungan yang salah jauh lebih sulit daripada membiarkan data ganda.

Berkas yang disentuh:
- `app/Services/AthleteMatcher.php`
- `app/Http/Controllers/Admin/AthleteMergeController.php`

Kriteria selesai:
- [ ] Saat menyimpan atlet baru, sistem menampilkan atlet mirip di klub yang sama dengan tahun lahir sama
- [ ] Kemiripan dihitung dengan jarak teks, ambangnya dapat dikonfigurasi
- [ ] Panitia dapat menggabungkan dua atlet, seluruh pendaftaran berpindah ke atlet yang dipertahankan
- [ ] Penggabungan tercatat pada jejak audit beserta identitas atlet yang dihapus
- [ ] Penggabungan ditolak bila kedua atlet terdaftar pada nomor lomba yang sama, karena akan melanggar indeks unik pendaftaran

Uji:
- Unit test bahwa `SEARIA AQUATIC PADANG` dan `Searia Aquatic Pdg` terdeteksi mirip
- Feature test penggabungan memindahkan seluruh baris `registrations`
