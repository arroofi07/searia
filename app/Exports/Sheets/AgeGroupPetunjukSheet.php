<?php

namespace App\Exports\Sheets;

use App\Models\Competition;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class AgeGroupPetunjukSheet implements FromArray, ShouldAutoSize, WithTitle
{
    public function __construct(private readonly Competition $competition) {}

    public function title(): string
    {
        return 'PETUNJUK';
    }

    /**
     * @return list<list<string>>
     */
    public function array(): array
    {
        return [
            ['Petunjuk pengisian kelompok umur'],
            [''],
            ['Unggah di', 'Pengaturan acara → Kelompok umur'],
            ['Kejuaraan', $this->competition->name],
            ['Tahun lomba', (string) $this->competition->year()],
            [''],
            ['Satu baris = satu kelompok umur. Nama boleh bebas (Searia1), kode 1–9 agar terhubung dengan Excel nomor lomba.'],
            ['Tahun lahir tidak boleh tumpang tindih. Grup yang sudah punya pendaftaran tidak boleh diubah tahun lahirnya.'],
            [''],
            ['KODE', 'Unik di kejuaraan ini, maksimal 10 karakter. Pakai 1–9 untuk grup baku.'],
            ['NAMA', 'Nama tampilan, misalnya Group 1 atau Searia1'],
            ['LABEL CETAK', 'Opsional. Biasanya Romawi I–IX di buku acara'],
            ['TAHUN LAHIR AWAL', 'Batas bawah tahun lahir'],
            ['TAHUN LAHIR AKHIR', 'Batas atas tahun lahir'],
            ['URUTAN', 'Opsional. Angka 1–99 untuk urutan tampil'],
        ];
    }
}
