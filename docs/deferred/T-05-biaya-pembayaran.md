# T-05 Biaya dan Pembayaran

Tagihan punya dua pemilik yang saling eksklusif, sesuai jalur masuk datanya:

| Jalur | Pemilik tagihan | Kapan terbit |
| --- | --- | --- |
| Form pendaftaran publik | `invoices.submission_id` | Otomatis saat form dikirim |
| Import Excel oleh panitia | `invoices.club_id` | Manual oleh panitia |

Pemisahan ini mengikuti siapa yang sebenarnya mentransfer. Satu klub yang mengirim berkas Excel berisi dua puluh atlet memang membayar sekaligus, sehingga satu tagihan per klub lebih masuk akal. Sebaliknya, orang tua yang mendaftarkan satu anak lewat form publik tidak punya hubungan apa pun dengan pendaftar lain di klub yang sama, sehingga menggabungkan tagihan mereka justru membuat pencocokan transfer mustahil.

Acuan: [04-alur-pendaftaran.md](../04-alur-pendaftaran.md) bagian Biaya dan Pembayaran

---

### T-05-01 Migrasi dan model tagihan

Prasyarat : T-03-01
Acuan : [../08-database.md](../08-database.md) tabel `invoices`
Perkiraan : S

Berkas yang disentuh:
- `database/migrations/*_create_invoices_table.php`
- `database/migrations/*_add_invoice_id_to_registrations_table.php`
- `app/Models/Invoice.php`
- `app/Enums/InvoiceStatus.php`

Kriteria selesai:
- [x] Indeks unik pada `submission_id` sehingga satu pengiriman hanya punya satu tagihan
- [x] Satu klub hanya punya satu tagihan per kejuaraan, ditegakkan di `IssueInvoice` karena banyak tagihan tidak punya `club_id`
- [x] `invoice_number` unik dan mengikuti pola yang dapat dibaca manusia, misalnya `INV-2026-0007`
- [x] Nominal disimpan sebagai integer rupiah, bukan tipe pecahan
- [x] Relasi `hasMany(Registration)` menghubungkan tagihan dengan entri yang ditagih

Uji:
- Unit test bahwa tagihan kedua untuk pengiriman yang sama ditolak basis data

Nominal disimpan sebagai integer karena tipe pecahan biner tidak pernah tepat untuk uang. Rupiah tidak mengenal sen, sehingga integer rupiah sudah memadai tanpa perlu tipe desimal.

---

### T-05-02 Perhitungan biaya

Prasyarat : T-05-01, T-02-01
Acuan : [../04-alur-pendaftaran.md](../04-alur-pendaftaran.md)
Perkiraan : S

Berkas yang disentuh:
- `app/Services/InvoiceCalculator.php`

Kriteria selesai:
- [ ] Total dihitung dari jumlah entri terverifikasi dikali biaya per nomor
- [ ] Entri yang didaftarkan setelah batas normal dikenakan biaya keterlambatan
- [ ] Entri berstatus `rejected` dan `withdrawn` tidak ikut dihitung
- [ ] Perhitungan menghasilkan rincian per entri, bukan hanya angka total, agar tagihan dapat diperiksa pendaftar

Uji:
- Unit test kombinasi entri normal dan terlambat
- Unit test bahwa entri yang ditolak tidak menambah total

---

### T-05-03 Penerbitan tagihan

Prasyarat : T-05-02
Acuan : [../02-fitur.md](../02-fitur.md) F-PAN-09
Perkiraan : M

Berkas yang disentuh:
- `app/Actions/IssueInvoice.php`
- `app/Http/Controllers/Admin/InvoiceController.php`
- `resources/views/admin/invoices/*`

Kriteria selesai:
- [x] Tagihan per pengiriman terbit otomatis dalam transaksi yang sama dengan entrinya
- [ ] Panitia dapat menerbitkan tagihan untuk satu klub atau seluruh klub sekaligus, terbatas pada entri hasil import
- [ ] Penerbitan ulang setelah ada perubahan entri memperbarui nominal dan rincian tagihan yang sama
- [ ] Penerbitan ulang atas tagihan berstatus lunas ditolak
- [ ] Tagihan memuat batas waktu pembayaran
- [ ] Tagihan dapat diunduh sebagai PDF

