<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class EventProgramWorkbookImport implements WithMultipleSheets
{
    public function __construct(public EventProgramSheetImport $sheet) {}

    /**
     * @return array<string|int, EventProgramSheetImport>
     */
    public function sheets(): array
    {
        return [
            'NOMOR LOMBA' => $this->sheet,
        ];
    }
}
