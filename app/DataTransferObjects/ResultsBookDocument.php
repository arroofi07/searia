<?php

namespace App\DataTransferObjects;

use Carbon\CarbonInterface;

class ResultsBookDocument
{
    /**
     * @param  list<ResultsBookSession>  $sessions
     */
    public function __construct(
        public int $competitionId,
        public string $competitionName,
        public string $venue,
        public string $city,
        public string $dateLabel,
        public int $poolLanes,
        public array $sessions,
        public CarbonInterface $printedAt,
    ) {}
}
