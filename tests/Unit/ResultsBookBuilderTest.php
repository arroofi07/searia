<?php

use App\Enums\DisqualificationCode;
use App\Enums\ResultStatus;
use App\Models\Athlete;
use App\Models\Heat;
use App\Models\HeatLane;
use App\Models\Result;
use App\Models\User;
use App\Services\ResultsBookBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('merges heats into one ranking table ordered by place', function () {
    $meet = openRegistrationMeet();
    $event = $meet['event'];
    $group = $meet['group'];
    $club = $meet['club'];
    $recorder = User::factory()->panitia()->create();

    $heat1 = Heat::factory()->create([
        'event_id' => $event->id,
        'age_group_id' => $group->id,
        'heat_number' => 1,
        'results_locked_at' => now(),
    ]);
    $heat2 = Heat::factory()->create([
        'event_id' => $event->id,
        'age_group_id' => $group->id,
        'heat_number' => 2,
        'results_locked_at' => now(),
    ]);

    $makeLane = function (Heat $heat, int $lane, string $name, ?int $timeMs, ResultStatus $status = ResultStatus::Ok) use ($meet, $club, $recorder, $event, $group) {
        $athlete = Athlete::factory()->create([
            'club_id' => $club->id,
            'full_name' => $name,
            'gender' => $meet['athlete']->gender,
            'birth_year' => 2016,
        ]);
        $registration = verifiedRegistration([
            'competition' => $meet['competition'],
            'event' => $event,
            'athlete' => $athlete,
            'group' => $group,
            'panitia' => $meet['panitia'],
        ], ['seed_time_ms' => 40_000]);

        $heatLane = HeatLane::factory()->create([
            'heat_id' => $heat->id,
            'lane_number' => $lane,
            'registration_id' => $registration->id,
        ]);

        Result::factory()->create([
            'heat_lane_id' => $heatLane->id,
            'time_ms' => $timeMs,
            'status' => $status,
            'dsq_code' => $status === ResultStatus::Dsq ? DisqualificationCode::Sf : null,
            'recorded_by' => $recorder->id,
            'verified_at' => now(),
            'verified_by' => $recorder->id,
        ]);
    };

    $makeLane($heat1, 1, 'HEAT ONE SLOWER', 36_000);
    $makeLane($heat1, 3, 'DSQ SWIMMER', null, ResultStatus::Dsq);
    $makeLane($heat2, 4, 'HEAT TWO WINNER', 33_000);
    $makeLane($heat2, 5, 'HEAT TWO SECOND', 34_000);

    $document = app(ResultsBookBuilder::class)->build($meet['competition']->fresh());
    $event = $document->sessions[0]->events[0];

    expect($event->title())->toContain('Nomor '.$meet['event']->event_number)
        ->and($event->title())->toContain('Putra')
        ->and($event->title())->toContain('Group 3')
        ->and($event->title())->not->toContain('EVENT')
        ->and($event->lanes)->toHaveCount(4)
        ->and(collect($event->lanes)->pluck('athleteName')->all())->toBe([
            'HEAT TWO WINNER',
            'HEAT TWO SECOND',
            'HEAT ONE SLOWER',
            'DSQ SWIMMER',
        ])
        ->and(collect($event->lanes)->pluck('rank')->all())->toBe([1, 2, 3, null])
        ->and($event->lanes[0]->medalMark(1))->toBe('gold')
        ->and($event->lanes[0]->medalMark(2))->toBe('')
        ->and($event->lanes[1]->medalMark(2))->toBe('silver')
        ->and($event->lanes[2]->medalMark(3))->toBe('bronze')
        ->and($event->lanes[3]->medalMark(1))->toBe('')
        ->and($event->lanes[0]->age)->toBe(2016)
        ->and(collect($event->lanes)->every(fn ($lane) => $lane->registrationId !== null))->toBeTrue();

    $html = view('pdf.results-book', [
        'document' => $document,
        'competitionName' => $document->competitionName,
        'venue' => $document->venue,
        'city' => $document->city,
        'dateLabel' => $document->dateLabel,
        'printedAt' => $document->printedAt,
        'includeCover' => false,
    ])->render();

    expect($html)->toContain('images/medals/gold.svg')
        ->and($html)->toContain('images/medals/silver.svg')
        ->and($html)->toContain('images/medals/bronze.svg')
        ->and($html)->not->toContain('✓');
});

it('splits ranking tables per age group within an event', function () {
    $meet = openRegistrationMeet();
    $event = $meet['event'];
    $young = $meet['group'];
    $club = $meet['club'];
    $recorder = User::factory()->panitia()->create();

    $older = \App\Models\AgeGroup::factory()->create([
        'competition_id' => $meet['competition']->id,
        'code' => '5',
        'name' => 'Group 5',
        'display_code' => 'V',
        'birth_year_start' => 2010,
        'birth_year_end' => 2011,
        'sort_order' => 5,
    ]);
    $event->ageGroups()->attach($older->id);

    $makeLane = function (Heat $heat, string $name, int $birthYear, \App\Models\AgeGroup $group, int $timeMs) use ($meet, $club, $recorder, $event) {
        $athlete = Athlete::factory()->create([
            'club_id' => $club->id,
            'full_name' => $name,
            'gender' => $meet['athlete']->gender,
            'birth_year' => $birthYear,
        ]);
        $registration = verifiedRegistration([
            'competition' => $meet['competition'],
            'event' => $event,
            'athlete' => $athlete,
            'group' => $group,
            'panitia' => $meet['panitia'],
        ], ['seed_time_ms' => 40_000, 'age_group_id' => $group->id]);

        $heatLane = HeatLane::factory()->create([
            'heat_id' => $heat->id,
            'lane_number' => 4,
            'registration_id' => $registration->id,
        ]);

        Result::factory()->create([
            'heat_lane_id' => $heatLane->id,
            'time_ms' => $timeMs,
            'status' => ResultStatus::Ok,
            'recorded_by' => $recorder->id,
            'verified_at' => now(),
            'verified_by' => $recorder->id,
        ]);
    };

    $heatYoung = Heat::factory()->create([
        'event_id' => $event->id,
        'age_group_id' => $young->id,
        'heat_number' => 1,
        'results_locked_at' => now(),
    ]);
    $heatOlder = Heat::factory()->create([
        'event_id' => $event->id,
        'age_group_id' => $older->id,
        'heat_number' => 1,
        'results_locked_at' => now(),
    ]);

    $makeLane($heatYoung, 'KU 3 JUARA', 2016, $young, 36_000);
    $makeLane($heatOlder, 'KU 5 JUARA', 2011, $older, 32_000);

    $document = app(ResultsBookBuilder::class)->build($meet['competition']->fresh());
    $blocks = $document->sessions[0]->events;

    expect($blocks)->toHaveCount(2)
        ->and($blocks[0]->ageGroupName)->toBe($young->name)
        ->and($blocks[1]->ageGroupName)->toBe($older->name)
        ->and($blocks[0]->lanes)->toHaveCount(1)
        ->and($blocks[1]->lanes)->toHaveCount(1)
        ->and($blocks[0]->lanes[0]->athleteName)->toBe('KU 3 JUARA')
        ->and($blocks[1]->lanes[0]->athleteName)->toBe('KU 5 JUARA')
        ->and($blocks[0]->lanes[0]->rank)->toBe(1)
        ->and($blocks[1]->lanes[0]->rank)->toBe(1)
        ->and($blocks[0]->lanes[0]->medalMark(1))->toBe('gold')
        ->and($blocks[1]->lanes[0]->medalMark(1))->toBe('gold');
});
