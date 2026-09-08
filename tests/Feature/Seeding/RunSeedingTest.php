<?php

use App\Actions\RunSeeding;
use App\Enums\RegistrationStatus;
use App\Exceptions\CannotReseedLockedHeatsException;
use App\Models\Athlete;
use App\Models\Heat;
use App\Models\HeatLane;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('seeds fifteen entrants into three heats of five', function () {
    [$competition, $event, $group] = seedMeetWithEntrants(15);

    $heats = app(RunSeeding::class)->handle($competition, $event, $group);

    expect($heats)->toHaveCount(3)
        ->and($heats->pluck('heat_number')->all())->toBe([1, 2, 3])
        ->and($heats->map(fn (Heat $heat) => $heat->lanes()->count())->all())->toBe([5, 5, 5]);
});

it('produces an identical layout when seeding twice', function () {
    [$competition, $event, $group] = seedMeetWithEntrants(15);
    $seeding = app(RunSeeding::class);

    $first = $seeding->handle($competition, $event, $group);
    $snapshot = HeatLane::query()
        ->whereIn('heat_id', $first->pluck('id'))
        ->with('heat')
        ->get()
        ->sortBy([
            fn (HeatLane $lane) => $lane->heat->heat_number,
            fn (HeatLane $lane) => $lane->lane_number,
        ])
        ->values()
        ->map(fn (HeatLane $lane) => [
            'heat_number' => $lane->heat->heat_number,
            'lane' => $lane->lane_number,
            'registration_id' => $lane->registration_id,
        ])
        ->all();

    $second = $seeding->handle($competition, $event, $group);
    $again = HeatLane::query()
        ->whereIn('heat_id', $second->pluck('id'))
        ->with('heat')
        ->get()
        ->sortBy([
            fn (HeatLane $lane) => $lane->heat->heat_number,
            fn (HeatLane $lane) => $lane->lane_number,
        ])
        ->values()
        ->map(fn (HeatLane $lane) => [
            'heat_number' => $lane->heat->heat_number,
            'lane' => $lane->lane_number,
            'registration_id' => $lane->registration_id,
        ])
        ->all();

    expect($again)->toBe($snapshot);
});

it('rejects reseeding locked heats without force', function () {
    [$competition, $event, $group] = seedMeetWithEntrants(6);
    $seeding = app(RunSeeding::class);
    $heats = $seeding->handle($competition, $event, $group);
    $heats->each->lock();

    $seeding->handle($competition, $event, $group);
})->throws(CannotReseedLockedHeatsException::class);

it('reseeds locked heats when force is true', function () {
    [$competition, $event, $group] = seedMeetWithEntrants(6);
    $seeding = app(RunSeeding::class);
    $seeding->handle($competition, $event, $group)->each->lock();

    $heats = $seeding->handle($competition, $event, $group, force: true);

    expect($heats)->toHaveCount(1)
        ->and($heats->first()->locked_at)->toBeNull();
});

it('seeds one thousand registrations in under ten seconds', function () {
    $meet = openRegistrationMeet();
    $meet['competition']->update(['pool_lanes' => 8]);
    $registrarId = $meet['panitia']->id;
    $rows = [];

    for ($i = 0; $i < 1000; $i++) {
        $athlete = Athlete::factory()->create([
            'club_id' => $meet['club']->id,
            'gender' => $meet['athlete']->gender,
            'birth_year' => 2016,
        ]);
        $rows[] = [
            'competition_id' => $meet['competition']->id,
            'event_id' => $meet['event']->id,
            'athlete_id' => $athlete->id,
            'age_group_id' => $meet['group']->id,
            'seed_time_ms' => 25_000 + $i,
            'status' => RegistrationStatus::Verified->value,
            'registered_by' => $registrarId,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    foreach (array_chunk($rows, 200) as $chunk) {
        DB::table('registrations')->insert($chunk);
    }

    $started = microtime(true);
    $heats = app(RunSeeding::class)->handle($meet['competition']->fresh(), $meet['event'], $meet['group']);
    $elapsed = microtime(true) - $started;

    expect($elapsed)->toBeLessThan(10)
        ->and($heats->sum(fn (Heat $heat) => $heat->lanes()->count()))->toBe(1000);
});
