# T-04 Import Excel

Modul yang melayani kebiasaan lama: klub mengirim daftar atlet dalam berkas Excel, panitia memasukkannya ke sistem. Alih-alih melawan kebiasaan itu, sistem menyediakan template baku dan memvalidasinya otomatis.

Acuan: [07-import-excel.md](../07-import-excel.md)

---

### T-04-01 Pasang dan konfigurasi paket Excel

Prasyarat : T-00-01
Acuan : [../README.md](../README.md)
Perkiraan : S

Berkas yang disentuh:
- `composer.json`
- `config/excel.php`

Kriteria selesai:
- [ ] Paket `maatwebsite/excel` terpasang dan konfigurasinya diterbitkan
- [ ] Batas memori dan waktu eksekusi disetel untuk berkas hingga dua ribu baris
- [ ] Pembacaan berkas memakai mode chunk, bukan memuat seluruh berkas ke memori

Uji:
- Uji manual membaca berkas dua ribu baris tanpa kehabisan memori

---

### T-04-02 Migrasi dan model batch import

Prasyarat : T-04-01, T-03-01
Acuan : [../08-database.md](../08-database.md) tabel `import_batches`
Perkiraan : S

Berkas yang disentuh:
- `database/migrations/*_create_import_batches_table.php`
- `app/Models/ImportBatch.php`
- `app/Enums/ImportStatus.php`

Kriteria selesai:
- [ ] Kolom `errors` bertipe jsonb menampung rincian kesalahan per baris
- [ ] Relasi `hasMany(Registration)` memungkinkan satu batch dibatalkan seluruhnya
- [ ] Berkas asal tersimpan pada disk `local` dengan penamaan yang tidak menimpa berkas lain
- [ ] Tersedia perintah terjadwal yang menghapus berkas asal setelah sembilan puluh hari

Uji:
- Unit test pembatalan batch menghapus seluruh pendaftaran yang berasal darinya

---

### T-04-03 Pembuatan template Excel

Prasyarat : T-04-01, T-02-06
Acuan : [../07-import-excel.md](../07-import-excel.md) bagian Template
Perkiraan : M

Template bukan berkas statis. Isinya menyesuaikan kejuaraan yang sedang dibuka, sehingga pengisi selalu melihat daftar nomor acara yang benar.

Berkas yang disentuh:
- `app/Exports/ParticipantTemplateExport.php`
- `app/Http/Controllers/Admin/ImportTemplateController.php`

Kriteria selesai:
- [ ] Lembar `PESERTA` memuat delapan kolom sesuai dokumen acuan beserta satu baris contoh
- [ ] Lembar `NOMOR LOMBA` memuat seluruh nomor acara kejuaraan tersebut beserta kelompok umur yang boleh mengikutinya
- [ ] Lembar `PETUNJUK` memuat rentang tahun lahir tiap grup, format waktu yang diterima, dan batas nomor per atlet
- [ ] Lembar rujukan terkunci agar tidak tersunting tanpa sengaja
- [ ] Kolom `TAHUN LAHIR` dan `KODE ACARA` diformat sebagai teks agar Excel tidak mengubahnya menjadi tanggal

Uji:
- Feature test unduhan template menghasilkan berkas dengan tiga lembar bernama sesuai ketentuan

Pemformatan kolom sebagai teks bukan hal sepele. Excel gemar menafsirkan angka sebagai tanggal, dan `2016` yang berubah bentuk akan menggagalkan seluruh baris tanpa penyebab yang jelas bagi pengisi.

---

### T-04-04 Pembacaan dan pemetaan berkas

Prasyarat : T-04-03
Acuan : [../07-import-excel.md](../07-import-excel.md)
Perkiraan : M

Berkas yang disentuh:
- `app/Imports/ParticipantImport.php`
- `app/DataTransferObjects/ParticipantRow.php`

Kriteria selesai:
- [ ] Menerima format `.xlsx` dan `.csv`
- [ ] Judul kolom dikenali tanpa memperhatikan besar kecil huruf dan spasi berlebih
- [ ] Berkas yang kekurangan kolom wajib ditolak sejak awal disertai daftar kolom yang hilang
- [ ] Baris kosong di tengah berkas dilewati, bukan dianggap kesalahan
- [ ] Nomor baris asli pada Excel tersimpan agar pesan kesalahan dapat menunjuk lokasi yang tepat
- [ ] Berkas melebihi lima megabita atau dua ribu baris ditolak

Uji:
- Unit test pembacaan berkas dengan judul kolom bervariasi
- Unit test berkas tanpa kolom `KODE ACARA` ditolak dengan pesan yang menyebut kolom tersebut

---

### T-04-05 Validasi per baris

Prasyarat : T-04-04, T-03-03
Acuan : [../07-import-excel.md](../07-import-excel.md) bagian Aturan Validasi
Perkiraan : L

