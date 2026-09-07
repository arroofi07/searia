<?php

use App\Enums\RegistrationStatus;
use App\Models\Athlete;
use App\Models\Event;
use App\Services\InvoiceCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('adds a late fee on top of the base fee for entries after the normal deadline', function () {
    $meet = openRegistrationMeet();
    $meet['competition']->update([
        'fee_per_event' => 50_000,
        'late_fee_per_event' => 15_000,
        'registration_closes_at' => now()->subDay(),
    ]);

    $secondEvent = Event::factory()->create([
        'competition_id' => $meet['competition']->id,
        'event_number' => 15,
        'gender' => $meet['event']->gender,
        'distance' => $meet['event']->distance,
        'stroke' => $meet['event']->stroke,
    ]);
    $secondEvent->ageGroups()->attach($meet['group']->id);

    $onTime = verifiedRegistration($meet);
    $onTime->forceFill(['created_at' => now()->subDays(3)])->save();

    $late = verifiedRegistration($meet, [
        'event_id' => $secondEvent->id,
    ]);
    $late->forceFill(['created_at' => now()])->save();

    $calculation = app(InvoiceCalculator::class)->forClub($meet['competition']->fresh(), $meet['club']);

    expect($calculation->itemCount())->toBe(2)
        ->and($calculation->total())->toBe(115_000)
        ->and($calculation->lines[0]->isLate)->toBeFalse()
        ->and($calculation->lines[0]->subtotal())->toBe(50_000)
        ->and($calculation->lines[1]->isLate)->toBeTrue()
        ->and($calculation->lines[1]->lateFee)->toBe(15_000)
        ->and($calculation->lines[1]->subtotal())->toBe(65_000);
});

it('excludes rejected and withdrawn entries from the total', function () {
    $meet = openRegistrationMeet();
    $meet['competition']->update(['fee_per_event' => 50_000]);

    verifiedRegistration($meet);

    $rejectedAthlete = Athlete::factory()->create([
        'club_id' => $meet['club']->id,
        'gender' => $meet['athlete']->gender,
        'birth_year' => 2016,
    ]);
    $withdrawnAthlete = Athlete::factory()->create([
        'club_id' => $meet['club']->id,
        'gender' => $meet['athlete']->gender,
        'birth_year' => 2016,
    ]);

    verifiedRegistration($meet, [
        'athlete_id' => $rejectedAthlete->id,
        'status' => RegistrationStatus::Rejected,
    ]);
    verifiedRegistration($meet, [
        'athlete_id' => $withdrawnAthlete->id,
        'status' => RegistrationStatus::Withdrawn,
    ]);

    $calculation = app(InvoiceCalculator::class)->forClub($meet['competition'], $meet['club']);

    expect($calculation->itemCount())->toBe(1)
        ->and($calculation->total())->toBe(50_000);
});

it('does not add a late fee when late_fee_per_event is zero', function () {
    $meet = openRegistrationMeet();
    $meet['competition']->update([
        'fee_per_event' => 50_000,
        'late_fee_per_event' => 0,
        'registration_closes_at' => now()->subDay(),
    ]);

    $late = verifiedRegistration($meet);
    $late->forceFill(['created_at' => now()])->save();

    $calculation = app(InvoiceCalculator::class)->forClub($meet['competition']->fresh(), $meet['club']);

    expect($calculation->total())->toBe(50_000)
        ->and($calculation->lines[0]->lateFee)->toBe(0);
});
