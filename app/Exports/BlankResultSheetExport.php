<?php

namespace App\Exports;

use App\Models\Competition;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class BlankResultSheetExport extends StartListExport implements WithEvents
{
    public function __construct(Competition $competition)
    {
        parent::__construct($competition, blankResults: true, sheetTitle: 'LEMBAR HASIL');
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $highest = max(2, (int) $sheet->getHighestRow());
                $validation = $sheet->getCell('L2')->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $validation->setAllowBlank(true);
                $validation->setShowDropDown(true);
                $validation->setFormula1('"OK,DNS,DNF,DSQ"');
                $sheet->setDataValidation("L2:L{$highest}", clone $validation);
            },
        ];
    }
}
