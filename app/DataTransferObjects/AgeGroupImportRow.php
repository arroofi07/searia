<?php

namespace App\DataTransferObjects;

class AgeGroupImportRow
{
    public function __construct(
        public int $excelRow,
        public string $code,
        public string $name,
        public ?string $displayCode,
        public int $birthYearStart,
        public int $birthYearEnd,
        public int $sortOrder,
    ) {}
}
