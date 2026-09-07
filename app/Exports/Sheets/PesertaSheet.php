<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class PesertaSheet extends StringValueBinder implements FromArray, ShouldAutoSize, WithColumnFormatting, WithCustomValueBinder, WithHeadings, WithTitle
{
    public function title(): string
    {
        return 'PESERTA';
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'NO',
            'NAMA LENGKAP',
            'L/P',
            'TAHUN LAHIR',
            'KLUB/SEKOLAH',
            'KABUPATEN/KOTA',
            'KODE ACARA',
            'CATATAN WAKTU',
        ];
    }

    /**
     * @return list<list<string>>
     */
    public function array(): array
    {
        return [
            ['1', 'AHZA DANISH RAHMAN', 'L', '2016', 'Gunung Sport Center', 'Padang', '13', '00:52.20'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function columnFormats(): array
    {
        return [
            'D' => NumberFormat::FORMAT_TEXT,
            'G' => NumberFormat::FORMAT_TEXT,
            'H' => NumberFormat::FORMAT_TEXT,
        ];
    }
}
