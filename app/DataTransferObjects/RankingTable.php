<?php

namespace App\DataTransferObjects;

class RankingTable
{
    /**
     * @param  list<RankingEntry>  $entries
     */
    public function __construct(
        public readonly int $eventId,
        public readonly int $ageGroupId,
        public readonly string $eventTitle,
        public readonly string $ageGroupName,
        public readonly array $entries,
    ) {}
}
