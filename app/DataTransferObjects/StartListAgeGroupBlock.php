<?php

namespace App\DataTransferObjects;

class StartListAgeGroupBlock
{
    /**
     * @param  list<StartListHeat>  $heats
     */
    public function __construct(
        public int $ageGroupId,
        public string $name,
        public string $displayCode,
        public int $sortOrder,
        public array $heats,
    ) {}
}
