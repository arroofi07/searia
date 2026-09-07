<?php

namespace Database\Factories;

use App\Enums\ResultStatus;
use App\Models\Athlete;
use App\Models\Certificate;
use App\Models\Competition;
use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Certificate>
 */
class CertificateFactory extends Factory
{
    protected $model = Certificate::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('??????????')),
            'competition_id' => Competition::factory(),
            'athlete_id' => Athlete::factory(),
            'event_id' => Event::factory(),
            'type' => 'participant',
            'rank' => null,
            'time_ms' => 45_000,
            'status' => ResultStatus::Ok,
        ];
    }

    public function winner(int $rank = 1): static
    {
        return $this->state(fn () => [
            'type' => 'winner',
            'rank' => $rank,
        ]);
    }
}
