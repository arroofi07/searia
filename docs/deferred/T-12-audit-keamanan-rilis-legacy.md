# T-12 Audit, Keamanan, dan Rilis

Modul penutup yang membuat sistem layak dipakai di lingkungan nyata. Sebagian besar isinya tidak terlihat pengguna, tetapi menentukan apakah sistem bertahan saat terjadi sengketa hasil atau gangguan pada hari lomba.

Acuan: [01-gambaran-umum.md](../01-gambaran-umum.md), [08-database.md](../08-database.md) tabel `activity_logs`

---

### T-12-01 Infrastruktur jejak audit

Prasyarat : T-06-07, T-08-08
Acuan : [../08-database.md](../08-database.md)
Perkiraan : M

Berkas yang disentuh:
- `database/migrations/*_create_activity_logs_table.php`
- `app/Models/ActivityLog.php`
- `app/Concerns/LogsActivity.php`

Kriteria selesai:
- [ ] Mencatat pelaku, tindakan, model yang disentuh, nilai lama, nilai baru, alasan, dan alamat IP
- [ ] Trait dapat dipasang pada model tanpa mengubah controller
- [ ] Tindakan yang wajib tercatat mencakup perubahan status kejuaraan, penukaran lintasan, seeding ulang, koreksi hasil, penggabungan atlet, dan verifikasi pembayaran
- [ ] Kata sandi dan berkas bukti transfer tidak pernah ikut tersimpan pada nilai lama maupun baru
- [ ] Entri audit tidak dapat diubah maupun dihapus lewat antarmuka mana pun

Uji:
- Unit test bahwa kolom kata sandi tersaring dari catatan audit
- Feature test koreksi hasil menghasilkan satu entri audit lengkap

---

### T-12-02 Layar penelusuran audit

Prasyarat : T-12-01
Acuan : [../02-fitur.md](../02-fitur.md) F-PAN-20
Perkiraan : S

Berkas yang disentuh:
- `app/Http/Controllers/Admin/ActivityLogController.php`

Kriteria selesai:
- [ ] Dapat disaring berdasarkan pengguna, jenis tindakan, dan rentang waktu
- [ ] Riwayat satu objek dapat dibuka langsung dari layar objek tersebut
- [ ] Perubahan nilai ditampilkan berdampingan antara sebelum dan sesudah
- [ ] Hanya Super Admin dan panitia yang dapat mengaksesnya

Uji:
- Feature test juri menerima 403 saat membuka layar audit

---

### T-12-03 Pengetatan otorisasi menyeluruh

Prasyarat : T-00-04
Acuan : [../01-gambaran-umum.md](../01-gambaran-umum.md) bagian Matriks Hak Akses
Perkiraan : M

Penelusuran seluruh route untuk memastikan tidak ada yang terlewat dari pemeriksaan Policy.

Berkas yang disentuh:
- `routes/web.php`
- `tests/Feature/Authorization/*`

Kriteria selesai:
- [ ] Setiap route yang mengubah data memanggil `authorize()` atau dilindungi middleware policy
- [ ] Tersedia test yang menelusuri seluruh route dan menggagalkan build bila ada yang tanpa perlindungan
- [ ] Setiap sel pada matriks hak akses memiliki satu test yang memastikan perilakunya
- [ ] Referensi objek langsung lewat parameter URL selalu diperiksa kepemilikannya

Uji:
- Feature test tabel matriks hak akses secara menyeluruh
- Test penelusuran route yang gagal bila ada route baru tanpa otorisasi

Test penelusuran route pada kriteria kedua adalah yang paling berharga di antara keempatnya. Ia menangkap kelalaian pada route yang ditambahkan berbulan-bulan kemudian, ketika tidak ada lagi yang ingat aturan ini pernah ada.

---

### T-12-04 Pengetatan keamanan berkas unggahan

Prasyarat : T-05-04, T-04-02
Acuan : [../02-fitur.md](../02-fitur.md)
Perkiraan : M

Berkas yang disentuh:
- `app/Http/Controllers/SecureFileController.php`
- `config/filesystems.php`

Kriteria selesai:
- [ ] Bukti transfer, foto atlet, dan berkas import tersimpan pada disk privat
- [ ] Akses berkas selalu melewati controller yang memeriksa Policy
- [ ] Tipe berkas diperiksa dari isinya, bukan hanya dari ekstensi nama
- [ ] Nama berkas yang diunggah tidak pernah dipakai langsung sebagai nama penyimpanan
- [ ] Batas ukuran ditegakkan di sisi server, bukan hanya di sisi peramban

