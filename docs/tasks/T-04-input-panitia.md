# T-04 Input Panitia: Manual + Excel (MVP)

Acuan: [07-import-excel.md](../07-import-excel.md), F-PAN-06, F-PAN-07

### T-04-01 Input / edit entri manual

Kriteria selesai:
- [x] Panitia menambah pendaftaran atlet × nomor dari admin
- [x] Panitia mengubah seed time atau membatalkan entri
- [x] Validasi V-01 … V-06 sama dengan form publik

### T-04-02 Template Excel

Kriteria selesai:
- [x] Unduh template peserta
- [x] Kolom: nama, gender, YOB, klub, kota, nomor acara, seed time

### T-04-03 Validasi, pratinjau, commit

Kriteria selesai:
- [x] Validasi baris dengan kode error jelas
- [x] Pratinjau sebelum commit
- [x] Commit menghasilkan `registrations` `pending` (tanpa invoice klub)

### T-04-04 Antrean import

Kriteria selesai:
- [x] File besar diproses aman (queue bila perlu)
- [x] Hanya panitia/super_admin
