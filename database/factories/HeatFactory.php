<?php

namespace Database\Factories;

use App\Models\AgeGroup;
use App\Models\Event;
use App\Models\Heat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Heat>
 */
class HeatFactory extends Factory
{
    protected $model = Heat::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'age_group_id' => AgeGroup::factory(),
            'heat_number' => 1,
            'round' => 'final',
            'scheduled_at' => null,
            'status' => 'pending',
            'locked_at' => null,
            'seeded_at' => now(),
        ];
    }
}
