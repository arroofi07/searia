<?php

namespace App\Exports;

use App\Exports\Sheets\EventProgramPetunjukSheet;
use App\Exports\Sheets\NomorLombaSheet;
use App\Models\Competition;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class EventProgramExport implements WithMultipleSheets
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
            new NomorLombaSheet($this->competition, protect: false),
            new EventProgramPetunjukSheet($this->competition),
        ];
    }
}
