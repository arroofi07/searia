<?php

namespace App\DataTransferObjects;

class StartListSession
{
    /**
     * @param  list<StartListEventBlock>  $events
     */
    public function __construct(
        public int $session,
        public array $events,
    ) {}
}
