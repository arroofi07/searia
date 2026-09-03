# T-03 Pendaftaran

Modul tempat atlet dihubungkan dengan nomor lomba. Satu baris pendaftaran mewakili satu atlet pada satu nomor lomba, lengkap dengan catatan waktu unggulan yang nanti dipakai proses seeding.

Acuan: [04-alur-pendaftaran.md](../04-alur-pendaftaran.md), [06-catatan-waktu.md](../06-catatan-waktu.md)

---

### T-03-01 Migrasi dan model pendaftaran

Prasyarat : T-01-04, T-02-05
Acuan : [../08-database.md](../08-database.md) tabel `registrations`
Perkiraan : S

Berkas yang disentuh:
- `database/migrations/*_create_registrations_table.php`
- `app/Models/Registration.php`
- `app/Enums/RegistrationStatus.php`

Kriteria selesai:
- [ ] Indeks unik pada `event_id` dan `athlete_id` yang menegakkan aturan satu atlet satu kali per nomor
- [ ] Indeks gabungan `event_id`, `age_group_id`, `seed_time_ms` yang menjadi tumpuan kueri seeding
- [ ] `seed_time_ms` bertipe integer nullable dengan cast `SwimTime`
- [ ] `age_group_id` disimpan sebagai kolom, bukan dihitung ulang setiap kali dibaca
- [ ] Scope `eligibleForSeeding()` mengembalikan pendaftaran berstatus `verified` yang tagihannya lunas atau belum ditagih

Uji:
- Unit test bahwa pendaftaran kedua untuk pasangan atlet dan nomor yang sama ditolak basis data

Alasan `age_group_id` dibekukan sebagai kolom: bila panitia memperbaiki rentang tahun lahir di tengah masa pendaftaran, peserta yang sudah terdaftar tidak boleh berpindah grup diam-diam. Perpindahan harus menjadi tindakan sadar yang tercatat.

---

### T-03-02 Layanan penentuan kelompok umur

Prasyarat : T-02-04
Acuan : [../03-struktur-lomba.md](../03-struktur-lomba.md)
Perkiraan : S

Berkas yang disentuh:
- `app/Services/AgeGroupResolver.php`

Kriteria selesai:
- [ ] Menerima kejuaraan dan tahun lahir, mengembalikan satu kelompok umur atau `null`
- [ ] Melempar pengecualian bila menemukan lebih dari satu grup yang cocok, karena itu menandakan konfigurasi tumpang tindih yang lolos validasi
- [ ] Hasil pencarian di-cache per kejuaraan agar pemrosesan import ribuan baris tidak menghasilkan ribuan kueri

Uji:
- Unit test tahun lahir tepat di batas bawah dan batas atas tiap grup
- Unit test tahun lahir di luar seluruh rentang mengembalikan `null`

---

### T-03-03 Aturan validasi pendaftaran

Prasyarat : T-03-01, T-03-02, T-00-06
Acuan : [../04-alur-pendaftaran.md](../04-alur-pendaftaran.md) bagian Aturan Validasi
Perkiraan : M

Sembilan aturan V-01 sampai V-09 dikumpulkan dalam satu tempat, lalu dipakai bersama oleh form pendaftaran dan import Excel. Menuliskannya dua kali hampir pasti menghasilkan dua perilaku yang berbeda.

Berkas yang disentuh:
- `app/Services/RegistrationValidator.php`
- `app/Http/Requests/StoreRegistrationRequest.php`

Kriteria selesai:
- [ ] Seluruh aturan V-01 sampai V-09 terimplementasi dengan pesan sesuai dokumen acuan
- [ ] Validator dapat dipanggil untuk satu entri maupun sekumpulan entri sekaligus
- [ ] Pemeriksaan batas jumlah nomor per atlet memperhitungkan entri yang sedang diproses, bukan hanya yang sudah tersimpan
- [ ] Validator mengembalikan daftar kesalahan, tidak berhenti pada kesalahan pertama

Uji:
- Unit test satu kasus untuk tiap aturan V-01 sampai V-09
- Unit test satu entri yang melanggar tiga aturan sekaligus melaporkan ketiganya

---

### T-03-04 Form pendaftaran

Prasyarat : T-03-03
Acuan : [../04-alur-pendaftaran.md](../04-alur-pendaftaran.md) bagian Form Pendaftaran
Perkiraan : L

Alur tiga langkah: pilih atlet, pilih nomor lomba beserta catatan waktu, lalu tinjau dan kirim.

Berkas yang disentuh:
- `app/Http/Controllers/RegistrationController.php`
- `resources/views/registrations/*`