Uji:
- Feature test akses langsung ke path penyimpanan bukti transfer menghasilkan 404
- Feature test berkas PHP yang dinamai ulang menjadi `.jpg` ditolak

---

### T-12-05 Pembatasan laju dan perlindungan penyalahgunaan

Prasyarat : T-10-06
Acuan : [../02-fitur.md](../02-fitur.md)
Perkiraan : S

Berkas yang disentuh:
- `app/Providers/AppServiceProvider.php`
- `bootstrap/app.php`

Kriteria selesai:
- [ ] Percobaan masuk dibatasi lajunya per alamat IP dan per alamat surel
- [ ] Pencarian atlet publik dibatasi lajunya
- [ ] Unggahan berkas dibatasi jumlahnya per pengguna per jam
- [ ] Pemulihan kata sandi dibatasi lajunya
- [ ] Perlindungan CSRF aktif pada seluruh form

Uji:
- Feature test percobaan masuk keenam dalam satu menit menerima respons 429

---

### T-12-06 Pencadangan dan pemulihan data kejuaraan

Prasyarat : T-09-07
Acuan : [../02-fitur.md](../02-fitur.md) F-ADM-05
Perkiraan : M

Berkas yang disentuh:
- `app/Console/Commands/ExportCompetition.php`
- `app/Console/Commands/ImportCompetition.php`

Kriteria selesai:
- [ ] Satu kejuaraan lengkap dapat diekspor menjadi satu berkas JSON beserta seluruh relasinya
- [ ] Berkas tersebut dapat dipulihkan ke instalasi lain
- [ ] Pencadangan basis data berjalan terjadwal sebelum dan sesudah hari lomba
- [ ] Prosedur pemulihan terdokumentasi dan sudah pernah dicoba, bukan hanya ditulis

Uji:
- Feature test export lalu import menghasilkan jumlah baris yang identik pada seluruh tabel terkait

Persyaratan bahwa pemulihan sudah pernah dicoba dituliskan secara sengaja. Pencadangan yang belum pernah diuji pemulihannya belum tentu benar-benar berfungsi, dan hari lomba bukan waktu yang tepat untuk mengetahuinya.

---

### T-12-07 Pemantauan dan penanganan galat

Prasyarat : T-00-01
Acuan : [../README.md](../README.md)
Perkiraan : S

Berkas yang disentuh:
- `bootstrap/app.php`
- `config/logging.php`

Kriteria selesai:
- [ ] Galat produksi tercatat beserta konteks pengguna dan permintaannya
- [ ] Halaman galat menampilkan pesan yang dapat dipahami, bukan jejak tumpukan
- [ ] Job antrean yang gagal tercatat dan dapat dijalankan ulang
- [ ] Log rotasi harian dengan penyimpanan tiga puluh hari
- [ ] Tersedia halaman pemeriksaan kesehatan untuk memantau basis data dan antrean

Uji:
- Feature test halaman pemeriksaan kesehatan mengembalikan status basis data dan antrean

---

### T-12-08 Persiapan rilis produksi

Prasyarat : T-12-04, T-12-06, T-12-07
Acuan : [../README.md](../README.md)
Perkiraan : M

Berkas yang disentuh:
- `README.md`
- `.env.example`
- `docs/deployment.md`

Kriteria selesai:
- [ ] `APP_DEBUG` bernilai salah dan `APP_ENV` bernilai `production` pada contoh konfigurasi produksi
- [ ] Konfigurasi, route, dan view di-cache saat penerapan
- [ ] Pekerja antrean berjalan sebagai layanan yang dipantau
- [ ] Penjadwal terpasang pada cron
- [ ] Prosedur penerapan dan pengembalian versi terdokumentasi
- [ ] Ada daftar periksa hari lomba yang mencakup pencadangan, pengujian jaringan kolam, dan penyiapan cetakan cadangan

Uji:
- Uji coba penerapan penuh ke lingkungan staging

Daftar periksa hari lomba pada kriteria terakhir bukan urusan teknis semata. Sebagian besar kegagalan pada acara semacam ini bukan berasal dari cacat perangkat lunak, melainkan dari jaringan kolam yang tidak sempat diuji dan cetakan cadangan yang tidak disiapkan.
