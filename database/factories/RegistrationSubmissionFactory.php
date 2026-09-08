<?php

namespace Database\Factories;

use App\Models\Athlete;
use App\Models\Competition;
use App\Models\RegistrationSubmission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RegistrationSubmission>
 */
class RegistrationSubmissionFactory extends Factory
{
    protected $model = RegistrationSubmission::class;

    public function definition(): array
    {
        return [
            'competition_id' => Competition::factory(),
            'athlete_id' => Athlete::factory(),
            'code' => RegistrationSubmission::generateCode(),
            'registrant_name' => fake()->name(),
            'registrant_phone' => '0812'.fake()->numerify('########'),
            'registrant_email' => fake()->unique()->safeEmail(),
            'ip_address' => '127.0.0.1',
        ];
    }

    public function withoutEmail(): static
    {
        return $this->state(fn (): array => ['registrant_email' => null]);
    }
}