Tiga belas kesalahan E-01 sampai E-13 dan lima peringatan W-01 sampai W-05. Validator memakai kembali `RegistrationValidator` dari T-03-03 agar aturannya identik dengan form pendaftaran.

Berkas yang disentuh:
- `app/Services/Import/RowValidator.php`
- `app/Services/Import/ImportValidationResult.php`

Kriteria selesai:
- [ ] Seluruh kode E-01 sampai E-13 terimplementasi dengan pesan sesuai dokumen acuan
- [ ] Seluruh kode W-01 sampai W-05 terimplementasi sebagai peringatan yang tidak menggagalkan baris
- [ ] Satu baris melaporkan seluruh kesalahannya sekaligus, bukan berhenti pada yang pertama
- [ ] E-10 mendeteksi duplikat di dalam berkas yang sama, E-11 mendeteksi bentrok dengan data yang sudah tersimpan
- [ ] E-12 menghitung gabungan baris dalam berkas dan pendaftaran yang sudah ada
- [ ] Validasi dua ribu baris selesai di bawah tiga puluh detik

Uji:
- Unit test satu kasus untuk tiap kode E dan W
- Uji kinerja atas berkas dua ribu baris

Persyaratan melaporkan seluruh kesalahan sekaligus terasa sepele tetapi sangat menentukan. Validator yang berhenti pada kesalahan pertama memaksa pengisi mengunggah berulang kali untuk menemukan masalah satu per satu.

---

### T-04-06 Layar unggah dan pratinjau

Prasyarat : T-04-05
Acuan : [../07-import-excel.md](../07-import-excel.md) bagian Layar Pratinjau
Perkiraan : L

Berkas yang disentuh:
- `app/Http/Controllers/Admin/ImportController.php`
- `resources/views/admin/imports/*`

Kriteria selesai:
- [ ] Ringkasan menampilkan jumlah baris dibaca, valid, bermasalah, dan berperingatan
- [ ] Tabel menampilkan baris bermasalah beserta kode dan pesan kesalahannya
- [ ] Baris bermasalah dapat disunting langsung di layar dan divalidasi ulang seketika
- [ ] Peringatan kemiripan nama klub menawarkan pemetaan ke klub yang sudah ada
- [ ] Peringatan kemiripan nama atlet menawarkan penggunaan atlet yang sudah ada
- [ ] Tersedia tombol import hanya baris valid, dengan jumlah baris yang akan diproses tertera jelas
- [ ] Tersedia tombol unduh laporan kesalahan dalam bentuk Excel

Uji:
- Feature test unggah berkas berisi empat baris bermasalah menampilkan keempatnya
- Feature test penyuntingan baris bermasalah di layar mengubahnya menjadi valid

---

### T-04-07 Commit ke basis data

Prasyarat : T-04-06
Acuan : [../07-import-excel.md](../07-import-excel.md) bagian Penyimpanan
Perkiraan : M

Berkas yang disentuh:
- `app/Actions/CommitImportBatch.php`

Kriteria selesai:
- [ ] Seluruh operasi berjalan dalam satu transaksi basis data
- [ ] Klub baru dibuat, atau dipetakan ke klub terpilih bila panitia memilih demikian
- [ ] Atlet baru dibuat, atau dipetakan ke atlet yang sudah ada
- [ ] Pendaftaran dibuat berstatus `pending` dengan `import_batch_id` terisi
- [ ] Kegagalan di tengah proses membatalkan seluruh perubahan tanpa menyisakan data separuh
- [ ] Batch dapat dibatalkan seluruhnya selama belum ada entrinya yang diverifikasi

Uji:
- Feature test commit berhasil menghasilkan jumlah klub, atlet, dan pendaftaran yang benar
- Feature test kegagalan di baris terakhir mengembalikan basis data ke keadaan semula
- Feature test pembatalan batch yang sebagian entrinya sudah diverifikasi ditolak

---

### T-04-08 Pemrosesan latar belakang untuk berkas besar

Prasyarat : T-04-07
Acuan : [../07-import-excel.md](../07-import-excel.md) bagian Batasan Teknis
Perkiraan : M

Berkas yang disentuh:
- `app/Jobs/ValidateImportBatch.php`
- `app/Jobs/CommitImportBatch.php`

Kriteria selesai:
- [ ] Berkas di atas dua ratus baris divalidasi lewat antrean, bukan dalam permintaan HTTP
- [ ] Layar menampilkan kemajuan proses dan menyegarkan diri
- [ ] Panitia menerima pemberitahuan saat validasi selesai
- [ ] Job yang gagal menandai batch berstatus `failed` beserta pesan kesalahannya
- [ ] Job yang sama tidak berjalan dua kali untuk satu batch

Uji:
- Feature test dengan `Queue::fake()` memastikan job terkirim untuk berkas besar
- Feature test bahwa berkas kecil diproses langsung tanpa antrean
