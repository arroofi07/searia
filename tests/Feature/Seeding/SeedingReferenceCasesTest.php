<?php

use App\Actions\RunSeeding;
use App\Enums\ClubStatus;
use App\Enums\ClubType;
use App\Enums\SeedingMode;
use App\Models\AgeGroup;
use App\Models\Athlete;
use App\Models\Club;
use App\Models\Heat;
use App\Models\HeatLane;
use App\Models\Registration;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('covers exact one heat for six entrants on six lanes', function () {
    [$competition, $event, $group, $registrations] = seedMeetWithEntrants(6);
    $heats = app(RunSeeding::class)->handle($competition, $event, $group);

    expect($heats)->toHaveCount(1);
    $byLane = $heats->first()->lanes()->get()->keyBy('lane_number');

    expect($byLane->keys()->sort()->values()->all())->toBe([1, 2, 3, 4, 5, 6])
        ->and($byLane[3]->registration_id)->toBe($registrations[0]->id)
        ->and($byLane[4]->registration_id)->toBe($registrations[1]->id)
        ->and($byLane[2]->registration_id)->toBe($registrations[2]->id)
        ->and($byLane[5]->registration_id)->toBe($registrations[3]->id)
        ->and($byLane[1]->registration_id)->toBe($registrations[4]->id)
        ->and($byLane[6]->registration_id)->toBe($registrations[5]->id);
});

it('leaves lane six empty for fifteen entrants on six lanes', function () {
    [$competition, $event, $group] = seedMeetWithEntrants(15);
    $heats = app(RunSeeding::class)->handle($competition, $event, $group);

    expect($heats->map(fn (Heat $heat) => $heat->lanes()->count())->all())->toBe([5, 5, 5]);
    foreach ($heats as $heat) {
        expect($heat->lanes()->where('lane_number', 6)->exists())->toBeFalse();
    }
});

it('puts all NT entrants into heat one when mixed with timed entrants', function () {
    [$competition, $event, $group] = seedMeetWithEntrants(12, timedCount: 8);
    $heats = app(RunSeeding::class)->handle($competition, $event, $group);

    $heatOneIds = $heats->firstWhere('heat_number', 1)->lanes()->pluck('registration_id');
    $ntIds = Registration::query()
        ->where('event_id', $event->id)
        ->where('age_group_id', $group->id)
        ->whereNull('seed_time_ms')
        ->pluck('id');

    expect($ntIds->diff($heatOneIds))->toBeEmpty();
});

it('never places an entrant twice and never overflows lane capacity', function () {
    foreach ([1, 5, 6, 11, 15, 17, 20, 33] as $count) {
        [$competition, $event, $group] = seedMeetWithEntrants($count, lanes: 8);
        $heats = app(RunSeeding::class)->handle($competition, $event, $group);
        $registrationIds = HeatLane::query()
            ->whereIn('heat_id', $heats->pluck('id'))
            ->pluck('registration_id');

        expect($registrationIds)->toHaveCount($count)
            ->and($registrationIds->unique())->toHaveCount($count);

        foreach ($heats as $heat) {
            expect($heat->lanes()->count())->toBeLessThanOrEqual(8);
        }
    }
});

