<?php

namespace Database\Factories;

use App\Enums\CompetitionStatus;
use App\Enums\CompetitionType;
use App\Enums\SeedingMode;
use App\Models\Competition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Competition>
 */
class CompetitionFactory extends Factory
{
    protected $model = Competition::class;

    public function definition(): array
    {
        $start = fake()->dateTimeBetween('+1 month', '+6 months');
        $end = (clone $start)->modify('+1 day');
        $opens = (clone $start)->modify('-30 days');
        $closes = (clone $start)->modify('-1 day');

        return [
            'name' => fake()->unique()->words(4, true).' Championship',
            'venue' => fake()->streetName().' Aquatic Center',
            'city' => fake()->city(),
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'registration_opens_at' => $opens,
            'registration_closes_at' => $closes,
            'technical_meeting_at' => (clone $start)->modify('-1 day')->setTime(19, 0),
            'type' => CompetitionType::Official,
            'pool_lanes' => 8,
            'pool_length' => 25,
            'max_events_per_athlete' => 3,
            'seeding_mode' => SeedingMode::Balanced,
            'fee_per_event' => 50_000,
            'late_fee_per_event' => 0,
            'status' => CompetitionStatus::Draft,
            'banner_path' => null,
            'description' => null,
        ];
    }

    public function status(CompetitionStatus $status): static
    {
        return $this->state(fn (): array => [
            'status' => $status,
        ]);
    }
}
