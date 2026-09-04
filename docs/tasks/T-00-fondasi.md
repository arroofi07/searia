# T-00 Fondasi dan Autentikasi

Modul ini menyiapkan kerangka yang dipakai seluruh modul lain: konfigurasi lingkungan, peran pengguna, otorisasi, tata letak halaman, dan tipe data waktu renang.

Acuan: [01-gambaran-umum.md](../01-gambaran-umum.md), [06-catatan-waktu.md](../06-catatan-waktu.md)

---

### T-00-01 Perbaiki konfigurasi basis data dan jalankan migrasi awal

Prasyarat : -
Acuan : [../README.md](../README.md)
Perkiraan : S

`.env` menyetel `DB_CONNECTION=pgsql` tetapi `DB_PORT=3306` yang merupakan port MySQL. Kombinasi ini membuat migrasi pertama gagal tersambung.

Berkas yang disentuh:
- `.env`
- `.env.example`
- `config/database.php`

Kriteria selesai:
- [ ] Port basis data sesuai dengan driver yang dipilih, `5432` untuk PostgreSQL
- [ ] `.env.example` memuat nilai yang sama agar pengembang baru tidak mengulang masalah ini
- [ ] `php artisan migrate` berhasil pada basis data kosong
- [ ] `php artisan migrate:fresh` berhasil dijalankan berulang kali

Uji:
- Jalankan `php artisan migrate:fresh --seed` pada basis data bersih

---

### T-00-02 Tambah peran pengguna

Prasyarat : T-00-01
Acuan : [../01-gambaran-umum.md](../01-gambaran-umum.md)
Perkiraan : S

Menambahkan kolom peran pada tabel `users` beserta enum PHP-nya.

Berkas yang disentuh:
- `database/migrations/*_add_role_to_users_table.php`
- `app/Enums/UserRole.php`
- `app/Models/User.php`

Kriteria selesai:
- [ ] Enum `UserRole` memuat `SuperAdmin`, `Panitia`, `Pelatih`, `Juri`, `Peserta`
- [ ] Kolom `role`, `club_id`, `phone`, `is_active` tersedia pada `users`
- [ ] Model `User` melakukan cast `role` ke enum
- [ ] Tersedia metode bantu seperti `isPanitia()` yang dipakai di seluruh aplikasi

Uji:
- Unit test bahwa `User::factory()->panitia()->create()->isPanitia()` bernilai benar

---

### T-00-03 Autentikasi dan pendaftaran akun

Prasyarat : T-00-02
Acuan : [../02-fitur.md](../02-fitur.md) F-PEL-01, F-PES-01
Perkiraan : M

Halaman masuk, keluar, daftar akun, dan pemulihan kata sandi. Pendaftaran akun mandiri hanya boleh menghasilkan peran `Pelatih` atau `Peserta`.

Berkas yang disentuh:
- `routes/web.php`
- `app/Http/Controllers/Auth/*`
- `resources/views/auth/*`

Kriteria selesai:
- [ ] Pengguna dapat masuk, keluar, dan mengatur ulang kata sandi
- [ ] Form pendaftaran hanya menawarkan peran Pelatih dan Peserta
- [ ] Percobaan mendaftar dengan peran `panitia` lewat manipulasi permintaan HTTP ditolak
- [ ] Akun dengan `is_active` bernilai salah tidak dapat masuk

Uji:
- Feature test untuk masuk berhasil, masuk gagal, dan penolakan akun nonaktif
- Feature test bahwa `role=super_admin` yang dikirim manual pada form pendaftaran diabaikan

---

### T-00-04 Policy dan gate otorisasi

Prasyarat : T-00-02
Acuan : [../01-gambaran-umum.md](../01-gambaran-umum.md) bagian Matriks Hak Akses
Perkiraan : M

Menerjemahkan matriks hak akses menjadi Policy Laravel. Modul lain memanggilnya, tidak menulis pemeriksaan peran sendiri.

Berkas yang disentuh:
- `app/Policies/*`
- `app/Providers/AppServiceProvider.php`

Kriteria selesai:
- [ ] Ada Policy untuk `Club`, `Athlete`, `Competition`, `Registration`, `Heat`, `Result`
- [ ] Pelatih hanya dapat mengubah data milik klubnya sendiri
- [ ] Juri tidak memiliki izin apa pun terhadap `Registration`
- [ ] Tidak ada pemeriksaan `$user->role === ...` di dalam controller mana pun

Uji:
- Feature test bahwa pelatih klub A menerima respons 403 saat mengakses atlet klub B

---

### T-00-05 Kelas SwimTime untuk penguraian dan pemformatan waktu

Prasyarat : T-00-01
Acuan : [../06-catatan-waktu.md](../06-catatan-waktu.md)
Perkiraan : M
Status : Selesai

