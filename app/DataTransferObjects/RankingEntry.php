<?php

namespace App\DataTransferObjects;

use App\Enums\ResultStatus;
use App\Support\MedalIcon;

class RankingEntry
{
    public function __construct(
        public readonly int $resultId,
        public readonly ?int $rank,
        public readonly string $athleteName,
        public readonly int $athleteId,
        public readonly string $clubName,
        public readonly ?int $clubId,
        public readonly ?string $city,
        public readonly ResultStatus $status,
        public readonly ?int $timeMs,
        public readonly ?int $seedTimeMs,
        public readonly int $heatNumber,
        public readonly int $laneNumber,
        public readonly ?int $gapToFirstMs,
        public readonly bool $isPersonalBest,
        public readonly ?string $dsqCode = null,
        public readonly ?string $ageGroupCode = null,
        public readonly ?int $age = null,
    ) {}

    public function isPodium(): bool
    {
        return $this->rank !== null && $this->rank <= 3;
    }

    public function formattedAge(): string
    {
        return $this->age !== null ? (string) $this->age : '—';
    }

    public function medalMark(int $place): string
    {
        if ($this->rank !== $place) {
            return '';
        }

        return MedalIcon::metal($place) ?? '';
    }
}
