# T-05 Biaya dan Pembayaran

Tagihan diterbitkan per klub, bukan per entri, karena pada praktiknya satu klub mentransfer sekaligus untuk semua atletnya. Pendaftar perorangan diperlakukan sebagai klub berisi satu orang agar alurnya tetap sama.

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
- [ ] Indeks unik pada `competition_id` dan `club_id` sehingga satu klub hanya punya satu tagihan per kejuaraan
- [ ] `invoice_number` unik dan mengikuti pola yang dapat dibaca manusia, misalnya `INV-2026-0007`
- [ ] Nominal disimpan sebagai integer rupiah, bukan tipe pecahan
- [ ] Relasi `hasMany(Registration)` menghubungkan tagihan dengan entri yang ditagih

Uji:
- Unit test bahwa tagihan kedua untuk klub dan kejuaraan yang sama ditolak

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
- [ ] Perhitungan menghasilkan rincian per entri, bukan hanya angka total, agar tagihan dapat diperiksa pelatih

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
- [ ] Panitia dapat menerbitkan tagihan untuk satu klub atau seluruh klub sekaligus
- [ ] Penerbitan ulang setelah ada perubahan entri memperbarui nominal dan rincian tagihan yang sama
- [ ] Penerbitan ulang atas tagihan berstatus lunas ditolak
- [ ] Tagihan memuat batas waktu pembayaran
- [ ] Tagihan dapat diunduh sebagai PDF

Uji:
- Feature test penerbitan sekaligus menghasilkan satu tagihan per klub
- Feature test penerbitan ulang tagihan lunas ditolak

---

### T-05-04 Unggah bukti pembayaran

Prasyarat : T-05-03
Acuan : [../02-fitur.md](../02-fitur.md) F-PEL-08
Perkiraan : S

Berkas yang disentuh:
- `app/Http/Controllers/Coach/PaymentProofController.php`
- `resources/views/coach/invoices/*`

Kriteria selesai:
- [ ] Pelatih melihat tagihan klubnya beserta rinciannya
- [ ] Bukti transfer dapat diunggah dalam format JPG, PNG, atau PDF hingga lima megabita
- [ ] Unggahan mengubah status tagihan menjadi `waiting_verification`
- [ ] Bukti dapat diganti selama status belum lunas
- [ ] Berkas bukti tidak dapat diakses lewat URL langsung tanpa otorisasi

Uji:
- Feature test pelatih klub A menerima 403 saat mengakses berkas bukti klub B

Berkas bukti transfer memuat nomor rekening dan nama pemilik, sehingga tidak boleh diletakkan pada disk publik. Aksesnya harus melewati controller yang memeriksa Policy.

---

### T-05-05 Verifikasi pembayaran

Prasyarat : T-05-04
Acuan : [../04-alur-pendaftaran.md](../04-alur-pendaftaran.md)
Perkiraan : M

Berkas yang disentuh:
- `app/Http/Controllers/Admin/PaymentVerificationController.php`

Kriteria selesai:
- [ ] Panitia melihat bukti dan nominal tagihan berdampingan
- [ ] Penandaan lunas membuat seluruh entri pada tagihan tersebut memenuhi syarat seeding
- [ ] Penolakan wajib disertai alasan dan mengembalikan status ke `unpaid`
- [ ] Verifikasi mencatat pelaku dan waktunya
- [ ] Pelatih menerima pemberitahuan atas hasil verifikasi

Uji:
- Feature test penandaan lunas membuat `Registration::eligibleForSeeding()` menyertakan entri klub tersebut

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
- [ ] Klub menerima pemberitahuan sebelum batas waktu, bukan hanya sesudahnya
- [ ] Panitia dapat mengembalikan status secara manual untuk keterlambatan yang dapat dimaklumi
- [ ] Perintah tidak menyentuh kejuaraan yang sudah berstatus `seeded` atau setelahnya

Uji:
- Feature test dengan waktu yang dimanipulasi memastikan entri lewat tempo berubah status
- Feature test bahwa kejuaraan berstatus `seeded` tidak terpengaruh

Perlindungan pada kriteria terakhir penting karena membatalkan entri setelah seeding dikunci akan mengosongkan lintasan pada buku acara yang mungkin sudah tercetak.
