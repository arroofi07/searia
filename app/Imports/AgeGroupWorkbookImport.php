<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AgeGroupWorkbookImport implements WithMultipleSheets
{
    public function __construct(public AgeGroupSheetImport $sheet) {}

    /**
     * @return array<string|int, AgeGroupSheetImport>
     */
    public function sheets(): array
    {
        return [
            'KELOMPOK UMUR' => $this->sheet,
        ];
    }
}
