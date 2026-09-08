# T-03 Pendaftaran Publik (MVP, tanpa tagihan)

Acuan: [04-alur-pendaftaran.md](../04-alur-pendaftaran.md)

### T-03-01 Wizard tanpa akun

Kriteria selesai:
- [x] Langkah: kompetisi → kontak+atlet → nomor+seed time → kirim
- [x] Rate limit + honeypot

### T-03-02 Submission dan kode

Kriteria selesai:
- [x] `registration_submissions` dengan kode `REG-…`
- [x] Satu baris `registrations` per nomor, status `pending`
- [x] **Tidak** menerbitkan invoice/tagihan

### T-03-03 Halaman selesai

Kriteria selesai:
- [x] Menampilkan kode pendaftaran dan daftar nomor
- [x] Tanpa instruksi transfer / unduh tagihan

### T-03-04 Verifikasi panitia

Kriteria selesai:
- [x] Setujui → `verified` (langsung eligible seeding)
- [x] Tolak dengan alasan → `rejected`
