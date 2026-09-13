<?php

namespace App\Exports\Sheets;

use App\Models\AgeGroup;
use App\Models\Competition;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class EventProgramPetunjukSheet implements FromArray, ShouldAutoSize, WithTitle
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
        $lines = [
            ['Petunjuk pengisian nomor lomba'],
            [''],
            ['Unggah di', 'Pengaturan acara → Nomor lomba'],
            ['Kejuaraan', $this->competition->name],
            [''],
            ['Isi lembar NOMOR LOMBA, lalu unggah ulang di halaman yang sama.'],
            ['Satu baris = satu nomor acara (putra dan putri ditulis terpisah).'],
            ['Jika kelompok umur belum ada, tulis nama berakhiran 1–6 (Group 1 atau Searia1). Sistem membuat grup baku otomatis.'],
            [''],
            ['KODE ACARA', 'Nomor urut acara, unik di kejuaraan ini'],
            ['NOMOR PERLOMBAAN', 'Contoh: 50 M Gaya Dada atau 50 M Gaya Kupu-Kupu (Fins)'],
            ['GENDER', 'Putra atau Putri (PA/PI juga diterima)'],
            ['GRUP YANG BOLEH IKUT', 'Dipisah koma. Contoh: Group 1, Group 2 atau Searia1, Searia2'],
            [''],
            ['Kelompok umur yang sudah ada di kejuaraan ini'],
        ];

        foreach ($this->competition->ageGroups as $group) {
            /** @var AgeGroup $group */
            $lines[] = [
                $group->name,
                $group->code.' · '.$group->birth_year_start.'–'.$group->birth_year_end,
            ];
        }

        if ($this->competition->ageGroups->isEmpty()) {
            $lines[] = ['Belum ada. Unggah nama grup 1–6 (Group 1 atau Searia1) pada lembar NOMOR LOMBA untuk membuatnya otomatis.'];
        }

        return $lines;
    }
}
