<?php

namespace App\DataTransferObjects;

class ResultsBookEventBlock
{
    /**
     * @param  list<ResultsBookAgeGroupBlock>  $ageGroups
     */
    public function __construct(
        public int $eventId,
        public int $eventNumber,
        public string $eventName,
        public int $session,
        public int $sortOrder,
        public array $ageGroups,
    ) {}

    public function title(): string
    {
        return 'EVENT '.$this->eventNumber.': '.$this->eventName;
    }
}
