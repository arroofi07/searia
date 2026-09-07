<?php

namespace App\Exports;

use App\Services\Import\ValidatedImportRow;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class ImportErrorReportExport implements FromArray, ShouldAutoSize, WithHeadings, WithTitle
{
    /**
     * @param  list<ValidatedImportRow>  $rows
     */
    public function __construct(private readonly array $rows) {}

    public function title(): string
    {
        return 'KESALAHAN';
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'BARIS',
            'NAMA LENGKAP',
            'L/P',
            'TAHUN LAHIR',
            'KLUB/SEKOLAH',
            'KABUPATEN/KOTA',
            'KODE ACARA',
            'CATATAN WAKTU',
            'KODE KESALAHAN',
            'PESAN',
        ];
    }

    /**
     * @return list<list<string>>
     */
    public function array(): array
    {
        $out = [];

        foreach ($this->rows as $row) {
            $codes = collect($row->errors)->pluck('code')->implode(', ');
            $messages = collect($row->errors)->pluck('message')->implode(' | ');
            $fields = $row->row;

            $out[] = [
                (string) $fields->excelRow,
                $fields->fullName,
                $fields->gender,
                $fields->birthYear,
                $fields->clubName,
                $fields->city,
                $fields->eventCode,
                $fields->seedTime,
                $codes,
                $messages,
            ];
        }

        return $out;
    }
}
