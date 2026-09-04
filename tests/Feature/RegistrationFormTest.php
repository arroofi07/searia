<?php

use App\Enums\EventGender;
use App\Models\Event;
use App\Models\Registration;

it('stores the selected events in one three-step submission', function () {
    $meet = openRegistrationMeet();
    $second = Event::factory()->create([
        'competition_id' => $meet['competition']->id,
        'event_number' => 15,
        'gender' => EventGender::Male,
        'distance' => 50,
        'stroke' => \App\Enums\Stroke::Freestyle,
    ]);
    $second->ageGroups()->attach($meet['group']->id);

    $this->actingAs($meet['coach'])
        ->post(route('registrations.athlete', $meet['competition']), [
            'athlete_id' => $meet['athlete']->id,
        ])
        ->assertRedirect(route('registrations.events', $meet['competition']));

    $this->actingAs($meet['coach'])
        ->post(route('registrations.events.store', $meet['competition']), [
            'event_ids' => [$meet['event']->id, $second->id],
            'seed_times' => [
                $meet['event']->id => '00:52.20',
                $second->id => '',
            ],
        ])
        ->assertRedirect(route('registrations.review', $meet['competition']));

    $this->actingAs($meet['coach'])->get(route('registrations.review', $meet['competition']));

    $this->actingAs($meet['coach'])
        ->post(route('registrations.store', $meet['competition']), [
            'submit_token' => session('registration_token.'.$meet['competition']->id),
        ])
        ->assertRedirect(route('coach.registrations.index', $meet['competition']));

    expect(Registration::query()->count())->toBe(2)
        ->and(Registration::query()->where('seed_time_ms', 52_200)->count())->toBe(1)
        ->and(Registration::query()->whereNull('seed_time_ms')->count())->toBe(1);
});

it('rejects an ineligible event posted directly over HTTP', function () {
    $meet = openRegistrationMeet();
    $ineligible = Event::factory()->create([
        'competition_id' => $meet['competition']->id,
        'event_number' => 5,
        'gender' => EventGender::Male,
        'distance' => 50,
        'stroke' => \App\Enums\Stroke::Butterfly,
        'equipment' => \App\Enums\Equipment::Fins,
    ]);

    $token = 'token-ineligible';

    $this->actingAs($meet['coach'])
        ->withSession([
            'registration_wizard.'.$meet['competition']->id => [
                'athlete_id' => $meet['athlete']->id,
                'event_ids' => [$ineligible->id],
                'seed_times' => [],
            ],
            'registration_token.'.$meet['competition']->id => $token,
        ])
        ->post(route('registrations.store', $meet['competition']), [
            'submit_token' => $token,
            'athlete_id' => $meet['athlete']->id,
            'event_ids' => [$ineligible->id],
        ])
        ->assertSessionHasErrors('events');

    expect(Registration::query()->count())->toBe(0);
});
