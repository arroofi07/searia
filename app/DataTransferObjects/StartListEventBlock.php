<?php

namespace App\DataTransferObjects;

class StartListEventBlock
{
    /**
     * @param  list<StartListAgeGroupBlock>  $ageGroups
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
        return 'Acara '.$this->eventNumber.' - '.$this->eventName;
    }
}
