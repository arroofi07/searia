<?php

use App\Enums\Equipment;
use App\Enums\EventGender;
use App\Enums\RegistrationStatus;
use App\Enums\Stroke;
use App\Models\Event;
use App\Models\Heat;
use App\Models\HeatLane;
use App\Models\Registration;
use App\Models\User;

function extraMaleEvent(array $meet, int $number = 15, Stroke $stroke = Stroke::Freestyle): Event
{
    $event = Event::factory()->create([
        'competition_id' => $meet['competition']->id,
        'event_number' => $number,
        'gender' => EventGender::Male,
        'distance' => 50,
        'stroke' => $stroke,
        'equipment' => Equipment::None,
    ]);
    $event->ageGroups()->attach($meet['group']->id);

    return $event;
}

it('shows the athlete event numbers on the detail page', function () {
    $meet = openRegistrationMeet();
    verifiedRegistration($meet, ['seed_time_ms' => 45_000]);

    $this->actingAs($meet['panitia'])
        ->get(route('athletes.show', $meet['athlete']))
        ->assertOk()
        ->assertSee('Nomor lomba yang diikuti')
        ->assertSee('50 M Gaya Dada - Putra', false)
        ->assertSee($meet['competition']->name)
        ->assertSee('Tambah nomor lomba');
});

it('lets panitia add event numbers from the athlete detail page', function () {
    $meet = openRegistrationMeet();
    $second = extraMaleEvent($meet);

    $this->actingAs($meet['panitia'])
        ->from(route('athletes.show', ['athlete' => $meet['athlete'], 'competition_id' => $meet['competition']->id]))
        ->post(route('athletes.registrations.store', $meet['athlete']), [
            'competition_id' => $meet['competition']->id,
            'event_ids' => [$meet['event']->id, $second->id],
            'seed_times' => [
                $meet['event']->id => '00:45.00',
                $second->id => '00:48.00',
            ],
            'verify_now' => '1',
        ])
        ->assertRedirect(route('athletes.show', [
            'athlete' => $meet['athlete'],
            'competition_id' => $meet['competition']->id,
        ]));

    expect(Registration::query()->where('athlete_id', $meet['athlete']->id)->count())->toBe(2)
        ->and(Registration::query()->where('event_id', $meet['event']->id)->first()?->status)->toBe(RegistrationStatus::Verified)
        ->and(Registration::query()->where('event_id', $second->id)->first()?->seed_time_ms)->toBe(48_000);
});

it('rejects adding an event the athlete already entered', function () {
    $meet = openRegistrationMeet();
    verifiedRegistration($meet);

    $this->actingAs($meet['panitia'])
        ->from(route('athletes.show', $meet['athlete']))
        ->post(route('athletes.registrations.store', $meet['athlete']), [
            'competition_id' => $meet['competition']->id,
            'event_ids' => [$meet['event']->id],
            'verify_now' => '1',
        ])
        ->assertRedirect(route('athletes.show', $meet['athlete']))
        ->assertSessionHasErrors('registration');
});

it('lets panitia change the event number from the athlete detail page', function () {
    $meet = openRegistrationMeet();
    $registration = verifiedRegistration($meet, ['seed_time_ms' => 45_000]);
    $second = extraMaleEvent($meet);

    $this->actingAs($meet['panitia'])
        ->from(route('athletes.show', $meet['athlete']))
        ->put(route('athletes.registrations.update', [$meet['athlete'], $registration]), [
            'event_id' => $second->id,
            'seed_time' => '00:47.00',
        ])
        ->assertRedirect(route('athletes.show', $meet['athlete']));

    expect($registration->fresh()->event_id)->toBe($second->id)
        ->and($registration->fresh()->seed_time_ms)->toBe(47_000);
});

it('lets panitia update seed time without changing the event', function () {
    $meet = openRegistrationMeet();
    $registration = verifiedRegistration($meet, ['seed_time_ms' => 45_000]);

    $this->actingAs($meet['panitia'])
        ->put(route('athletes.registrations.update', [$meet['athlete'], $registration]), [
            'event_id' => $meet['event']->id,
            'seed_time' => '00:51.00',
        ])
        ->assertRedirect(route('athletes.show', $meet['athlete']));

    expect($registration->fresh()->event_id)->toBe($meet['event']->id)
        ->and($registration->fresh()->seed_time_ms)->toBe(51_000);
});

it('does not change the event after the athlete is placed in a heat', function () {
    $meet = openRegistrationMeet();
    $registration = verifiedRegistration($meet);
    $second = extraMaleEvent($meet);
    $heat = Heat::factory()->create([
        'event_id' => $meet['event']->id,
        'age_group_id' => $meet['group']->id,
    ]);
    HeatLane::factory()->create([
        'heat_id' => $heat->id,
        'registration_id' => $registration->id,
        'lane_number' => 4,
    ]);

    $this->actingAs($meet['panitia'])
        ->from(route('athletes.show', $meet['athlete']))
        ->put(route('athletes.registrations.update', [$meet['athlete'], $registration]), [
            'event_id' => $second->id,
            'seed_time' => '00:47.00',
        ])
        ->assertRedirect(route('athletes.show', $meet['athlete']))
        ->assertSessionHasErrors('event_id');

    expect($registration->fresh()->event_id)->toBe($meet['event']->id);
});

it('lets panitia withdraw an event number from the athlete detail page', function () {
    $meet = openRegistrationMeet();
    $registration = verifiedRegistration($meet);

    $this->actingAs($meet['panitia'])
        ->from(route('athletes.show', $meet['athlete']))
        ->delete(route('athletes.registrations.destroy', [$meet['athlete'], $registration]))
        ->assertRedirect(route('athletes.show', $meet['athlete']));

    expect($registration->fresh()->status)->toBe(RegistrationStatus::Withdrawn);
});

it('reactivates a withdrawn event instead of inserting a duplicate row', function () {
    $meet = openRegistrationMeet();
    $registration = verifiedRegistration($meet, ['status' => RegistrationStatus::Withdrawn]);

    $this->actingAs($meet['panitia'])
        ->post(route('athletes.registrations.store', $meet['athlete']), [
            'competition_id' => $meet['competition']->id,
            'event_ids' => [$meet['event']->id],
            'seed_times' => [$meet['event']->id => '00:44.00'],
            'verify_now' => '1',
        ])
        ->assertRedirect();

    expect(Registration::query()->where('athlete_id', $meet['athlete']->id)->count())->toBe(1)
        ->and($registration->fresh()->status)->toBe(RegistrationStatus::Verified)
        ->and($registration->fresh()->seed_time_ms)->toBe(44_000);
});

it('forbids juri from managing athlete event numbers', function () {
    $meet = openRegistrationMeet();
    $judge = User::factory()->juri()->create();

    $this->actingAs($judge)
        ->post(route('athletes.registrations.store', $meet['athlete']), [
            'competition_id' => $meet['competition']->id,
            'event_ids' => [$meet['event']->id],
        ])
        ->assertForbidden();
});
