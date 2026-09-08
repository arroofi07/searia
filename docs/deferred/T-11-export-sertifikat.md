# T-11 Export dan Sertifikat

Keluaran yang dibawa pulang peserta dan yang dipakai panitia untuk pelaporan. Seluruh export memakai struktur kolom yang sama dengan template import, sehingga berkas hasil export dapat disunting lalu diunggah kembali.

Acuan: [07-import-excel.md](../07-import-excel.md) bagian Export, [02-fitur.md](../02-fitur.md) F-PAN-18

---

### T-11-01 Export daftar peserta

Prasyarat : T-04-01, T-03-01
Acuan : [../07-import-excel.md](../07-import-excel.md) bagian Export
Perkiraan : S

Berkas yang disentuh:
- `app/Exports/ParticipantExport.php`

Kriteria selesai:
- [ ] Kolomnya sama persis dengan template import ditambah kolom status dan alasan penolakan
- [ ] Berkas hasil export dapat diunggah kembali lewat alur import tanpa penyesuaian kolom
- [ ] Tersedia penyaring per klub, per nomor lomba, dan per status
- [ ] Catatan waktu diformat sebagai teks agar Excel tidak mengubahnya menjadi tanggal

Uji:
- Feature test berkas hasil export diunggah kembali menghasilkan nol kesalahan format

Kesetaraan kolom antara export dan import bukan kebetulan. Panitia sering mengoreksi data secara massal di Excel, dan alur itu hanya mulus bila kedua ujungnya berbicara dalam bentuk yang sama.

---

### T-11-02 Export start list dan hasil

Prasyarat : T-06-05, T-09-01
Acuan : [../07-import-excel.md](../07-import-excel.md) bagian Export
Perkiraan : M

Berkas yang disentuh:
- `app/Exports/StartListExport.php`
- `app/Exports/ResultExport.php`
- `app/Exports/MedalTallyExport.php`

Kriteria selesai:
- [ ] Export start list memuat nomor acara, kelompok umur, seri, lintasan, identitas, dan seed time
- [ ] Export hasil memuat peringkat, catatan waktu, status, dan selisih terhadap seed time
- [ ] Export rekap medali memuat perolehan per klub
- [ ] Tiap nomor acara ditempatkan pada lembar terpisah bila panitia memilih demikian
- [ ] Export kejuaraan berisi seribu pendaftaran selesai di bawah tiga puluh detik

Uji:
- Feature test ketiga export menghasilkan jumlah baris yang sesuai dengan data

---

### T-11-03 Export lembar hasil kosong dalam Excel

Prasyarat : T-11-02
Acuan : [../02-fitur.md](../02-fitur.md) F-PAN-14
Perkiraan : S

Berkas yang disentuh:
- `app/Exports/BlankResultSheetExport.php`

Kriteria selesai:
- [ ] Isinya sama dengan start list ditambah kolom kosong untuk waktu dan status
- [ ] Kolom waktu diformat sebagai teks
- [ ] Kolom status memakai validasi daftar berisi OK, DNS, DNF, dan DSQ
- [ ] Berkas yang sudah diisi dapat diunggah kembali untuk memasukkan hasil secara massal

Uji:
- Feature test berkas terisi diunggah kembali menghasilkan baris `results` yang benar

Jalur ini adalah cadangan ketika layar input juri tidak dapat dipakai, misalnya karena jaringan di kolam mati sepanjang hari. Kejuaraan tetap berjalan dengan kertas, dan hasilnya masuk belakangan lewat satu berkas.

---

### T-11-04 Template sertifikat

Prasyarat : T-07-01, T-09-07
Acuan : [../02-fitur.md](../02-fitur.md) F-PUB-06
Perkiraan : M

Berkas yang disentuh:
- `resources/views/pdf/certificate-participant.blade.php`
- `resources/views/pdf/certificate-winner.blade.php`
- `app/Http/Controllers/CertificateController.php`

Kriteria selesai:
- [ ] Sertifikat peserta memuat nama atlet, klub, nomor lomba, dan catatan waktu
- [ ] Sertifikat juara memuat peringkat dan kelompok umur
- [ ] Gambar latar dan teks penandatangan dapat diatur panitia per kejuaraan
- [ ] Nama panjang tetap muat tanpa terpotong maupun keluar dari bingkai
- [ ] Sertifikat hanya tersedia setelah kejuaraan berstatus `published`

Uji:
- Uji manual atas nama sepanjang delapan puluh karakter
- Feature test permintaan sertifikat pada kejuaraan berstatus `finished` ditolak

---

### T-11-05 Unduhan sertifikat massal

Prasyarat : T-11-04
Acuan : [../02-fitur.md](../02-fitur.md) F-PUB-06
Perkiraan : M

Berkas yang disentuh:
- `app/Jobs/GenerateCertificateArchive.php`

Kriteria selesai:
- [x] Panitia dapat mengunduh seluruh sertifikat kejuaraan dalam satu berkas zip
- [x] Hanya panitia yang dapat meminta pembuatan arsip
- [ ] Pembuatan berjalan lewat antrean dan pemohon menerima pemberitahuan saat siap
- [ ] Tautan unduhan kedaluwarsa setelah tujuh hari

Uji:
- [x] Feature test juri menerima 403 saat meminta arsip sertifikat
- [x] Feature test dengan `Queue::fake()` memastikan job terkirim

Sertifikat per atlet tetap dapat diunduh siapa saja setelah kejuaraan berstatus `published`. Tanpa akun pendaftar tidak ada lagi cara membatasi unduhan per klub, dan membatasinya pun tidak ada gunanya: nama juara memang diumumkan.

---

### T-11-06 Verifikasi keaslian sertifikat

Prasyarat : T-11-04
Acuan : [../02-fitur.md](../02-fitur.md)
Perkiraan : S

Berkas yang disentuh:
- `app/Http/Controllers/Public/CertificateVerificationController.php`

Kriteria selesai:
- [ ] Tiap sertifikat memuat kode unik dan kode QR yang mengarah ke halaman verifikasi
- [ ] Halaman verifikasi menampilkan nama, nomor lomba, catatan waktu, dan peringkat
- [ ] Kode yang tidak dikenal menampilkan pesan yang jelas, bukan galat
- [ ] Halaman verifikasi tidak menampilkan data pribadi selain yang tercetak pada sertifikat

Uji:
- Feature test kode sertifikat yang sah menampilkan data yang benar
- Feature test kode acak menampilkan pesan tidak ditemukan

Sertifikat renang dipakai untuk pendaftaran sekolah dan seleksi atlet, sehingga pemalsuannya bukan kekhawatiran yang mengada-ada. Kode QR membuat pemeriksaannya selesai dalam hitungan detik.