it('matches the worked example lane names for series three', function () {
    $meet = openRegistrationMeet();
    $meet['competition']->update(['pool_lanes' => 6, 'seeding_mode' => SeedingMode::Balanced]);

    $roster = [
        ['Nikcholas Bryan S.', 'Bunda Swimming Club', 38120],
        ['Muhammad Ihsan A.', 'Swimming Sport Club', 39050],
        ['Lashira Anaesika', 'Painan Aquatic Club', 40440],
        ['Hazical Thaif Haris', 'SeaRIA Aquatic Padang', 41200],
        ['Heri Rafael Panggabean', 'SeaRIA Aquatic Padang', 42830],
        ['Muhammad Habibi Akbar', 'Homi Swimming Club', 43110],
        ['Naufal Sidqi Lumbantobing', 'Padang Swimming Club', 44600],
        ['Hayden Angelo Ghifari', 'Angkasa Swimming Club', 45050],
        ['Fathan Athaya A.', 'Swimming Sport Club B', 46770],
        ['Dhias Riawan', 'Rani Swimming Club', 48300],
        ['Arziwarna Rafiandra A.', 'Painan Jaya Swimming Club', 49150],
        ['Bintang Al Rafaeyza', 'SeaRIA Aquatic Padang B', 51020],
        ['Elyathan Halim', 'Rio Aquatic', null],
        ['Hansel Ali Ghufron', 'Alinafiqa Akuatik Padang', null],
        ['Abdullah Hanif R.', 'Get Fit Swimming', null],
    ];

    foreach ($roster as [$name, $clubName, $time]) {
        $club = Club::query()->firstOrCreate(
            ['name' => $clubName],
            [
                'city' => 'Padang',
                'type' => ClubType::Perkumpulan,
                'status' => ClubStatus::Verified,
                'is_active' => true,
            ],
        );
        $athlete = Athlete::factory()->create([
            'club_id' => $club->id,
            'full_name' => $name,
            'gender' => $meet['athlete']->gender,
            'birth_year' => 2016,
        ]);
        verifiedRegistration($meet, [
            'athlete_id' => $athlete->id,
            'seed_time_ms' => $time,
        ]);
    }

    $heats = app(RunSeeding::class)->handle($meet['competition']->fresh(), $meet['event'], $meet['group']);
    $heat3 = $heats->firstWhere('heat_number', 3)->load('lanes.registration.athlete');

    $namesByLane = $heat3->lanes->mapWithKeys(
        fn (HeatLane $lane) => [$lane->lane_number => $lane->registration?->athlete?->full_name],
    );

    expect($namesByLane[3])->toBe('Nikcholas Bryan S.')
        ->and($namesByLane[4])->toBe('Muhammad Ihsan A.')
        ->and($namesByLane[2])->toBe('Lashira Anaesika')
        ->and($namesByLane[5])->toBe('Hazical Thaif Haris')
        ->and($namesByLane[1])->toBe('Heri Rafael Panggabean');
});

it('seeds ten NT entrants into two reproducible heats', function () {
    [$competition, $event, $group] = seedMeetWithEntrants(10, timedCount: 0);
    $first = app(RunSeeding::class)->handle($competition, $event, $group);
    $layout = $first->mapWithKeys(fn (Heat $heat) => [
        $heat->heat_number => $heat->lanes()->orderBy('lane_number')->pluck('registration_id')->all(),
    ])->all();

    expect($first)->toHaveCount(2);

    $second = app(RunSeeding::class)->handle($competition->fresh(), $event, $group);
    $again = $second->mapWithKeys(fn (Heat $heat) => [
        $heat->heat_number => $heat->lanes()->orderBy('lane_number')->pluck('registration_id')->all(),
    ])->all();

    expect($again)->toBe($layout);
});

it('places a solitary entrant in lane three of a six-lane pool', function () {
    [$competition, $event, $group] = seedMeetWithEntrants(1);
    $heats = app(RunSeeding::class)->handle($competition, $event, $group);

    expect($heats)->toHaveCount(1);
    $lanes = $heats->first()->lanes()->get()->keyBy('lane_number');
    expect($lanes)->toHaveCount(1)
        ->and($lanes->keys()->all())->toBe([3]);
});

it('creates no heats when an age group has zero entrants', function () {
    $meet = openRegistrationMeet();
    $emptyGroup = AgeGroup::factory()->create([
        'competition_id' => $meet['competition']->id,
        'code' => '9',
        'name' => 'Group Empty',
        'birth_year_start' => 2008,
        'birth_year_end' => 2009,
        'sort_order' => 9,
    ]);
    $meet['event']->ageGroups()->attach($emptyGroup->id);

    $heats = app(RunSeeding::class)->handle($meet['competition'], $meet['event'], $emptyGroup);

    expect($heats)->toHaveCount(0);
});
