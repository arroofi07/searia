<?php

namespace App\DataTransferObjects;

class StartListDocument
{
    /**
     * @param  list<StartListSession>  $sessions
     */
    public function __construct(
        public int $competitionId,
        public string $competitionName,
        public string $venue,
        public string $city,
        public string $dateLabel,
        public int $poolLanes,
        public array $sessions,
        public \Carbon\CarbonInterface $printedAt,
    ) {}

    /**
     * @return list<StartListEventBlock>
     */
    public function allEvents(): array
    {
        $events = [];
        foreach ($this->sessions as $session) {
            foreach ($session->events as $event) {
                $events[] = $event;
            }
        }

        return $events;
    }
}
