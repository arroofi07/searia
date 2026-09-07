# T-04 Import Excel

Modul yang melayani kebiasaan lama: klub mengirim daftar atlet dalam berkas Excel, panitia memasukkannya ke sistem. Alih-alih melawan kebiasaan itu, sistem menyediakan template baku dan memvalidasinya otomatis.

Acuan: [07-import-excel.md](../07-import-excel.md)

**Status modul: selesai.** Delapan task di bawah sudah diimplementasikan beserta tes Pest-nya.

| Task | Judul | Status |
| --- | --- | --- |
| T-04-01 | Pasang dan konfigurasi paket Excel | Selesai |
| T-04-02 | Migrasi dan model batch import | Selesai |
| T-04-03 | Pembuatan template Excel | Selesai |
| T-04-04 | Pembacaan dan pemetaan berkas | Selesai |
| T-04-05 | Validasi per baris | Selesai |
| T-04-06 | Layar unggah dan pratinjau | Selesai |
| T-04-07 | Commit ke basis data | Selesai |
| T-04-08 | Pemrosesan latar belakang untuk berkas besar | Selesai |

---

### T-04-01 Pasang dan konfigurasi paket Excel

Prasyarat : T-00-01
Acuan : [../README.md](../README.md)
Perkiraan : S
Status : Selesai

Berkas yang disentuh:
- `composer.json`
- `config/excel.php`
- `config/searia.php`

Kriteria selesai:
- [x] Paket `maatwebsite/excel` terpasang dan konfigurasinya diterbitkan
- [x] Batas memori dan waktu eksekusi disetel untuk berkas hingga dua ribu baris
- [x] Pembacaan berkas memakai mode chunk, bukan memuat seluruh berkas ke memori

Uji:
- [x] Unit test validasi dua ribu baris selesai di bawah tiga puluh detik

---

### T-04-02 Migrasi dan model batch import

Prasyarat : T-04-01, T-03-01
Acuan : [../08-database.md](../08-database.md) tabel `import_batches`
Perkiraan : S
Status : Selesai

Berkas yang disentuh:
- `database/migrations/2026_01_01_000016_create_import_batches_table.php`
- `app/Models/ImportBatch.php`
- `app/Enums/ImportStatus.php`
- `app/Console/Commands/PruneImportFiles.php`
- `bootstrap/app.php`
- `database/factories/ImportBatchFactory.php`
- `tests/Unit/ParticipantFileReaderTest.php`

Kriteria selesai:
- [x] Kolom `errors` bertipe jsonb menampung rincian kesalahan per baris
- [x] Relasi `hasMany(Registration)` memungkinkan satu batch dibatalkan seluruhnya
- [x] Berkas asal tersimpan pada disk `local` dengan penamaan yang tidak menimpa berkas lain
- [x] Tersedia perintah terjadwal yang menghapus berkas asal setelah sembilan puluh hari

Uji:
- [x] Unit test pembatalan batch menghapus seluruh pendaftaran yang berasal darinya

---

### T-04-03 Pembuatan template Excel

Prasyarat : T-04-01, T-02-06
Acuan : [../07-import-excel.md](../07-import-excel.md) bagian Template
Perkiraan : M
Status : Selesai

Template bukan berkas statis. Isinya menyesuaikan kejuaraan yang sedang dibuka, sehingga pengisi selalu melihat daftar nomor acara yang benar.

Berkas yang disentuh:
- `app/Exports/ParticipantTemplateExport.php`
- `app/Exports/Sheets/PesertaSheet.php`
- `app/Exports/Sheets/NomorLombaSheet.php`
- `app/Exports/Sheets/PetunjukSheet.php`
- `app/Http/Controllers/Admin/ImportTemplateController.php`
- `tests/Feature/ImportExcelTest.php`

