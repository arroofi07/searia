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
    $ageGroup = $document->sessions[0]->events[0]->ageGroups[0];

    expect($ageGroup->lanes)->toHaveCount(4)
        ->and(collect($ageGroup->lanes)->pluck('athleteName')->all())->toBe([
            'HEAT TWO WINNER',
            'HEAT TWO SECOND',
            'HEAT ONE SLOWER',
            'DSQ SWIMMER',
        ])
        ->and(collect($ageGroup->lanes)->pluck('rank')->all())->toBe([1, 2, 3, null])
        ->and(collect($ageGroup->lanes)->every(fn ($lane) => $lane->registrationId !== null))->toBeTrue();
});
