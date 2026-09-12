<?php

namespace App\DataTransferObjects;

use App\Enums\Equipment;
use App\Enums\EventGender;
use App\Enums\Stroke;

class EventProgramRow
{
    /**
     * @param  list<int>|null  $ageGroupIds  null = jangan mengubah grup yang sudah tersimpan
     */
    public function __construct(
        public int $excelRow,
        public int $eventNumber,
        public EventGender $gender,
        public int $distance,
        public Stroke $stroke,
        public Equipment $equipment,
        public ?array $ageGroupIds,
    ) {}
}
