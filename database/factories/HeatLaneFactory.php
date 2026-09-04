<?php

namespace Database\Factories;

use App\Models\Heat;
use App\Models\HeatLane;
use App\Models\Registration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HeatLane>
 */
class HeatLaneFactory extends Factory
{
    protected $model = HeatLane::class;

    public function definition(): array
    {
        return [
            'heat_id' => Heat::factory(),
            'lane_number' => fake()->numberBetween(1, 8),
            'registration_id' => Registration::factory(),
        ];
    }
}
