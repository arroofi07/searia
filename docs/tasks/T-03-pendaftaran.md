# T-03 Pendaftaran

Modul tempat atlet dihubungkan dengan nomor lomba. Satu baris pendaftaran mewakili satu atlet pada satu nomor lomba, lengkap dengan catatan waktu unggulan yang nanti dipakai proses seeding.

Acuan: [04-alur-pendaftaran.md](../04-alur-pendaftaran.md), [06-catatan-waktu.md](../06-catatan-waktu.md)

**Status modul: selesai.** Delapan task di bawah sudah diimplementasikan beserta tes Pest-nya.

| Task | Judul | Status |
| --- | --- | --- |
| T-03-01 | Migrasi dan model pendaftaran | Selesai |
| T-03-02 | Layanan penentuan kelompok umur | Selesai |
| T-03-03 | Aturan validasi pendaftaran | Selesai |
| T-03-04 | Form pendaftaran | Selesai |
| T-03-05 | Ringkasan dan pengelolaan entri klub | Selesai |
| T-03-06 | Antrean verifikasi panitia | Selesai |
| T-03-07 | Pemberitahuan perubahan status | Selesai |
| T-03-08 | Pengusulan seed time dari hasil kejuaraan sebelumnya | Selesai |

---

### T-03-01 Migrasi dan model pendaftaran

Prasyarat : T-01-04, T-02-05
Acuan : [../08-database.md](../08-database.md) tabel `registrations`
Perkiraan : S
Status : Selesai

Berkas yang disentuh:
- `database/migrations/2026_01_01_000010_create_registrations_table.php`
- `database/migrations/2026_01_01_000015_create_invoices_table.php`
- `app/Models/Registration.php`
- `app/Models/Invoice.php`
- `app/Enums/RegistrationStatus.php`
- `app/Enums/InvoiceStatus.php`
- `app/Casts/SwimTimeCast.php`
- `database/factories/RegistrationFactory.php`
- `database/factories/InvoiceFactory.php`
- `tests/Unit/RegistrationConstraintTest.php`

Kriteria selesai:
- [x] Indeks unik pada `event_id` dan `athlete_id` yang menegakkan aturan satu atlet satu kali per nomor
- [x] Indeks gabungan `event_id`, `age_group_id`, `seed_time_ms` yang menjadi tumpuan kueri seeding
- [x] `seed_time_ms` bertipe integer nullable dengan cast `SwimTime`
- [x] `age_group_id` disimpan sebagai kolom, bukan dihitung ulang setiap kali dibaca
- [x] Scope `eligibleForSeeding()` mengembalikan pendaftaran berstatus `verified` yang tagihannya lunas atau belum ditagih

Uji:
- [x] Unit test bahwa pendaftaran kedua untuk pasangan atlet dan nomor yang sama ditolak basis data

Alasan `age_group_id` dibekukan sebagai kolom: bila panitia memperbaiki rentang tahun lahir di tengah masa pendaftaran, peserta yang sudah terdaftar tidak boleh berpindah grup diam-diam. Perpindahan harus menjadi tindakan sadar yang tercatat.

Catatan implementasi: tabel `invoices` ikut dibuat agar scope `eligibleForSeeding()` dapat membedakan entri yang belum ditagih (`invoice_id` kosong) dan entri yang tagihannya belum lunas. UI tagihan tetap menjadi T-05.

---

### T-03-02 Layanan penentuan kelompok umur

Prasyarat : T-02-04
Acuan : [../03-struktur-lomba.md](../03-struktur-lomba.md)
Perkiraan : S
Status : Selesai

Berkas yang disentuh:
- `app/Services/AgeGroupResolver.php`
- `app/Exceptions/OverlappingAgeGroupsException.php`
- `tests/Unit/AgeGroupResolverTest.php`

Kriteria selesai:
- [x] Menerima kejuaraan dan tahun lahir, mengembalikan satu kelompok umur atau `null`
- [x] Melempar pengecualian bila menemukan lebih dari satu grup yang cocok, karena itu menandakan konfigurasi tumpang tindih yang lolos validasi
- [x] Hasil pencarian di-cache per kejuaraan agar pemrosesan import ribuan baris tidak menghasilkan ribuan kueri

Uji:
- [x] Unit test tahun lahir tepat di batas bawah dan batas atas tiap grup
- [x] Unit test tahun lahir di luar seluruh rentang mengembalikan `null`

