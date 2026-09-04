<?php

namespace Database\Factories;

use App\Enums\ClubStatus;
use App\Enums\ClubType;
use App\Models\Club;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Club>
 */
class ClubFactory extends Factory
{
    protected $model = Club::class;

    public function definition(): array
    {
        $name = fake()->unique()->company().' Swimming Club';

        return [
            'name' => $name,
            'short_name' => strtoupper(fake()->lexify('???')),
            'type' => ClubType::Perkumpulan,
            'city' => fake()->city(),
            'province' => fake()->state(),
            'contact_name' => fake()->name(),
            'contact_phone' => fake()->numerify('08##########'),
            'logo_path' => null,
            'status' => ClubStatus::Verified,
            'rejection_reason' => null,
            'is_active' => true,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (): array => [
            'status' => ClubStatus::Pending,
        ]);
    }

    public function rejected(string $reason = 'Data klub tidak lengkap.'): static
    {
        return $this->state(fn (): array => [
            'status' => ClubStatus::Rejected,
            'rejection_reason' => $reason,
        ]);
    }

    public function sekolah(): static
    {
        return $this->state(fn (): array => [
            'type' => ClubType::Sekolah,
        ]);
    }
}
