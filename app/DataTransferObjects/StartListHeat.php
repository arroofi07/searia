<?php

namespace App\DataTransferObjects;

class StartListHeat
{
    /**
     * @param  list<StartListLane>  $lanes
     */
    public function __construct(
        public int $heatId,
        public int $heatNumber,
        public array $lanes,
    ) {}
}