---

### T-03-03 Aturan validasi pendaftaran

Prasyarat : T-03-01, T-03-02, T-00-06
Acuan : [../04-alur-pendaftaran.md](../04-alur-pendaftaran.md) bagian Aturan Validasi
Perkiraan : M
Status : Selesai

Sembilan aturan V-01 sampai V-09 dikumpulkan dalam satu tempat, lalu dipakai bersama oleh form pendaftaran dan import Excel. Menuliskannya dua kali hampir pasti menghasilkan dua perilaku yang berbeda.

Berkas yang disentuh:
- `app/Services/RegistrationValidator.php`
- `app/Services/RegistrationDraft.php`
- `app/Http/Requests/StoreRegistrationRequest.php`
- `tests/Unit/RegistrationValidatorTest.php`

Kriteria selesai:
- [x] Seluruh aturan V-01 sampai V-09 terimplementasi dengan pesan sesuai dokumen acuan
- [x] Validator dapat dipanggil untuk satu entri maupun sekumpulan entri sekaligus
- [x] Pemeriksaan batas jumlah nomor per atlet memperhitungkan entri yang sedang diproses, bukan hanya yang sudah tersimpan
- [x] Validator mengembalikan daftar kesalahan, tidak berhenti pada kesalahan pertama

Uji:
- [x] Unit test satu kasus untuk tiap aturan V-01 sampai V-09
- [x] Unit test satu entri yang melanggar tiga aturan sekaligus melaporkan ketiganya

---

### T-03-04 Form pendaftaran

Prasyarat : T-03-03
Acuan : [../04-alur-pendaftaran.md](../04-alur-pendaftaran.md) bagian Form Pendaftaran
Perkiraan : L
Status : Selesai

Alur tiga langkah: pilih atlet, pilih nomor lomba beserta catatan waktu, lalu tinjau dan kirim.

Berkas yang disentuh:
- `app/Http/Controllers/RegistrationController.php`
- `app/Http/Requests/StoreRegistrationAthleteRequest.php`
- `app/Http/Requests/StoreRegistrationEventsRequest.php`
- `app/Policies/RegistrationPolicy.php`
- `resources/views/registrations/index.blade.php`
- `resources/views/registrations/create.blade.php`
- `resources/views/registrations/events.blade.php`
- `resources/views/registrations/review.blade.php`
- `routes/web.php`
- `tests/Feature/RegistrationFormTest.php`

Kriteria selesai:
- [x] Setelah tahun lahir terisi, kelompok umur hasil pencocokan langsung ditampilkan
- [x] Daftar nomor lomba tersaring oleh jenis kelamin atlet dan matriks kelayakan grupnya
- [x] Sisa kuota nomor per atlet ditampilkan dan diperbarui saat kotak centang berubah
- [x] Kolom catatan waktu menampilkan hasil penguraian dalam format baku di sebelah masukan
- [x] Kolom catatan waktu boleh dikosongkan dan ditandai sebagai NT
- [x] Seluruh entri dalam satu pengiriman tersimpan dalam satu transaksi
- [x] Pengiriman ganda akibat tombol ditekan dua kali tidak menghasilkan pendaftaran ganda

Uji:
- [x] Feature test alur lengkap tiga langkah menghasilkan jumlah baris `registrations` yang benar
- [x] Feature test bahwa nomor lomba di luar kelayakan grup ditolak walaupun dikirim langsung lewat permintaan HTTP

Penyaringan di antarmuka saja tidak cukup. Permintaan HTTP dapat disusun manual, sehingga aturan yang sama harus ditegakkan lagi di sisi server.

---

### T-03-05 Ringkasan dan pengelolaan entri klub

Prasyarat : T-03-04
Acuan : [../02-fitur.md](../02-fitur.md) F-PEL-06, F-PEL-07
Perkiraan : M
Status : Selesai

Berkas yang disentuh:
- `app/Http/Controllers/Coach/RegistrationSummaryController.php`
- `app/Http/Requests/UpdateRegistrationRequest.php`
- `resources/views/coach/registrations/index.blade.php`
- `tests/Feature/RegistrationSummaryTest.php`

