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
            ['Siapa yang mengunggah', 'Panitia dan Super Admin, lewat Pendaftaran → Import Excel. Peserta memakai form publik.'],
            ['Kejuaraan', $this->competition->name],
            ['Batas nomor per atlet', (string) $this->competition->max_events_per_athlete],
            [''],
            ['Yang diisi', 'Hanya lembar PESERTA. Lembar NOMOR LOMBA dan PETUNJUK terkunci.'],
            ['Kolom wajib', 'NAMA LENGKAP, L/P, TAHUN LAHIR, KLUB/SEKOLAH, KODE ACARA'],
            ['L/P', 'Isi L untuk putra atau P untuk putri'],
            ['KODE ACARA', 'Salin nomor dari kolom KODE ACARA di lembar NOMOR LOMBA, bukan nama gaya'],
            ['CATATAN WAKTU', 'Boleh dikosongkan (berarti NT)'],
            [''],
            ['Lembar NOMOR LOMBA (jangan diubah)'],
            ['KODE ACARA', 'Nomor urut acara yang disalin ke lembar PESERTA'],
            ['NOMOR PERLOMBAAN', 'Nama nomor, misalnya 50 M Gaya Dada'],
            ['GENDER', 'Putra atau Putri'],
            ['GRUP YANG BOLEH IKUT', 'Kelompok umur yang diizinkan. Ubah di Matriks kelayakan di aplikasi, bukan di Excel.'],
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