Uji:
- Feature test penerbitan sekaligus menghasilkan satu tagihan per klub
- Feature test penerbitan ulang tagihan lunas ditolak

---

### T-05-04 Halaman bukti pendaftaran dan instruksi pembayaran

Prasyarat : T-05-03
Acuan : [../02-fitur.md](../02-fitur.md) F-DAF-06
Perkiraan : S

Berkas yang disentuh:
- `app/Http/Controllers/Public/RegistrationController.php` metode `done`
- `resources/views/register/done.blade.php`
- `app/Services/Invoice/InvoicePdf.php`

Kriteria selesai:
- [x] Halaman menampilkan kode pendaftaran, rincian entri, nominal, dan batas waktu pembayaran
- [x] Instruksi transfer memuat nomor rekening panitia
- [x] Tagihan dapat diunduh sebagai PDF
- [x] Halaman hanya terbuka untuk peramban yang baru saja mengirim form

Uji:
- Feature test kode pendaftaran orang lain menghasilkan 404 pada sesi berbeda

Tidak ada unggah bukti transfer di sini, dan itu keputusan yang disengaja. Menerima berkas dari pengunjung anonim berarti menyediakan tempat penyimpanan yang bisa diisi siapa saja tanpa jejak akun, dengan imbalan yang kecil: panitia tetap harus membuka mutasi rekening untuk memastikan uangnya benar-benar masuk. Karena pemeriksaan mutasi tidak bisa dihindari, bukti transfer hanya menambah langkah tanpa menambah kepastian.

Konsekuensinya, halaman ini adalah satu-satunya kesempatan pendaftar melihat nominal tagihannya di layar. Karena itu isinya juga dikirim lewat email bila alamatnya dicantumkan.

---

### T-05-05 Verifikasi pembayaran

Prasyarat : T-05-04
Acuan : [../04-alur-pendaftaran.md](../04-alur-pendaftaran.md)
Perkiraan : M

Berkas yang disentuh:
- `app/Http/Controllers/Admin/PaymentVerificationController.php`

Kriteria selesai:
- [x] Panitia melihat nominal yang harus dicocokkan dengan mutasi rekening
- [x] Penandaan lunas membuat seluruh entri pada tagihan tersebut memenuhi syarat seeding
- [x] Pembatalan status lunas wajib disertai alasan dan mengembalikan status ke `unpaid`
- [x] Verifikasi mencatat pelaku dan waktunya
- [x] Pendaftar menerima email atas hasil verifikasi bila alamatnya dicantumkan

Uji:
- Feature test penandaan lunas membuat `Registration::eligibleForSeeding()` menyertakan entri tagihan tersebut
- Feature test email hasil verifikasi terkirim ke alamat pendaftar

---

### T-05-06 Pembatalan otomatis entri belum lunas

Prasyarat : T-05-05
Acuan : [../04-alur-pendaftaran.md](../04-alur-pendaftaran.md)
Perkiraan : S

Berkas yang disentuh:
- `app/Console/Commands/ExpireUnpaidRegistrations.php`
- `routes/console.php`

Kriteria selesai:
- [ ] Perintah terjadwal mengubah entri pada tagihan yang lewat batas waktu menjadi `withdrawn`
- [x] Pendaftar menerima email pengingat sebelum batas waktu, bukan hanya sesudahnya
- [ ] Panitia dapat mengembalikan status secara manual untuk keterlambatan yang dapat dimaklumi
- [ ] Perintah tidak menyentuh kejuaraan yang sudah berstatus `seeded` atau setelahnya

Uji:
- Feature test dengan waktu yang dimanipulasi memastikan entri lewat tempo berubah status
- Feature test bahwa kejuaraan berstatus `seeded` tidak terpengaruh

Perlindungan pada kriteria terakhir penting karena membatalkan entri setelah seeding dikunci akan mengosongkan lintasan pada buku acara yang mungkin sudah tercetak.
