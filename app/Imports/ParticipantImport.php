<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ParticipantImport implements WithMultipleSheets
{
    public function __construct(public ParticipantSheetImport $sheet) {}

    /**
     * @return array<string|int, ParticipantSheetImport>
     */
    public function sheets(): array
    {
        return [
            'PESERTA' => $this->sheet,
        ];
    }
}
