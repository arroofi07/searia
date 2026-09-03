# T-08 Input Hasil Juri

Modul yang dipakai di pinggir kolam, sering pada ponsel, dengan jaringan yang tidak selalu stabil dan seri berikutnya yang sudah menunggu. Kecepatan pengisian dan ketahanan terhadap gangguan koneksi lebih menentukan di sini daripada kelengkapan fitur.

Acuan: [06-catatan-waktu.md](../06-catatan-waktu.md) bagian Status Selain Waktu dan Layar Input Hasil oleh Juri

---

### T-08-01 Migrasi dan model hasil

Prasyarat : T-06-01
Acuan : [../08-database.md](../08-database.md) tabel `results`
Perkiraan : S

Berkas yang disentuh:
- `database/migrations/*_create_results_table.php`
- `app/Models/Result.php`
- `app/Enums/ResultStatus.php`
- `app/Enums/DisqualificationCode.php`

Kriteria selesai:
- [ ] Relasi satu ke satu dengan `heat_lanes` lewat indeks unik pada `heat_lane_id`
- [ ] `time_ms` bertipe integer nullable dengan cast `SwimTime`
- [ ] Enum status memuat `ok`, `dns`, `dnf`, `dsq`
- [ ] Enum kode diskualifikasi memuat `SF`, `ST`, `TN`, `FN`, `NA`, `OT`
- [ ] Batasan tingkat model memastikan `time_ms` terisi bila dan hanya bila status bernilai `ok`

Uji:
- Unit test menyimpan status `dsq` beserta `time_ms` ditolak
- Unit test menyimpan status `ok` tanpa `time_ms` ditolak

---

### T-08-02 Penugasan juri per nomor lomba

Prasyarat : T-00-02, T-02-05
Acuan : [../02-fitur.md](../02-fitur.md) F-PAN-15, F-JUR-01
Perkiraan : S

Berkas yang disentuh:
- `database/migrations/*_create_event_judge_table.php`
- `app/Http/Controllers/Admin/JudgeAssignmentController.php`

Kriteria selesai:
- [ ] Panitia dapat menetapkan satu juri ke beberapa nomor lomba
- [ ] Juri hanya melihat nomor lomba yang ditugaskan kepadanya
- [ ] Juri tanpa penugasan melihat halaman kosong disertai penjelasan, bukan galat
- [ ] Panitia selalu dapat mengisi hasil nomor lomba mana pun

Uji:
- Feature test juri menerima 403 saat membuka nomor lomba di luar penugasannya

---

### T-08-03 Daftar tugas juri

Prasyarat : T-08-02
Acuan : [../02-fitur.md](../02-fitur.md) F-JUR-01
Perkiraan : S

Berkas yang disentuh:
- `app/Http/Controllers/Judge/TaskListController.php`
- `resources/views/judge/tasks/*`

Kriteria selesai:
- [ ] Menampilkan nomor lomba yang ditugaskan beserta kemajuan pengisiannya, misalnya sembilan dari lima belas seri selesai
- [ ] Seri yang belum terisi ditampilkan lebih dahulu
- [ ] Satu ketukan dari daftar langsung membuka layar input seri
- [ ] Halaman menyegarkan diri agar juri melihat kemajuan rekan yang mengisi nomor lain

Uji:
- Feature test perhitungan kemajuan sesuai jumlah seri yang sudah dikunci

---

### T-08-04 Layar input hasil per seri

Prasyarat : T-08-01, T-08-03
Acuan : [../06-catatan-waktu.md](../06-catatan-waktu.md) bagian Layar Input Hasil oleh Juri
Perkiraan : L

Berkas yang disentuh:
- `app/Http/Controllers/Judge/HeatResultController.php`
- `resources/views/judge/heats/*`

Kriteria selesai:
- [ ] Satu baris per lintasan terisi, sudah memuat nama, klub, dan seed time
- [ ] Hanya kolom waktu dan status yang dapat disunting
- [ ] Lintasan kosong ditampilkan sebagai baris nonaktif, bukan disembunyikan
- [ ] Kolom waktu menampilkan hasil penguraian dalam format baku di sebelah masukan
- [ ] Memilih status DNS, DNF, atau DSQ mengosongkan dan menonaktifkan kolom waktu
- [ ] Memilih status DSQ memunculkan pilihan kode diskualifikasi yang wajib diisi
- [ ] Tersedia navigasi ke seri sebelumnya dan berikutnya tanpa kembali ke daftar
- [ ] Sasaran ketuk berukuran nyaman untuk jari basah di pinggir kolam