Kriteria selesai:
- [x] Lembar `PESERTA` memuat delapan kolom sesuai dokumen acuan beserta satu baris contoh
- [x] Lembar `NOMOR LOMBA` memuat seluruh nomor acara kejuaraan tersebut beserta kelompok umur yang boleh mengikutinya
- [x] Lembar `PETUNJUK` memuat rentang tahun lahir tiap grup, format waktu yang diterima, dan batas nomor per atlet
- [x] Lembar rujukan terkunci agar tidak tersunting tanpa sengaja
- [x] Kolom `TAHUN LAHIR` dan `KODE ACARA` diformat sebagai teks agar Excel tidak mengubahnya menjadi tanggal

Uji:
- [x] Feature test unduhan template menghasilkan berkas dengan tiga lembar bernama sesuai ketentuan

Pemformatan kolom sebagai teks bukan hal sepele. Excel gemar menafsirkan angka sebagai tanggal, dan `2016` yang berubah bentuk akan menggagalkan seluruh baris tanpa penyebab yang jelas bagi pengisi.

---

### T-04-04 Pembacaan dan pemetaan berkas

Prasyarat : T-04-03
Acuan : [../07-import-excel.md](../07-import-excel.md)
Perkiraan : M
Status : Selesai

Berkas yang disentuh:
- `app/Imports/ParticipantImport.php`
- `app/Imports/ParticipantSheetImport.php`
- `app/DataTransferObjects/ParticipantRow.php`
- `app/Services/Import/ImportHeaders.php`
- `app/Services/Import/ParticipantFileReader.php`
- `tests/Unit/ParticipantFileReaderTest.php`

Kriteria selesai:
- [x] Menerima format `.xlsx` dan `.csv`
- [x] Judul kolom dikenali tanpa memperhatikan besar kecil huruf dan spasi berlebih
- [x] Berkas yang kekurangan kolom wajib ditolak sejak awal disertai daftar kolom yang hilang
- [x] Baris kosong di tengah berkas dilewati, bukan dianggap kesalahan
- [x] Nomor baris asli pada Excel tersimpan agar pesan kesalahan dapat menunjuk lokasi yang tepat
- [x] Berkas melebihi lima megabita atau dua ribu baris ditolak

Uji:
- [x] Unit test pembacaan berkas dengan judul kolom bervariasi
- [x] Unit test berkas tanpa kolom `KODE ACARA` ditolak dengan pesan yang menyebut kolom tersebut

---

### T-04-05 Validasi per baris

Prasyarat : T-04-04, T-03-03
Acuan : [../07-import-excel.md](../07-import-excel.md) bagian Aturan Validasi
Perkiraan : L
Status : Selesai

Tiga belas kesalahan E-01 sampai E-13 dan lima peringatan W-01 sampai W-05. Validator memakai kembali `RegistrationValidator` dari T-03-03 agar aturannya identik dengan form pendaftaran.

Berkas yang disentuh:
- `app/Services/Import/RowValidator.php`
- `app/Services/Import/ImportValidationResult.php`
- `app/Services/Import/ValidatedImportRow.php`
- `tests/Unit/ImportRowValidatorTest.php`

Kriteria selesai:
- [x] Seluruh kode E-01 sampai E-13 terimplementasi dengan pesan sesuai dokumen acuan
- [x] Seluruh kode W-01 sampai W-05 terimplementasi sebagai peringatan yang tidak menggagalkan baris
- [x] Satu baris melaporkan seluruh kesalahannya sekaligus, bukan berhenti pada yang pertama
- [x] E-10 mendeteksi duplikat di dalam berkas yang sama, E-11 mendeteksi bentrok dengan data yang sudah tersimpan
- [x] E-12 menghitung gabungan baris dalam berkas dan pendaftaran yang sudah ada
- [x] Validasi dua ribu baris selesai di bawah tiga puluh detik

Uji:
- [x] Unit test satu kasus untuk tiap kode E dan W
- [x] Uji kinerja atas berkas dua ribu baris

Persyaratan melaporkan seluruh kesalahan sekaligus terasa sepele tetapi sangat menentukan. Validator yang berhenti pada kesalahan pertama memaksa pengisi mengunggah berulang kali untuk menemukan masalah satu per satu.

---

