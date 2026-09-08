# T-06 Buku Acara dan Hasil PDF (MVP)

Acuan: gambar start list referensi; F-PAN-10, F-PAN-11, F-PUB-02, F-PUB-03

Format cetakan resmi (kolom):
`LANE | NAMA | YOB | AGE | CLUB | KABUPATEN/KOTA | BEST TIME` (acara)
Hasil menambah: `HASIL | TEMPAT` (atau mengganti BEST TIME dengan HASIL + TEMPAT).

Header blok: `EVENT nnn: …` lalu `SERI n`.

### T-06-01 Template PDF dasar

Kriteria selesai:
- [x] DomPDF + layout kop kejuaraan, nomor halaman

### T-06-02 Builder buku acara

Kriteria selesai:
- [x] Struktur sesi → nomor acara → kelompok umur → seri → lintasan
- [x] Lintasan kosong tetap dicetak

### T-06-03 Unduh buku acara

Kriteria selesai:
- [x] Panitia & juri dapat mengunduh
- [x] Publik setelah `seeded`

### T-06-04 PDF hasil lomba

Kriteria selesai:
- [x] Builder hasil memakai layout mirip start list
- [x] Kolom waktu final + peringkat lintas seri
- [x] Unduh panitia, juri; publik setelah `published`
- [x] Feature test unduh PDF hasil

### T-06-05 Lembar hasil kosong (opsional cadangan)

Kriteria selesai:
- [x] PDF lembar kosong per seri untuk catatan tangan