Satu kelas yang menjadi satu-satunya tempat aturan format waktu ditulis. Dipakai oleh pendaftaran, import Excel, input hasil, dan seluruh tampilan.

Berkas yang disentuh:
- `app/Support/SwimTime.php`
- `app/Casts/SwimTimeCast.php`
- `app/Exceptions/InvalidSwimTimeException.php`
- `tests/Unit/SwimTimeTest.php`

Kriteria selesai:
- [x] Menguraikan `52.20`, `52,20`, `00:52.20`, `1:34.70`, `00:01:34.70` menjadi milidetik yang benar
- [x] Menguraikan `NT`, string kosong, `-`, dan `99:99:99` menjadi `null`
- [x] Menguraikan bentuk tanpa pemisah `5220` dan `13470` bila mode input cepat aktif
- [x] Memformat milidetik menjadi `mm:ss.SS`, dan `hh:mm:ss.SS` bila melewati satu jam
- [x] Menolak masukan yang tidak dikenali dengan melempar `InvalidSwimTimeException`
- [x] Cast Eloquent dapat dipasang pada kolom integer milidetik

Uji:
- [x] Unit test tabel masukan dan keluaran untuk seluruh bentuk pada dokumen acuan
- [x] Uji bolak-balik: memformat lalu menguraikan kembali menghasilkan nilai semula

---

### T-00-06 Validasi batas kewajaran waktu

Prasyarat : T-00-05
Acuan : [../06-catatan-waktu.md](../06-catatan-waktu.md) bagian Batas kewajaran
Perkiraan : S
Status : Selesai

Aturan validasi yang menahan salah ketik seperti `00:05.20` untuk jarak 50 meter.

Berkas yang disentuh:
- `app/Rules/ReasonableSwimTime.php`
- `config/searia.php`
- `tests/Unit/ReasonableSwimTimeTest.php`

Kriteria selesai:
- [x] Ambang bawah dan atas per jarak dibaca dari konfigurasi, bukan ditulis tetap di kode
- [x] Aturan menolak nilai di luar rentang saat dipakai pada pendaftaran
- [x] Aturan hanya menghasilkan peringatan, bukan penolakan, saat dipakai pada input hasil juri
- [x] Nilai `null` yang berarti NT selalu lolos

Uji:
- [x] Unit test untuk jarak 25, 50, dan 100 meter pada batas bawah, batas atas, dan di antaranya

---

### T-00-07 Tata letak dan komponen antarmuka dasar

Prasyarat : T-00-03
Acuan : [../02-fitur.md](../02-fitur.md)
Perkiraan : M

Kerangka tampilan yang dipakai seluruh halaman: tata letak publik, tata letak panel pengelolaan, navigasi yang menyesuaikan peran, dan komponen tabel serta form yang berulang.

Berkas yang disentuh:
- `resources/views/layouts/*`
- `resources/views/components/*`
- `resources/css/app.css`

Kriteria selesai:
- [ ] Ada tata letak terpisah untuk halaman publik dan panel pengelolaan
- [ ] Menu navigasi hanya menampilkan tautan yang diizinkan Policy pengguna tersebut
- [ ] Ada komponen tabel dengan pengurutan, pencarian, dan penomoran halaman
- [ ] Ada komponen tampilan waktu renang yang memformat lewat `SwimTime`
- [ ] Tampilan terbaca pada lebar layar ponsel, karena juri bekerja di pinggir kolam

Uji:
- Uji manual pada lebar layar 375 piksel dan 1440 piksel

---

### T-00-08 Seeder data contoh

Prasyarat : T-00-02, T-00-05
Acuan : [../03-struktur-lomba.md](../03-struktur-lomba.md)
Perkiraan : M

Data contoh yang cukup untuk mengembangkan dan mendemokan seluruh modul tanpa mengetik manual.

Berkas yang disentuh:
- `database/seeders/*`
- `database/factories/*`

Kriteria selesai:
- [ ] Satu akun untuk tiap peran dengan kata sandi yang tercatat di berkas seeder
- [ ] Satu kejuaraan lengkap dengan enam kelompok umur dan tiga puluh empat nomor acara
- [ ] Sekitar sepuluh klub dan dua ratus atlet dengan sebaran tahun lahir yang wajar
- [ ] Pendaftaran contoh yang menghasilkan kelompok umur berisi 15 peserta, agar kasus tiga seri pada [../05-seri-dan-lintasan.md](../05-seri-dan-lintasan.md) dapat diperagakan
- [ ] Sekitar seperlima pendaftaran sengaja dibuat tanpa catatan waktu untuk menguji penanganan NT

Uji:
- `php artisan migrate:fresh --seed` selesai tanpa galat dan menghasilkan jumlah baris yang diharapkan