### T-04-06 Layar unggah dan pratinjau

Prasyarat : T-04-05
Acuan : [../07-import-excel.md](../07-import-excel.md) bagian Layar Pratinjau
Perkiraan : L
Status : Selesai

Berkas yang disentuh:
- `app/Http/Controllers/Admin/ImportController.php`
- `app/Http/Requests/StoreImportRequest.php`
- `app/Http/Requests/UpdateImportRowRequest.php`
- `resources/views/admin/imports/index.blade.php`
- `resources/views/admin/imports/show.blade.php`
- `app/Exports/ImportErrorReportExport.php`
- `tests/Feature/ImportExcelTest.php`

Kriteria selesai:
- [x] Ringkasan menampilkan jumlah baris dibaca, valid, bermasalah, dan berperingatan
- [x] Tabel menampilkan baris bermasalah beserta kode dan pesan kesalahannya
- [x] Baris bermasalah dapat disunting langsung di layar dan divalidasi ulang seketika
- [x] Peringatan kemiripan nama klub menawarkan pemetaan ke klub yang sudah ada
- [x] Peringatan kemiripan nama atlet menawarkan penggunaan atlet yang sudah ada
- [x] Tersedia tombol import hanya baris valid, dengan jumlah baris yang akan diproses tertera jelas
- [x] Tersedia tombol unduh laporan kesalahan dalam bentuk Excel

Uji:
- [x] Feature test unggah berkas berisi empat baris bermasalah menampilkan keempatnya
- [x] Feature test penyuntingan baris bermasalah di layar mengubahnya menjadi valid

---

### T-04-07 Commit ke basis data

Prasyarat : T-04-06
Acuan : [../07-import-excel.md](../07-import-excel.md) bagian Penyimpanan
Perkiraan : M
Status : Selesai

Berkas yang disentuh:
- `app/Actions/CommitImportBatch.php`
- `tests/Feature/ImportExcelTest.php`

Kriteria selesai:
- [x] Seluruh operasi berjalan dalam satu transaksi basis data
- [x] Klub baru dibuat, atau dipetakan ke klub terpilih bila panitia memilih demikian
- [x] Atlet baru dibuat, atau dipetakan ke atlet yang sudah ada
- [x] Pendaftaran dibuat berstatus `pending` dengan `import_batch_id` terisi
- [x] Kegagalan di tengah proses membatalkan seluruh perubahan tanpa menyisakan data separuh
- [x] Batch dapat dibatalkan seluruhnya selama belum ada entrinya yang diverifikasi

Uji:
- [x] Feature test commit berhasil menghasilkan jumlah klub, atlet, dan pendaftaran yang benar
- [x] Feature test kegagalan di baris terakhir mengembalikan basis data ke keadaan semula
- [x] Feature test pembatalan batch yang sebagian entrinya sudah diverifikasi ditolak

---

### T-04-08 Pemrosesan latar belakang untuk berkas besar

Prasyarat : T-04-07
Acuan : [../07-import-excel.md](../07-import-excel.md) bagian Batasan Teknis
Perkiraan : M
Status : Selesai

Berkas yang disentuh:
- `app/Jobs/ValidateImportBatch.php`
- `app/Jobs/CommitImportBatch.php`
- `app/Notifications/ImportValidationCompleted.php`
- `app/Services/Import/ImportUploadService.php`
- `tests/Feature/ImportExcelTest.php`

Kriteria selesai:
- [x] Berkas di atas dua ratus baris divalidasi lewat antrean, bukan dalam permintaan HTTP
- [x] Layar menampilkan kemajuan proses dan menyegarkan diri
- [x] Panitia menerima pemberitahuan saat validasi selesai
- [x] Job yang gagal menandai batch berstatus `failed` beserta pesan kesalahannya
- [x] Job yang sama tidak berjalan dua kali untuk satu batch

Uji:
- [x] Feature test dengan `Queue::fake()` memastikan job terkirim untuk berkas besar
- [x] Feature test bahwa berkas kecil diproses langsung tanpa antrean
