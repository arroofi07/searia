<?php

namespace Database\Factories;

use App\Models\AgeGroup;
use App\Models\Competition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AgeGroup>
 */
class AgeGroupFactory extends Factory
{
    protected $model = AgeGroup::class;

    public function definition(): array
    {
        $end = fake()->numberBetween(2012, 2018);
        $code = (string) fake()->unique()->numberBetween(1, 99);

        return [
            'competition_id' => Competition::factory(),
            'code' => $code,
            'name' => 'Group '.$code,
            'display_code' => $code,
            'birth_year_start' => $end - 1,
            'birth_year_end' => $end,
            'sort_order' => (int) $code,
        ];
    }
}
