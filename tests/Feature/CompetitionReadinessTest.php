<?php

use App\Enums\Equipment;
use App\Enums\EventGender;
use App\Enums\Stroke;
use App\Models\AgeGroup;
use App\Models\Competition;
use App\Models\Event;
use App\Models\User;

it('lists an event without an age group as a readiness finding', function () {
    $competition = Competition::factory()->create(['fee_per_event' => 75_000]);
    AgeGroup::factory()->create([
        'competition_id' => $competition->id,
        'code' => '3',
        'name' => 'Group 3',
        'birth_year_start' => 2015,
        'birth_year_end' => 2016,
    ]);
    Event::factory()->create([
        'competition_id' => $competition->id,
        'event_number' => 13,
        'distance' => 50,
        'stroke' => Stroke::Breaststroke,
        'equipment' => Equipment::None,
        'gender' => EventGender::Male,
    ]);
    $panitia = User::factory()->panitia()->create();

    $this->actingAs($panitia)
        ->get(route('admin.competitions.readiness', $competition))
        ->assertOk()
        ->assertSee('tidak memiliki kelompok umur pada matriks kelayakan')
        ->assertSee('Buka pendaftaran');
});
