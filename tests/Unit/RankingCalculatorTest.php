<?php

use App\Enums\DisqualificationCode;
use App\Enums\ResultStatus;
use App\Models\AgeGroup;
use App\Models\Athlete;
use App\Models\Club;
use App\Models\Event;
use App\Models\Heat;
use App\Models\HeatLane;
use App\Models\Registration;
use App\Models\Result;
use App\Models\User;
use App\Services\MedalTally;
use App\Services\RankingCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function rankingFixture(): array
{
    $meet = openRegistrationMeet();
    $competition = $meet['competition'];
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

    $makeLane = function (Heat $heat, int $lane, string $name, ?int $timeMs, ResultStatus $status = ResultStatus::Ok, ?int $seed = 40_000) use ($meet, $club, $recorder, $event, $group) {
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
            'coach' => $meet['coach'],
        ], ['seed_time_ms' => $seed]);

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

        return $athlete;
    };

    return compact('meet', 'competition', 'event', 'group', 'heat1', 'heat2', 'makeLane', 'recorder');
}

it('gives rank one to the fastest swimmer even from heat two', function () {
    $fx = rankingFixture();
    ($fx['makeLane'])($fx['heat1'], 1, 'HEAT ONE A', 36_000);
    ($fx['makeLane'])($fx['heat1'], 2, 'HEAT ONE B', 35_000);
    $best = ($fx['makeLane'])($fx['heat2'], 1, 'HEAT TWO BEST', 33_000);

    $table = app(RankingCalculator::class)->forEventAgeGroup($fx['event'], $fx['group']);

    expect($table->entries[0]->athleteName)->toBe('HEAT TWO BEST')
        ->and($table->entries[0]->rank)->toBe(1)
        ->and($table->entries[0]->athleteId)->toBe($best->id);
});

it('shares rank one on equal times and skips rank two', function () {
    $fx = rankingFixture();
    ($fx['makeLane'])($fx['heat1'], 1, 'TIE A', 34_000);
    ($fx['makeLane'])($fx['heat1'], 2, 'TIE B', 34_000);
    ($fx['makeLane'])($fx['heat2'], 1, 'THIRD', 35_000);

    $table = app(RankingCalculator::class)->forEventAgeGroup($fx['event'], $fx['group']);

    expect($table->entries[0]->rank)->toBe(1)
        ->and($table->entries[1]->rank)->toBe(1)
        ->and($table->entries[2]->rank)->toBe(3)
        ->and($table->entries[2]->athleteName)->toBe('THIRD');
});

it('lists dsq after ranked swimmers without a rank', function () {
    $fx = rankingFixture();
    ($fx['makeLane'])($fx['heat1'], 1, 'OK SWIMMER', 34_000);
    ($fx['makeLane'])($fx['heat1'], 2, 'DSQ SWIMMER', null, ResultStatus::Dsq);

    $table = app(RankingCalculator::class)->forEventAgeGroup($fx['event'], $fx['group']);

    expect($table->entries)->toHaveCount(2)
        ->and($table->entries[0]->rank)->toBe(1)
        ->and($table->entries[1]->athleteName)->toBe('DSQ SWIMMER')
        ->and($table->entries[1]->rank)->toBeNull()
        ->and($table->entries[1]->status)->toBe(ResultStatus::Dsq);
});

it('awards two golds and no silver when times tie for first', function () {
    $fx = rankingFixture();
    ($fx['makeLane'])($fx['heat1'], 1, 'TIE A', 34_000);
    ($fx['makeLane'])($fx['heat1'], 2, 'TIE B', 34_000);
    ($fx['makeLane'])($fx['heat2'], 1, 'THIRD', 36_000);

    $blocks = app(MedalTally::class)->forCompetition($fx['competition']->load('events'), app(RankingCalculator::class));
    $block = $blocks->first();

    expect($block['gold'])->toBe(2)
        ->and($block['silver'])->toBe(0)
        ->and($block['bronze'])->toBe(1);
});

it('flags age groups with fewer than three participants', function () {
    $fx = rankingFixture();
    ($fx['makeLane'])($fx['heat1'], 1, 'ONLY A', 34_000);
    ($fx['makeLane'])($fx['heat1'], 2, 'ONLY B', 35_000);

    $blocks = app(MedalTally::class)->forCompetition($fx['competition']->load('events'), app(RankingCalculator::class));

    expect($blocks->first()['small_field'])->toBeTrue();
});

it('rolls medal totals up by club and age group', function () {
    $fx = rankingFixture();
    ($fx['makeLane'])($fx['heat1'], 1, 'TIE A', 34_000);
    ($fx['makeLane'])($fx['heat1'], 2, 'TIE B', 34_000);
    ($fx['makeLane'])($fx['heat2'], 1, 'THIRD', 36_000);

    $tally = app(MedalTally::class);
    $blocks = $tally->forCompetition($fx['competition']->load('events'), app(RankingCalculator::class));
    $byClub = $tally->rollupClubs($blocks);
    $byAge = $tally->rollupAgeGroups($blocks);

    expect($byClub)->toHaveCount(1)
        ->and($byClub->first()['gold'])->toBe(2)
        ->and($byClub->first()['silver'])->toBe(0)
        ->and($byClub->first()['bronze'])->toBe(1)
        ->and($byAge)->toHaveCount(1)
        ->and($byAge->first()['age_group_name'])->toBe($fx['group']->name)
        ->and($byAge->first()['total'])->toBe(3);
});
