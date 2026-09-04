<?php

use App\Enums\RegistrationStatus;
use App\Models\Athlete;
use App\Models\Registration;
use App\Notifications\RegistrationStatusChanged;
use Illuminate\Support\Facades\Notification;

it('approves one hundred pending entries in a single request', function () {
    $meet = openRegistrationMeet();
    $ids = [];

    for ($i = 0; $i < 100; $i++) {
        $athlete = Athlete::factory()->create([
            'club_id' => $meet['club']->id,
            'full_name' => 'ATLET BULK '.str_pad((string) $i, 3, '0', STR_PAD_LEFT),
            'gender' => $meet['athlete']->gender,
            'birth_year' => 2016,
        ]);
        $ids[] = Registration::factory()->create([
            'competition_id' => $meet['competition']->id,
            'event_id' => $meet['event']->id,
            'athlete_id' => $athlete->id,
            'age_group_id' => $meet['group']->id,
            'registered_by' => $meet['coach']->id,
            'status' => RegistrationStatus::Pending,
        ])->id;
    }

    $panitia = \App\Models\User::factory()->panitia()->create();

    $this->actingAs($panitia)
        ->post(route('admin.registrations.bulk-approve', $meet['competition']), [
            'registration_ids' => $ids,
        ])
        ->assertRedirect();

    expect(Registration::query()->where('status', RegistrationStatus::Verified)->count())->toBe(100)
        ->and(Registration::query()->whereNotNull('verified_by')->count())->toBe(100)
        ->and(Registration::query()->whereNotNull('verified_at')->count())->toBe(100);
});

it('rejects a rejection that has no reason', function () {
    $meet = openRegistrationMeet();
    $registration = Registration::factory()->create([
        'competition_id' => $meet['competition']->id,
        'event_id' => $meet['event']->id,
        'athlete_id' => $meet['athlete']->id,
        'age_group_id' => $meet['group']->id,
        'registered_by' => $meet['coach']->id,
        'status' => RegistrationStatus::Pending,
    ]);
    $panitia = \App\Models\User::factory()->panitia()->create();

    $this->actingAs($panitia)
        ->from(route('admin.registrations.index', $meet['competition']))
        ->patch(route('admin.registrations.reject', $registration), [])
        ->assertRedirect(route('admin.registrations.index', $meet['competition']))
        ->assertSessionHasErrors('rejection_reason');

    expect($registration->fresh()->status)->toBe(RegistrationStatus::Pending);
});

it('sends one status notification per club when approving in bulk', function () {
    Notification::fake();
    $meet = openRegistrationMeet();
    $secondAthlete = Athlete::factory()->create([
        'club_id' => $meet['club']->id,
        'gender' => $meet['athlete']->gender,
        'birth_year' => 2016,
    ]);
    $first = Registration::factory()->create([
        'competition_id' => $meet['competition']->id,
        'event_id' => $meet['event']->id,
        'athlete_id' => $meet['athlete']->id,
        'age_group_id' => $meet['group']->id,
        'registered_by' => $meet['coach']->id,
        'status' => RegistrationStatus::Pending,
    ]);
    $otherEvent = \App\Models\Event::factory()->create([
        'competition_id' => $meet['competition']->id,
        'event_number' => 15,
        'gender' => \App\Enums\EventGender::Male,
    ]);
    $second = Registration::factory()->create([
        'competition_id' => $meet['competition']->id,
        'event_id' => $otherEvent->id,
        'athlete_id' => $secondAthlete->id,
        'age_group_id' => $meet['group']->id,
        'registered_by' => $meet['coach']->id,
        'status' => RegistrationStatus::Pending,
    ]);
    $panitia = \App\Models\User::factory()->panitia()->create();

    $this->actingAs($panitia)
        ->post(route('admin.registrations.bulk-approve', $meet['competition']), [
            'registration_ids' => [$first->id, $second->id],
        ])
        ->assertRedirect();

    Notification::assertSentTo($meet['coach'], RegistrationStatusChanged::class);
    Notification::assertCount(1);
});
