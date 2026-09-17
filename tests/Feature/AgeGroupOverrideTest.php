<?php

use App\Actions\RunSeeding;
use App\Enums\RegistrationStatus;
use App\Models\ActivityLog;
use App\Models\Heat;
use App\Models\HeatLane;
use App\Models\Registration;
use App\Models\User;

it('lets panitia create a manual entry that competes up', function () {
    $meet = competeUpMeet();
    $panitia = User::factory()->panitia()->create();

    $this->actingAs($panitia)
        ->post(route('admin.registrations.store', $meet['competition']), [
            'athlete_id' => $meet['athlete']->id,
            'event_id' => $meet['event']->id,
            'seed_time' => '00:45.00',
            'verify_now' => '1',
            'age_group_id' => $meet['olderGroup']->id,
            'override_reason' => 'Anak siap lawan 2018',
        ])
        ->assertRedirect(route('admin.registrations.index', $meet['competition']));

    $registration = Registration::query()->first();
    $log = ActivityLog::query()->where('action', 'registration.age_group_override')->first();

    expect($registration)->not->toBeNull()
        ->and($registration->age_group_id)->toBe($meet['olderGroup']->id)
        ->and($registration->status)->toBe(RegistrationStatus::Verified)
        ->and($registration->isAgeGroupOverride())->toBeTrue()
        ->and($meet['athlete']->fresh()->birth_year)->toBe(2019)
        ->and($log)->not->toBeNull()
        ->and($log->reason)->toBe('Anak siap lawan 2018');
});

it('lets panitia move an existing entry up an age group', function () {
    $meet = competeUpMeet();
    $registration = verifiedRegistration($meet, [
        'age_group_id' => $meet['youngerGroup']->id,
        'status' => RegistrationStatus::Pending,
    ]);
    $panitia = User::factory()->panitia()->create();

    $this->actingAs($panitia)
        ->from(route('admin.registrations.index', $meet['competition']))
        ->patch(route('admin.registrations.override-age-group', $registration), [
            'age_group_id' => $meet['olderGroup']->id,
            'reason' => 'Permintaan pelatih',
        ])
        ->assertRedirect(route('admin.registrations.index', $meet['competition']));

    expect($registration->fresh()->age_group_id)->toBe($meet['olderGroup']->id)
        ->and(ActivityLog::query()->where('action', 'registration.age_group_override')->count())->toBe(1);
});

it('rejects competing down through the admin form', function () {
    $meet = competeUpMeet();
    $meet['athlete']->update(['birth_year' => 2018]);
    $registration = verifiedRegistration($meet, [
        'age_group_id' => $meet['olderGroup']->id,
        'status' => RegistrationStatus::Pending,
    ]);
    $panitia = User::factory()->panitia()->create();

    $this->actingAs($panitia)
        ->from(route('admin.registrations.index', $meet['competition']))
        ->patch(route('admin.registrations.override-age-group', $registration), [
            'age_group_id' => $meet['youngerGroup']->id,
            'reason' => 'Minta turun',
        ])
        ->assertRedirect(route('admin.registrations.index', $meet['competition']))
        ->assertSessionHasErrors('age_group_id');

    expect($registration->fresh()->age_group_id)->toBe($meet['olderGroup']->id);
});

it('requires a reason when competing up', function () {
    $meet = competeUpMeet();
    $registration = verifiedRegistration($meet, [
        'age_group_id' => $meet['youngerGroup']->id,
        'status' => RegistrationStatus::Pending,
    ]);
    $panitia = User::factory()->panitia()->create();

    $this->actingAs($panitia)
        ->from(route('admin.registrations.index', $meet['competition']))
        ->patch(route('admin.registrations.override-age-group', $registration), [
            'age_group_id' => $meet['olderGroup']->id,
        ])
        ->assertRedirect(route('admin.registrations.index', $meet['competition']))
        ->assertSessionHasErrors('reason');
});