Kriteria selesai:
- [ ] Setelah tahun lahir terisi, kelompok umur hasil pencocokan langsung ditampilkan
- [ ] Daftar nomor lomba tersaring oleh jenis kelamin atlet dan matriks kelayakan grupnya
- [ ] Sisa kuota nomor per atlet ditampilkan dan diperbarui saat kotak centang berubah
- [ ] Kolom catatan waktu menampilkan hasil penguraian dalam format baku di sebelah masukan
- [ ] Kolom catatan waktu boleh dikosongkan dan ditandai sebagai NT
- [ ] Seluruh entri dalam satu pengiriman tersimpan dalam satu transaksi
- [ ] Pengiriman ganda akibat tombol ditekan dua kali tidak menghasilkan pendaftaran ganda

Uji:
- Feature test alur lengkap tiga langkah menghasilkan jumlah baris `registrations` yang benar
- Feature test bahwa nomor lomba di luar kelayakan grup ditolak walaupun dikirim langsung lewat permintaan HTTP

Penyaringan di antarmuka saja tidak cukup. Permintaan HTTP dapat disusun manual, sehingga aturan yang sama harus ditegakkan lagi di sisi server.

---

### T-03-05 Ringkasan dan pengelolaan entri klub

Prasyarat : T-03-04
Acuan : [../02-fitur.md](../02-fitur.md) F-PEL-06, F-PEL-07
Perkiraan : M

Berkas yang disentuh:
- `app/Http/Controllers/Coach/RegistrationSummaryController.php`
- `resources/views/coach/registrations/*`

Kriteria selesai:
- [ ] Menampilkan seluruh entri klub yang dikelompokkan per atlet
- [ ] Menampilkan status tiap entri beserta alasan penolakan bila ada
- [ ] Menampilkan total biaya berjalan
- [ ] Entri dapat diubah atau dibatalkan selama kejuaraan berstatus `registration`
- [ ] Setelah status `closed`, seluruh tombol ubah dan batal nonaktif disertai penjelasan

Uji:
- Feature test perubahan entri pada kejuaraan berstatus `closed` menerima 403

---

### T-03-06 Antrean verifikasi panitia

Prasyarat : T-03-04
Acuan : [../04-alur-pendaftaran.md](../04-alur-pendaftaran.md) bagian Verifikasi oleh Panitia
Perkiraan : M

Berkas yang disentuh:
- `app/Http/Controllers/Admin/RegistrationVerificationController.php`
- `resources/views/admin/registrations/*`

Kriteria selesai:
- [ ] Daftar pendaftaran berstatus `pending` dapat disaring per klub, per nomor lomba, dan per kelompok umur
- [ ] Panitia dapat menyetujui atau menolak satu entri maupun banyak entri sekaligus
- [ ] Penolakan wajib disertai alasan yang terlihat oleh pendaftar
- [ ] Penyetujuan sekaligus atas ratusan entri berjalan dalam satu transaksi
- [ ] Setiap perubahan status mencatat pelaku dan waktunya

Uji:
- Feature test penyetujuan sekaligus atas seratus entri mengubah seluruh statusnya
- Feature test penolakan tanpa alasan ditolak validasi

---

### T-03-07 Pemberitahuan perubahan status

Prasyarat : T-03-06
Acuan : [../04-alur-pendaftaran.md](../04-alur-pendaftaran.md)
Perkiraan : S

Berkas yang disentuh:
- `app/Notifications/RegistrationStatusChanged.php`
- `app/Listeners/*`

Kriteria selesai:
- [ ] Pendaftar menerima pemberitahuan saat entri disetujui atau ditolak
- [ ] Penolakan memuat alasan dan tautan langsung ke layar perbaikan
- [ ] Penyetujuan sekaligus menghasilkan satu pemberitahuan ringkasan per klub, bukan satu per entri
- [ ] Pengiriman berjalan lewat antrean agar tidak memperlambat permintaan HTTP

Uji:
- Feature test dengan `Notification::fake()` memastikan satu ringkasan terkirim per klub

---

### T-03-08 Pengusulan seed time dari hasil kejuaraan sebelumnya

Prasyarat : T-03-04, T-09-01
Acuan : [../06-catatan-waktu.md](../06-catatan-waktu.md) bagian Perbandingan Seed Time dan Hasil
Perkiraan : M

Atlet yang pernah berlomba di sistem ini sudah memiliki catatan waktu resmi. Mengetik ulang angka yang sudah ada hanya menambah peluang salah ketik.

Berkas yang disentuh:
- `app/Services/SeedTimeSuggester.php`

Kriteria selesai:
- [ ] Saat pendaftar memilih nomor lomba, sistem mengusulkan waktu terbaik atlet pada nomor setara di kejuaraan sebelumnya
- [ ] Usulan hanya diambil dari kejuaraan berjenis Resmi, bukan Fun
- [ ] Usulan hanya diambil dari hasil berstatus `ok`, bukan DNS, DNF, atau DSQ
- [ ] Usulan hanya diambil dari kejuaraan dengan panjang kolam yang sama
- [ ] Pendaftar dapat menimpa usulan tersebut

Uji:
- Unit test bahwa hasil dari kejuaraan Fun tidak diusulkan
- Unit test bahwa hasil dari kolam 25 meter tidak diusulkan untuk kejuaraan di kolam 50 meter
