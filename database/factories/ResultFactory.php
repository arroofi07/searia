<?php

namespace Database\Factories;

use App\Models\HeatLane;
use App\Models\Result;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Result>
 */
class ResultFactory extends Factory
{
    protected $model = Result::class;

    public function definition(): array
    {
        return [
            'heat_lane_id' => HeatLane::factory(),
            'time_ms' => fake()->numberBetween(20_000, 180_000),
            'status' => 'ok',
            'dsq_code' => null,
            'dsq_reason' => null,
            'recorded_by' => User::factory(),
            'recorded_at' => now(),
            'verified_by' => null,
            'verified_at' => null,
        ];
    }

    public function verified(?User $by = null): static
    {
        return $this->state(fn (): array => [
            'verified_by' => $by?->id ?? User::factory(),
            'verified_at' => now(),
        ]);
    }
}
