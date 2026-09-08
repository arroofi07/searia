<?php

namespace App\DataTransferObjects;

class ResultsBookSession
{
    /**
     * @param  list<ResultsBookEventBlock>  $events
     */
    public function __construct(
        public int $session,
        public array $events,
    ) {}
}
