<?php

namespace App\DataTransferObjects;

class ResultsBookAgeGroupBlock
{
    /**
     * @param  list<ResultsBookLane>  $lanes
     */
    public function __construct(
        public int $ageGroupId,
        public string $name,
        public string $displayCode,
        public int $sortOrder,
        public array $lanes,
    ) {}
}
