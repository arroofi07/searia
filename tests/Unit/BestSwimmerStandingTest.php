<?php

use App\Enums\ClubStatus;
use App\Enums\Equipment;
use App\Enums\EventGender;
use App\Enums\Gender;
use App\Enums\ResultStatus;
use App\Enums\Stroke;
use App\Models\Athlete;
use App\Models\Club;
use App\Models\Event;
use App\Models\Heat;
use App\Models\HeatLane;
use App\Models\Result;
use App\Models\User;
use App\Services\BestSwimmerStanding;
use App\Services\MedalTally;
use App\Services\RankingCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('picks the swimmer with more golds as best in the age group', function () {
    $meet = openRegistrationMeet();
    $competition = $meet['competition'];
    $group = $meet['group'];
    $club = $meet['club'];
    $recorder = User::factory()->panitia()->create();
    $lane = 0;

    $addResult = function (Event $event, Athlete $athlete, int $timeMs) use (&$lane, $meet, $group, $recorder) {
        $lane++;
        $heat = Heat::query()->firstOrCreate(
            [
                'event_id' => $event->id,
                'age_group_id' => $group->id,
                'heat_number' => 1,
            ],
            ['results_locked_at' => now(), 'round' => 'final', 'status' => 'finished'],
        );
        if ($heat->results_locked_at === null) {
            $heat->update(['results_locked_at' => now()]);
        }

        $registration = verifiedRegistration([
            'competition' => $meet['competition'],
            'event' => $event,
            'athlete' => $athlete,
            'group' => $group,
            'panitia' => $meet['panitia'],
        ], ['seed_time_ms' => 40_000]);

        $heatLane = HeatLane::factory()->create([
            'heat_id' => $heat->id,
            'lane_number' => HeatLane::query()->where('heat_id', $heat->id)->count() + 1,
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

    $best = Athlete::factory()->create([
        'club_id' => $club->id,
        'full_name' => 'BEST SWIMMER',
        'gender' => Gender::Male,
        'birth_year' => 2016,
        'identity_number' => '41182',
    ]);
    $second = Athlete::factory()->create([
        'club_id' => $club->id,
        'full_name' => 'SECOND SWIMMER',
        'gender' => Gender::Male,
        'birth_year' => 2016,
    ]);
    $anchor = Athlete::factory()->create([
        'club_id' => $club->id,
        'full_name' => 'ANCHOR SWIMMER',
        'gender' => Gender::Male,
        'birth_year' => 2016,
    ]);

    $firstEvent = $meet['event'];
    $addResult($firstEvent, $best, 30_000);
    $addResult($firstEvent, $second, 31_000);
    $addResult($firstEvent, $anchor, 32_000);

    $secondEvent = Event::factory()->create([
        'competition_id' => $competition->id,
        'event_number' => 14,
        'gender' => EventGender::Male,
        'distance' => 50,
        'stroke' => Stroke::Freestyle,
        'equipment' => Equipment::None,
    ]);
    $secondEvent->ageGroups()->attach($group->id);
    $addResult($secondEvent, $best, 29_000);
    $addResult($secondEvent, $second, 30_000);
    $addResult($secondEvent, $anchor, 31_000);

    $groups = app(BestSwimmerStanding::class)->forCompetition(
        $competition->fresh(),
        app(MedalTally::class),
        app(RankingCalculator::class),
    );

    expect($groups)->toHaveCount(1);
    $winners = $groups->first()['winners'];
    expect($winners)->toHaveCount(1)
        ->and($winners[0]['athlete_id'])->toBe($best->id)
        ->and($winners[0]['identity_number'])->toBe('41182')
        ->and($winners[0]['gold'])->toBe(2)
        ->and($winners[0]['points'])->toBe(10)
        ->and($groups->first()['athletes'][1]['athlete_id'])->toBe($second->id)
        ->and($groups->first()['athletes'][1]['position'])->toBe(2);
});

it('lists a putra and putri winner and keeps ties at position one', function () {
    $meet = openRegistrationMeet();
    $competition = $meet['competition'];
    $group = $meet['group'];
    $club = $meet['club'];
    $otherClub = Club::factory()->create(['status' => ClubStatus::Verified, 'name' => 'Other SC', 'city' => 'Medan']);
    $recorder = User::factory()->panitia()->create();

    $heatFor = function (Event $event) use ($group): Heat {
        return Heat::query()->firstOrCreate(
            [
                'event_id' => $event->id,
                'age_group_id' => $group->id,
                'heat_number' => 1,
            ],
            ['results_locked_at' => now(), 'round' => 'final', 'status' => 'finished'],
        );
    };

    $addResult = function (Event $event, Athlete $athlete, int $timeMs) use ($meet, $group, $recorder, $heatFor) {
        $heat = $heatFor($event);
        $registration = verifiedRegistration([
            'competition' => $meet['competition'],
            'event' => $event,
            'athlete' => $athlete,
            'group' => $group,
            'panitia' => $meet['panitia'],
        ], ['seed_time_ms' => 40_000]);
        $heatLane = HeatLane::factory()->create([
            'heat_id' => $heat->id,
            'lane_number' => HeatLane::query()->where('heat_id', $heat->id)->count() + 1,
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

    $maleA = Athlete::factory()->create([
        'club_id' => $club->id,
        'full_name' => 'MALE TIE A',
        'gender' => Gender::Male,
        'birth_year' => 2016,
    ]);
    $maleB = Athlete::factory()->create([
        'club_id' => $otherClub->id,
        'full_name' => 'MALE TIE B',
        'gender' => Gender::Male,
        'birth_year' => 2016,
    ]);
    $maleThird = Athlete::factory()->create([
        'club_id' => $club->id,
        'full_name' => 'MALE THIRD',
        'gender' => Gender::Male,
        'birth_year' => 2016,
    ]);
    $female = Athlete::factory()->create([
        'club_id' => $club->id,
        'full_name' => 'FEMALE BEST',
        'gender' => Gender::Female,
        'birth_year' => 2016,
    ]);
    $femaleSecond = Athlete::factory()->create([
        'club_id' => $otherClub->id,
        'full_name' => 'FEMALE SECOND',
        'gender' => Gender::Female,
        'birth_year' => 2016,
    ]);
    $femaleThird = Athlete::factory()->create([
        'club_id' => $club->id,
        'full_name' => 'FEMALE THIRD',
        'gender' => Gender::Female,
        'birth_year' => 2016,
    ]);

    $maleEvent = $meet['event'];
    $addResult($maleEvent, $maleA, 30_000);
    $addResult($maleEvent, $maleB, 30_000);
    $addResult($maleEvent, $maleThird, 32_000);

    $femaleEvent = Event::factory()->create([
        'competition_id' => $competition->id,
        'event_number' => 15,
        'gender' => EventGender::Female,
        'distance' => 50,
        'stroke' => Stroke::Freestyle,
        'equipment' => Equipment::None,
    ]);
    $femaleEvent->ageGroups()->attach($group->id);
    $addResult($femaleEvent, $female, 31_000);
    $addResult($femaleEvent, $femaleSecond, 32_000);
    $addResult($femaleEvent, $femaleThird, 33_000);

    $groups = app(BestSwimmerStanding::class)->forCompetition(
        $competition->fresh(),
        app(MedalTally::class),
        app(RankingCalculator::class),
    );

    expect($groups)->toHaveCount(2)
        ->and($groups[0]['gender'])->toBe(Gender::Male)
        ->and($groups[0]['winners'])->toHaveCount(2)
        ->and(collect($groups[0]['winners'])->pluck('athlete_name')->all())->toEqualCanonicalizing(['MALE TIE A', 'MALE TIE B'])
        ->and($groups[1]['gender'])->toBe(Gender::Female)
        ->and($groups[1]['winners'])->toHaveCount(1)
        ->and($groups[1]['winners'][0]['athlete_name'])->toBe('FEMALE BEST')
        ->and($groups[1]['winners'][0]['gender_label'])->toBe('Putri');
});
