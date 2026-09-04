<?php

namespace Database\Factories;

use App\Enums\Equipment;
use App\Enums\EventGender;
use App\Enums\Stroke;
use App\Models\Competition;
use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        return [
            'competition_id' => Competition::factory(),
            'event_number' => fake()->unique()->numberBetween(1, 900),
            'gender' => EventGender::Male,
            'distance' => 50,
            'stroke' => Stroke::Breaststroke,
            'equipment' => Equipment::None,
            'session' => 1,
            'sort_order' => fake()->numberBetween(1, 40),
            'is_active' => true,
        ];
    }
}
