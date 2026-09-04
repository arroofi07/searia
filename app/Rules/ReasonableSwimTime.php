<?php

namespace App\Rules;

use App\Exceptions\InvalidSwimTimeException;
use App\Support\SwimTime;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ReasonableSwimTime implements ValidationRule
{
    public function __construct(
        private readonly int $distance,
        private readonly bool $rejectOutOfRange = true,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        try {
            $parsed = $value instanceof SwimTime
                ? $value
                : (is_int($value) ? SwimTime::fromMilliseconds($value) : SwimTime::parse((string) $value));
        } catch (InvalidSwimTimeException $exception) {
            $fail($exception->getMessage());

            return;
        }

        if ($parsed === null || $parsed->milliseconds === null) {
            return;
        }

        if ($this->isOutOfRange($parsed->milliseconds) && $this->rejectOutOfRange) {
            $fail("Waktu terlalu cepat untuk jarak {$this->distance} m");
        }
    }

    public function isOutOfRange(int $milliseconds): bool
    {
        $bounds = $this->bounds();

        if ($bounds === null) {
            return false;
        }

        return $milliseconds < $bounds['min_ms'] || $milliseconds > $bounds['max_ms'];
    }

    /**
     * @return array{min_ms: int, max_ms: int}|null
     */
    public function bounds(): ?array
    {
        $configured = config('searia.swim_time.bounds.'.$this->distance);

        if (! is_array($configured)) {
            return null;
        }

        return [
            'min_ms' => (int) $configured['min_ms'],
            'max_ms' => (int) $configured['max_ms'],
        ];
    }
}
