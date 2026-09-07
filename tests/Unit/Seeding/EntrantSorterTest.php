<?php

use App\Models\Athlete;
use App\Models\Club;
use App\Services\Seeding\EntrantSorter;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('orders equal seed times alphabetically by club then athlete', function () {
    $meet = openRegistrationMeet();
    $clubA = Club::factory()->create(['name' => 'Alpha Club']);
    $clubB = Club::factory()->create(['name' => 'Beta Club']);

    $athleteB = Athlete::factory()->create([
        'club_id' => $clubB->id,
        'full_name' => 'Zara Athlete',
        'gender' => $meet['athlete']->gender,
        'birth_year' => 2016,
    ]);
    $athleteA = Athlete::factory()->create([
        'club_id' => $clubA->id,
        'full_name' => 'Adam Athlete',
        'gender' => $meet['athlete']->gender,
        'birth_year' => 2016,
    ]);

    $beta = verifiedRegistration($meet, [
        'athlete_id' => $athleteB->id,
        'seed_time_ms' => 40_000,
    ]);
    $alpha = verifiedRegistration($meet, [
        'athlete_id' => $athleteA->id,
        'seed_time_ms' => 40_000,
    ]);

    $sorted = app(EntrantSorter::class)->sort(
        collect([$beta->fresh(['athlete.club']), $alpha->fresh(['athlete.club'])]),
        $meet['competition']->id,
        $meet['event']->id,
        $meet['group']->id,
    );

    expect($sorted->pluck('id')->all())->toBe([$alpha->id, $beta->id]);
});

it('keeps NT shuffle identical across one hundred runs', function () {
    $meet = openRegistrationMeet();
    $registrations = collect();

    for ($i = 0; $i < 10; $i++) {
        $athlete = Athlete::factory()->create([
            'club_id' => $meet['club']->id,
            'gender' => $meet['athlete']->gender,
            'birth_year' => 2016,
            'full_name' => 'NT ATHLETE '.$i,
        ]);
        $registrations->push(verifiedRegistration($meet, [
            'athlete_id' => $athlete->id,
            'seed_time_ms' => null,
        ])->fresh(['athlete.club']));
    }

    $sorter = app(EntrantSorter::class);
    $first = $sorter->sort(
        $registrations,
        $meet['competition']->id,
        $meet['event']->id,
        $meet['group']->id,
    )->pluck('id')->all();

    for ($run = 0; $run < 100; $run++) {
        $again = $sorter->sort(
            $registrations->shuffle()->values(),
            $meet['competition']->id,
            $meet['event']->id,
            $meet['group']->id,
        )->pluck('id')->all();

        expect($again)->toBe($first);
    }
});
