<?php

namespace App\Exports;

use App\Models\Competition;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ParticipantTemplateExport implements WithMultipleSheets
{
    use Exportable;

    public function __construct(private readonly Competition $competition) {}

    /**
     * @return list<object>
     */
    public function sheets(): array
    {
        $this->competition->loadMissing(['events.ageGroups', 'ageGroups']);

        return [
            new Sheets\PesertaSheet,
            new Sheets\NomorLombaSheet($this->competition, protect: false, seedDefaults: true),
            new Sheets\PetunjukSheet($this->competition),
        ];
    }
}
