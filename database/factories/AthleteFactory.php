<?php

namespace Database\Factories;

use App\Enums\Gender;
use App\Models\Athlete;
use App\Models\Club;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Athlete>
 */
class AthleteFactory extends Factory
{
    protected $model = Athlete::class;

    public function definition(): array
    {
        return [
            'club_id' => Club::factory(),
            'full_name' => strtoupper(fake()->unique()->name()),
            'gender' => fake()->randomElement(Gender::cases()),
            'birth_year' => fake()->numberBetween(2008, 2018),
            'birth_date' => null,
            'identity_number' => null,
            'photo_path' => null,
            'is_active' => true,
        ];
    }
}
