<?php

namespace App\DataTransferObjects;

use App\Enums\ResultStatus;
use App\Support\SwimTime;

class ResultsBookLane
{
    public function __construct(
        public int $laneNumber,
        public ?int $registrationId,
        public ?string $athleteName,
        public ?int $birthYear,
        public ?string $ageGroupCode,
        public ?string $clubName,
        public ?string $city,
        public ?int $resultTimeMs,
        public ?ResultStatus $resultStatus,
        public ?int $rank,
        public ?string $dsqCode = null,
    ) {}

    public function isEmpty(): bool
    {
        return $this->registrationId === null;
    }

    public function formattedResult(): string
    {
        if ($this->resultStatus === null) {
            return '—';
        }

        if ($this->resultStatus !== ResultStatus::Ok) {
            $label = strtoupper($this->resultStatus->value);
            if ($this->dsqCode) {
                return $label.' '.$this->dsqCode;
            }

            return $label;
        }

        return SwimTime::formatMilliseconds($this->resultTimeMs);
    }

    public function formattedRank(): string
    {
        return $this->rank !== null ? (string) $this->rank : '—';
    }
}