Kriteria selesai:
- [x] Menampilkan seluruh entri klub yang dikelompokkan per atlet
- [x] Menampilkan status tiap entri beserta alasan penolakan bila ada
- [x] Menampilkan total biaya berjalan
- [x] Entri dapat diubah atau dibatalkan selama kejuaraan berstatus `registration`
- [x] Setelah status `closed`, seluruh tombol ubah dan batal nonaktif disertai penjelasan

Uji:
- [x] Feature test perubahan entri pada kejuaraan berstatus `closed` menerima 403

---

### T-03-06 Antrean verifikasi panitia

Prasyarat : T-03-04
Acuan : [../04-alur-pendaftaran.md](../04-alur-pendaftaran.md) bagian Verifikasi oleh Panitia
Perkiraan : M
Status : Selesai

Berkas yang disentuh:
- `app/Http/Controllers/Admin/RegistrationVerificationController.php`
- `app/Http/Requests/RejectRegistrationRequest.php`
- `app/Http/Requests/BulkVerifyRegistrationRequest.php`
- `resources/views/admin/registrations/index.blade.php`
- `tests/Feature/RegistrationVerificationTest.php`

Kriteria selesai:
- [x] Daftar pendaftaran berstatus `pending` dapat disaring per klub, per nomor lomba, dan per kelompok umur
- [x] Panitia dapat menyetujui atau menolak satu entri maupun banyak entri sekaligus
- [x] Penolakan wajib disertai alasan yang terlihat oleh pendaftar
- [x] Penyetujuan sekaligus atas ratusan entri berjalan dalam satu transaksi
- [x] Setiap perubahan status mencatat pelaku dan waktunya

Uji:
- [x] Feature test penyetujuan sekaligus atas seratus entri mengubah seluruh statusnya
- [x] Feature test penolakan tanpa alasan ditolak validasi

---

### T-03-07 Pemberitahuan perubahan status

Prasyarat : T-03-06
Acuan : [../04-alur-pendaftaran.md](../04-alur-pendaftaran.md)
Perkiraan : S
Status : Selesai

Berkas yang disentuh:
- `app/Events/RegistrationsStatusUpdated.php`
- `app/Listeners/SendRegistrationStatusNotifications.php`
- `app/Notifications/RegistrationStatusChanged.php`
- `tests/Feature/RegistrationVerificationTest.php`

Kriteria selesai:
- [x] Pendaftar menerima pemberitahuan saat entri disetujui atau ditolak
- [x] Penolakan memuat alasan dan tautan langsung ke layar perbaikan
- [x] Penyetujuan sekaligus menghasilkan satu pemberitahuan ringkasan per klub, bukan satu per entri
- [x] Pengiriman berjalan lewat antrean agar tidak memperlambat permintaan HTTP

Uji:
- [x] Feature test dengan `Notification::fake()` memastikan satu ringkasan terkirim per klub

---

### T-03-08 Pengusulan seed time dari hasil kejuaraan sebelumnya

Prasyarat : T-03-04, T-09-01
Acuan : [../06-catatan-waktu.md](../06-catatan-waktu.md) bagian Perbandingan Seed Time dan Hasil
Perkiraan : M
Status : Selesai

Atlet yang pernah berlomba di sistem ini sudah memiliki catatan waktu resmi. Mengetik ulang angka yang sudah ada hanya menambah peluang salah ketik.

Berkas yang disentuh:
- `app/Services/SeedTimeSuggester.php`
- `tests/Unit/SeedTimeSuggesterTest.php`

Kriteria selesai:
- [x] Saat pendaftar memilih nomor lomba, sistem mengusulkan waktu terbaik atlet pada nomor setara di kejuaraan sebelumnya
- [x] Usulan hanya diambil dari kejuaraan berjenis Resmi, bukan Fun
- [x] Usulan hanya diambil dari hasil berstatus `ok`, bukan DNS, DNF, atau DSQ
- [x] Usulan hanya diambil dari kejuaraan dengan panjang kolam yang sama
- [x] Pendaftar dapat menimpa usulan tersebut

Uji:
- [x] Unit test bahwa hasil dari kejuaraan Fun tidak diusulkan
- [x] Unit test bahwa hasil dari kolam 25 meter tidak diusulkan untuk kejuaraan di kolam 50 meter

Catatan implementasi: pengusul memakai tabel `heats`, `heat_lanes`, dan `results` yang sudah ada sejak T-02. UI input hasil tetap menjadi T-08 / T-09.
