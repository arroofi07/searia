<?php

use App\Models\Heat;
use App\Models\HeatLane;
use App\Models\Registration;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('rejects two entrants on the same lane of one heat', function () {
    $heat = Heat::factory()->create();
    $first = Registration::factory()->create();
    $second = Registration::factory()->create([
        'competition_id' => $first->competition_id,
        'event_id' => $first->event_id + 1,
        'athlete_id' => \App\Models\Athlete::factory(),
        'age_group_id' => $first->age_group_id,
        'registered_by' => $first->registered_by,
    ]);

    HeatLane::factory()->create([
        'heat_id' => $heat->id,
        'lane_number' => 3,
        'registration_id' => $first->id,
    ]);

    HeatLane::factory()->create([
        'heat_id' => $heat->id,
        'lane_number' => 3,
        'registration_id' => $second->id,
    ]);
})->throws(UniqueConstraintViolationException::class);

it('rejects placing one registration on two lanes', function () {
    $heatA = Heat::factory()->create();
    $heatB = Heat::factory()->create([
        'event_id' => $heatA->event_id,
        'age_group_id' => $heatA->age_group_id,
        'heat_number' => 2,
    ]);
    $registration = Registration::factory()->create();

    HeatLane::factory()->create([
        'heat_id' => $heatA->id,
        'lane_number' => 3,
        'registration_id' => $registration->id,
    ]);

    HeatLane::factory()->create([
        'heat_id' => $heatB->id,
        'lane_number' => 4,
        'registration_id' => $registration->id,
    ]);
})->throws(UniqueConstraintViolationException::class);
