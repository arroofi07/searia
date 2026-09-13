<?php

namespace App\Exports;

use App\Exports\Sheets\AgeGroupPetunjukSheet;
use App\Exports\Sheets\KelompokUmurSheet;
use App\Models\Competition;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AgeGroupExport implements WithMultipleSheets
{
    use Exportable;

    public function __construct(private readonly Competition $competition) {}

    /**
     * @return list<object>
     */
    public function sheets(): array
    {
        $this->competition->loadMissing('ageGroups');

        return [
            new KelompokUmurSheet($this->competition),
            new AgeGroupPetunjukSheet($this->competition),
        ];
    }
}
