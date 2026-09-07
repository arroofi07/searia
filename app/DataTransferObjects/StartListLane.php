<?php

namespace App\DataTransferObjects;

class StartListLane
{
    public function __construct(
        public int $laneNumber,
        public ?int $registrationId,
        public ?string $athleteName,
        public ?int $birthYear,
        public ?string $ageGroupCode,
        public ?string $clubName,
        public ?string $city,
        public ?int $seedTimeMs,
        public ?int $clubId = null,
    ) {}

    public function isEmpty(): bool
    {
        return $this->registrationId === null;
    }

    public function formattedSeedTime(): string
    {
        return \App\Support\SwimTime::formatMilliseconds($this->seedTimeMs);
    }
}
