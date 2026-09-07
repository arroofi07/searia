<?php

namespace App\Services\Import;

use App\DataTransferObjects\ParticipantRow;

class ValidatedImportRow
{
    /**
     * @param  list<array{code: string, message: string}>  $errors
     * @param  list<array{code: string, message: string}>  $warnings
     * @param  list<array{id: int, name: string}>  $clubSuggestions
     * @param  list<array{id: int, full_name: string}>  $athleteSuggestions
     */
    public function __construct(
        public ParticipantRow $row,
        public array $errors = [],
        public array $warnings = [],
        public array $clubSuggestions = [],
        public array $athleteSuggestions = [],
        public ?int $resolvedClubId = null,
        public ?int $resolvedAthleteId = null,
    ) {}

    public function isValid(): bool
    {
        return $this->errors === [];
    }

    public function hasWarnings(): bool
    {
        return $this->warnings !== [];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            ...$this->row->toArray(),
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'club_suggestions' => $this->clubSuggestions,
            'athlete_suggestions' => $this->athleteSuggestions,
            'resolved_club_id' => $this->resolvedClubId,
            'resolved_athlete_id' => $this->resolvedAthleteId,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            row: ParticipantRow::fromFields($payload, (int) ($payload['excel_row'] ?? 0)),
            errors: $payload['errors'] ?? [],
            warnings: $payload['warnings'] ?? [],
            clubSuggestions: $payload['club_suggestions'] ?? [],
            athleteSuggestions: $payload['athlete_suggestions'] ?? [],
            resolvedClubId: isset($payload['resolved_club_id']) ? (int) $payload['resolved_club_id'] : null,
            resolvedAthleteId: isset($payload['resolved_athlete_id']) ? (int) $payload['resolved_athlete_id'] : null,
        );
    }
}
