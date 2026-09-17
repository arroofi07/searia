<?php

use App\Enums\RegistrationStatus;
use App\Exceptions\CannotOverrideAgeGroupException;
use App\Models\ActivityLog;
use App\Models\Heat;
use App\Models\HeatLane;
use App\Models\User;
use App\Services\AgeGroupOverride;
use App\Services\AgeGroupResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('picks the nearest older group that is on the event matrix', function () {
    $meet = competeUpMeet();
    $service = new AgeGroupOverride(new AgeGroupResolver);

    $group = $service->nearestOlderEligible(
        $meet['competition'],
        $meet['athlete'],
        $meet['event']->load('ageGroups'),
    );

    expect($group?->is($meet['olderGroup']))->toBeTrue();
});

it('allows an athlete to compete in an older age group that is on the matrix', function () {
    $meet = competeUpMeet();
    $service = new AgeGroupOverride(new AgeGroupResolver);

    $group = $service->decide(
        $meet['competition'],
        $meet['athlete'],
        $meet['event']->load('ageGroups'),
        $meet['olderGroup'],
    );

    expect($group->is($meet['olderGroup']))->toBeTrue();
});

it('rejects competing down into a younger age group', function () {
    $meet = competeUpMeet();
    $meet['athlete']->update(['birth_year' => 2018]);
    $service = new AgeGroupOverride(new AgeGroupResolver);

    $service->decide(
        $meet['competition'],
        $meet['athlete']->fresh(),
        $meet['event']->load('ageGroups'),
        $meet['youngerGroup'],
    );
})->throws(CannotOverrideAgeGroupException::class, 'Turun kelas tidak diizinkan.');

it('rejects an older group that is missing from the event matrix', function () {
    $meet = competeUpMeet();
    $meet['event']->ageGroups()->detach($meet['olderGroup']->id);
    $service = new AgeGroupOverride(new AgeGroupResolver);

    $service->decide(
        $meet['competition'],
        $meet['athlete'],
        $meet['event']->load('ageGroups'),
        $meet['olderGroup'],
    );
})->throws(CannotOverrideAgeGroupException::class);

it('treats the natural group as a no-op override', function () {
    $meet = competeUpMeet();
    $service = new AgeGroupOverride(new AgeGroupResolver);

    $group = $service->decide(
        $meet['competition'],
        $meet['athlete'],
        $meet['event']->load('ageGroups'),
        $meet['youngerGroup'],
    );

    expect($group->is($meet['youngerGroup']))->toBeTrue();
});

it('moves a registration into the older group and writes an audit log', function () {
    $meet = competeUpMeet();
    $registration = verifiedRegistration($meet, ['age_group_id' => $meet['youngerGroup']->id]);
    $actor = User::factory()->panitia()->create();

    $updated = (new AgeGroupOverride(new AgeGroupResolver))->apply(
        $registration,
        $meet['olderGroup'],
        $actor,
        'Siap lawan 2018',
        '127.0.0.1',
    );

    $log = ActivityLog::query()->where('action', 'registration.age_group_override')->first();

    expect($updated->age_group_id)->toBe($meet['olderGroup']->id)
        ->and($updated->isAgeGroupOverride())->toBeTrue()
        ->and($meet['athlete']->fresh()->birth_year)->toBe(2019)
        ->and($log)->not->toBeNull()
        ->and($log->reason)->toBe('Siap lawan 2018')
        ->and($log->old_values['age_group_id'])->toBe($meet['youngerGroup']->id)
        ->and($log->new_values['age_group_id'])->toBe($meet['olderGroup']->id);
});

it('rejects an override after seeding for the pair is locked', function () {
    $meet = competeUpMeet();
    $registration = verifiedRegistration($meet, ['age_group_id' => $meet['youngerGroup']->id]);
    Heat::factory()->create([
        'event_id' => $meet['event']->id,
        'age_group_id' => $meet['youngerGroup']->id,
        'locked_at' => now(),
    ]);

    (new AgeGroupOverride(new AgeGroupResolver))->apply(
        $registration,
        $meet['olderGroup'],
        User::factory()->panitia()->create(),
        'Terlambat naik kelas',
    );
})->throws(CannotOverrideAgeGroupException::class, 'seeding untuk nomor ini sudah dikunci');

it('clears the current heat lane when the override is applied before lock', function () {
    $meet = competeUpMeet();
    $registration = verifiedRegistration($meet, ['age_group_id' => $meet['youngerGroup']->id]);
    $heat = Heat::factory()->create([
        'event_id' => $meet['event']->id,
        'age_group_id' => $meet['youngerGroup']->id,
        'locked_at' => null,
    ]);
    $lane = HeatLane::factory()->create([
        'heat_id' => $heat->id,
        'lane_number' => 4,
        'registration_id' => $registration->id,
    ]);

    (new AgeGroupOverride(new AgeGroupResolver))->apply(
        $registration,
        $meet['olderGroup'],
        User::factory()->panitia()->create(),
        'Pindah sebelum kunci',
    );

    expect($lane->fresh()->registration_id)->toBeNull()
        ->and($registration->fresh()->age_group_id)->toBe($meet['olderGroup']->id);
});

it('rejects a blank reason', function () {
    $meet = competeUpMeet();
    $registration = verifiedRegistration($meet, ['age_group_id' => $meet['youngerGroup']->id]);

    (new AgeGroupOverride(new AgeGroupResolver))->apply(
        $registration,
        $meet['olderGroup'],
        User::factory()->panitia()->create(),
        '   ',
    );
})->throws(CannotOverrideAgeGroupException::class, 'Alasan naik kelas wajib diisi.');

it('rejects overriding a withdrawn registration', function () {
    $meet = competeUpMeet();
    $registration = verifiedRegistration($meet, [
        'age_group_id' => $meet['youngerGroup']->id,
        'status' => RegistrationStatus::Withdrawn,
    ]);

    (new AgeGroupOverride(new AgeGroupResolver))->apply(
        $registration,
        $meet['olderGroup'],
        User::factory()->panitia()->create(),
        'Tetap naik',
    );
})->throws(CannotOverrideAgeGroupException::class, 'Entri yang dibatalkan tidak dapat dinaikkan kelas.');
