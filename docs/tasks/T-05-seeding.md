# T-05 Seeding Seri dan Lintasan (MVP)

Acuan: [05-seri-dan-lintasan.md](../05-seri-dan-lintasan.md)

### T-05-01 Model heats & heat_lanes

Kriteria selesai:
- [x] Unik event × age group × heat number
- [x] Unik heat × lane; unik registration di satu lintasan
- [x] `locked_at` untuk kunci susunan

### T-05-02 Algoritma

Kriteria selesai:
- [x] Urut seed time ASC, NT terakhir
- [x] Pembagian seri balanced; lintasan center-out
- [x] Hanya `verified` yang di-seed (**tanpa** syarat invoice)

### T-05-03 Preview, penyesuaian, kunci

Kriteria selesai:
- [x] Preview sebelum commit
- [x] Tukar lintasan / pindah seri oleh panitia
- [x] Kunci seeding; audit koreksi setelah kunci
