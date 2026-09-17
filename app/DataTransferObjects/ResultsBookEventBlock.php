<?php

namespace App\DataTransferObjects;

class ResultsBookEventBlock
{
    /**
     * @param  list<ResultsBookLane>  $lanes
     */
    public function __construct(
        public int $eventId,
        public int $eventNumber,
        public string $eventName,
        public string $ageGroupName,
        public string $ageGroupCode,
        public int $session,
        public int $sortOrder,
        public array $lanes,
    ) {}

    public function title(): string
    {
        return 'Nomor '.$this->eventNumber.' · '.$this->eventName.' · '.$this->ageGroupName;
    }
}
