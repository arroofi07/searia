<?php

namespace App\Exports\Sheets;

use App\Models\AgeGroup;
use App\Models\Competition;
use App\Support\SwimTime;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

class PetunjukSheet implements FromArray, ShouldAutoSize, WithEvents, WithTitle
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
            ['Petunjuk pengisian template pendaftaran'],
            [''],
            ['Kejuaraan', $this->competition->name],
            ['Batas nomor per atlet', (string) $this->competition->max_events_per_athlete],
            [''],
            ['Kolom wajib', 'NAMA LENGKAP, L/P, TAHUN LAHIR, KLUB/SEKOLAH, KODE ACARA'],
            ['L/P', 'Isi L untuk putra atau P untuk putri'],
            ['KODE ACARA', 'Nomor acara dari lembar NOMOR LOMBA, bukan nama gaya'],
            ['CATATAN WAKTU', 'Boleh dikosongkan (berarti NT)'],
            [''],
            ['Format waktu yang diterima'],
            ['Contoh 52,20 detik', SwimTime::formatMilliseconds(52_200)],
            ['Contoh satu menit lebih', SwimTime::formatMilliseconds(94_700)],
            ['Tanpa waktu', 'NT, -, atau dikosongkan'],
            [''],
            ['Kelompok umur (tahun lahir)'],
        ];

        foreach ($this->competition->ageGroups as $group) {
            /** @var AgeGroup $group */
            $lines[] = [
                $group->name,
                $group->birth_year_start.'–'.$group->birth_year_end,
            ];
        }

        return $lines;
    }

    /**
     * @return array<class-string, callable>
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $protection = $event->sheet->getDelegate()->getProtection();
                $protection->setPassword('searia');
                $protection->setSheet(true);
            },
        ];
    }
}