it('rejects an override after the event age group heats are locked', function () {
    $meet = competeUpMeet();
    $registration = verifiedRegistration($meet, ['age_group_id' => $meet['youngerGroup']->id]);
    Heat::factory()->create([
        'event_id' => $meet['event']->id,
        'age_group_id' => $meet['olderGroup']->id,
        'locked_at' => now(),
    ]);
    $panitia = User::factory()->panitia()->create();

    $this->actingAs($panitia)
        ->from(route('admin.registrations.index', $meet['competition']))
        ->patch(route('admin.registrations.override-age-group', $registration), [
            'age_group_id' => $meet['olderGroup']->id,
            'reason' => 'Terlambat',
        ])
        ->assertRedirect(route('admin.registrations.index', $meet['competition']))
        ->assertSessionHasErrors('age_group_id');

    expect($registration->fresh()->age_group_id)->toBe($meet['youngerGroup']->id);
});

it('seeds a compete-up entry into the older age group heats', function () {
    $meet = competeUpMeet();
    $meet['competition']->update(['pool_lanes' => 6]);
    $registration = verifiedRegistration($meet, [
        'age_group_id' => $meet['olderGroup']->id,
        'seed_time_ms' => 32_000,
    ]);

    $heats = app(RunSeeding::class)->handle($meet['competition'], $meet['event'], $meet['olderGroup']);

    $seededIds = HeatLane::query()
        ->whereIn('heat_id', $heats->pluck('id'))
        ->pluck('registration_id');

    expect($heats)->not->toBeEmpty()
        ->and($seededIds)->toContain($registration->id)
        ->and(Heat::query()->where('age_group_id', $meet['youngerGroup']->id)->count())->toBe(0);
});

it('forbids judges from overriding an age group', function () {
    $meet = competeUpMeet();
    $registration = verifiedRegistration($meet, ['age_group_id' => $meet['youngerGroup']->id]);
    $judge = User::factory()->juri()->create();

    $this->actingAs($judge)
        ->patch(route('admin.registrations.override-age-group', $registration), [
            'age_group_id' => $meet['olderGroup']->id,
            'reason' => 'Tidak boleh',
        ])
        ->assertForbidden();
});

it('shows the naik kelas menu with existing overrides and candidates', function () {
    $meet = competeUpMeet();
    $promoted = verifiedRegistration($meet, [
        'age_group_id' => $meet['olderGroup']->id,
        'status' => RegistrationStatus::Pending,
    ]);
    $candidateAthlete = \App\Models\Athlete::factory()->create([
        'club_id' => $meet['club']->id,
        'gender' => $meet['athlete']->gender,
        'birth_year' => 2019,
        'full_name' => 'KANDIDAT NAIK KELAS',
    ]);
    verifiedRegistration($meet, [
        'athlete_id' => $candidateAthlete->id,
        'age_group_id' => $meet['youngerGroup']->id,
        'status' => RegistrationStatus::Pending,
    ]);
    $panitia = User::factory()->panitia()->create();

    $this->actingAs($panitia)
        ->get(route('admin.age-group-promotions.index', $meet['competition']))
        ->assertOk()
        ->assertSee('Naik kelas')
        ->assertSee($promoted->athlete->full_name)
        ->assertSee('KANDIDAT NAIK KELAS')
        ->assertSee('Saran: Group 7');
});

it('lets panitia promote a candidate from the naik kelas menu', function () {
    $meet = competeUpMeet();
    $registration = verifiedRegistration($meet, [
        'age_group_id' => $meet['youngerGroup']->id,
        'status' => RegistrationStatus::Pending,
    ]);
    $panitia = User::factory()->panitia()->create();

    $this->actingAs($panitia)
        ->from(route('admin.age-group-promotions.index', $meet['competition']))
        ->patch(route('admin.registrations.override-age-group', $registration), [
            'age_group_id' => $meet['olderGroup']->id,
            'reason' => 'Siap lawan grup 7',
        ])
        ->assertRedirect(route('admin.age-group-promotions.index', $meet['competition']));

    expect($registration->fresh()->age_group_id)->toBe($meet['olderGroup']->id);
});

it('forbids judges from opening the naik kelas menu', function () {
    $meet = competeUpMeet();
    $judge = User::factory()->juri()->create();

    $this->actingAs($judge)
        ->get(route('admin.age-group-promotions.index', $meet['competition']))
        ->assertForbidden();
});