Uji:
- Feature test penyimpanan hasil satu seri lengkap
- Feature test status DSQ tanpa kode ditolak validasi

---

### T-08-05 Input cepat tanpa pemisah

Prasyarat : T-08-04, T-00-05
Acuan : [../06-catatan-waktu.md](../06-catatan-waktu.md) bagian Penguraian Masukan
Perkiraan : M

Mengetik `3470` jauh lebih cepat daripada `00:34.70`, terutama pada papan ketik ponsel. Selisih beberapa detik per lintasan menjadi berarti ketika ada ratusan lintasan dalam satu hari.

Berkas yang disentuh:
- `resources/js/swim-time-input.js`

Kriteria selesai:
- [ ] Masukan empat digit dibaca sebagai detik dan perseratus detik
- [ ] Masukan lima digit dibaca sebagai menit, detik, dan perseratus detik
- [ ] Format baku hasil penguraian tampil seketika saat mengetik
- [ ] Papan ketik ponsel yang muncul adalah papan angka
- [ ] Tombol Enter memindahkan fokus ke lintasan berikutnya
- [ ] Mode ini dapat dinonaktifkan lewat konfigurasi kejuaraan
- [ ] Penguraian di sisi klien dan di sisi server menghasilkan nilai yang sama

Uji:
- Unit test JavaScript untuk seluruh bentuk masukan
- Unit test PHP dengan masukan yang sama menghasilkan milidetik identik

Menyimpan aturan penguraian di dua bahasa berisiko keduanya menyimpang. Uji terakhir pada daftar di atas ada khusus untuk menahan penyimpangan itu.

---

### T-08-06 Simpan otomatis per baris

Prasyarat : T-08-04
Acuan : [../02-fitur.md](../02-fitur.md) F-JUR-05
Perkiraan : M

Berkas yang disentuh:
- `app/Http/Controllers/Judge/HeatLaneResultController.php`
- `resources/js/heat-autosave.js`

Kriteria selesai:
- [ ] Setiap baris tersimpan begitu kolomnya ditinggalkan
- [ ] Penanda visual membedakan baris tersimpan, sedang menyimpan, dan gagal menyimpan
- [ ] Kegagalan jaringan dicoba ulang otomatis dengan jeda yang meningkat
- [ ] Meninggalkan halaman dengan baris yang belum tersimpan memunculkan peringatan
- [ ] Penyimpanan bersifat idempoten sehingga percobaan ulang tidak menggandakan baris hasil

Uji:
- Feature test pengiriman dua kali untuk lintasan yang sama menghasilkan satu baris hasil
- Uji manual dengan jaringan dimatikan lalu dinyalakan kembali

---

### T-08-07 Penguncian seri

Prasyarat : T-08-06
Acuan : [../02-fitur.md](../02-fitur.md) F-JUR-06
Perkiraan : S

Berkas yang disentuh:
- `app/Actions/LockHeat.php`

Kriteria selesai:
- [ ] Tombol kunci hanya aktif setelah seluruh lintasan terisi waktu atau status
- [ ] Setelah terkunci, juri tidak dapat lagi mengubah isinya
- [ ] Seri terkunci ditandai jelas pada daftar tugas
- [ ] Panitia dapat membuka kunci disertai alasan yang tercatat

Uji:
- Feature test penguncian seri yang belum lengkap ditolak
- Feature test juri menerima 403 saat mengubah seri terkunci

---

### T-08-08 Koreksi hasil oleh panitia

Prasyarat : T-08-07
Acuan : [../06-catatan-waktu.md](../06-catatan-waktu.md)
Perkiraan : M

Berkas yang disentuh:
- `app/Http/Controllers/Admin/ResultCorrectionController.php`
- `app/Actions/CorrectResult.php`

Kriteria selesai:
- [ ] Panitia dapat mengubah waktu maupun status pada seri yang sudah terkunci
- [ ] Koreksi wajib disertai alasan
- [ ] Jejak audit mencatat nilai lama, nilai baru, pelaku, dan waktunya
- [ ] Riwayat koreksi terlihat pada layar hasil nomor lomba tersebut
- [ ] Koreksi atas kejuaraan yang sudah berstatus `published` memunculkan peringatan tambahan karena hasil sudah beredar

Uji:
- Feature test koreksi tanpa alasan ditolak
- Feature test koreksi menghasilkan satu entri audit berisi nilai lama dan baru

Riwayat koreksi sengaja terlihat oleh publik, bukan hanya oleh panitia. Perubahan hasil setelah pengumuman selalu menimbulkan pertanyaan, dan keterbukaan jauh lebih murah daripada perdebatan.
