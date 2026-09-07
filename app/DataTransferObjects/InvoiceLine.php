<?php

namespace App\DataTransferObjects;

class InvoiceLine
{
    public function __construct(
        public int $registrationId,
        public string $athleteName,
        public string $eventName,
        public int $baseFee,
        public int $lateFee,
        public bool $isLate,
    ) {}

    public function subtotal(): int
    {
        return $this->baseFee + $this->lateFee;
    }

    /**
     * @return array{
     *     registration_id: int,
     *     athlete_name: string,
     *     event_name: string,
     *     base_fee: int,
     *     late_fee: int,
     *     is_late: bool,
     *     subtotal: int
     * }
     */
    public function toArray(): array
    {
        return [
            'registration_id' => $this->registrationId,
            'athlete_name' => $this->athleteName,
            'event_name' => $this->eventName,
            'base_fee' => $this->baseFee,
            'late_fee' => $this->lateFee,
            'is_late' => $this->isLate,
            'subtotal' => $this->subtotal(),
        ];
    }
}
