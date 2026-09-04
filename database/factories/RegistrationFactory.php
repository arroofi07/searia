<?php

namespace Database\Factories;

use App\Models\Athlete;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Registration>
 */
class RegistrationFactory extends Factory
{
    protected $model = Registration::class;

    public function definition(): array
    {
        return [
            'competition_id' => 1,
            'event_id' => fake()->numberBetween(1, 200),
            'athlete_id' => Athlete::factory(),
            'age_group_id' => 1,
            'seed_time_ms' => fake()->optional(0.8)->numberBetween(20_000, 180_000),
            'status' => 'pending',
            'rejection_reason' => null,
            'registered_by' => User::factory(),
            'import_batch_id' => null,
            'invoice_id' => null,
            'verified_by' => null,
            'verified_at' => null,
        ];
    }
}
