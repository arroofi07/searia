<?php

namespace App\DataTransferObjects;

class ResultsBookHeat
{
    /**
     * @param  list<ResultsBookLane>  $lanes
     */
    public function __construct(
        public int $heatId,
        public int $heatNumber,
        public array $lanes,
    ) {}
}
