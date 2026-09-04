<?php

use App\Enums\CompetitionStatus;
use App\Enums\RegistrationStatus;
use App\Models\Registration;

it('forbids a coach from changing an entry after registration has closed', function () {
    $meet = openRegistrationMeet();
    $registration = Registration::factory()->create([
        'competition_id' => $meet['competition']->id,
        'event_id' => $meet['event']->id,
        'athlete_id' => $meet['athlete']->id,
        'age_group_id' => $meet['group']->id,
        'registered_by' => $meet['coach']->id,
        'status' => RegistrationStatus::Pending,
        'seed_time_ms' => 52_200,
    ]);
    $meet['competition']->update(['status' => CompetitionStatus::Closed]);

    $this->actingAs($meet['coach'])
        ->put(route('registrations.update', $registration), [
            'seed_time' => '00:50.00',
        ])
        ->assertForbidden();

    $this->actingAs($meet['coach'])
        ->delete(route('registrations.destroy', $registration))
        ->assertForbidden();

    expect($registration->fresh()->seed_time_ms)->toBe(52_200)
        ->and($registration->fresh()->status)->toBe(RegistrationStatus::Pending);
});
