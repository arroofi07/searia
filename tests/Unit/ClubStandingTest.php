<?php

use App\Enums\ClubStatus;
use App\Enums\Equipment;
use App\Enums\EventGender;
use App\Enums\ResultStatus;
use App\Enums\Stroke;
use App\Models\Athlete;
use App\Models\Club;
use App\Models\Event;
use App\Models\Heat;
use App\Models\HeatLane;
use App\Models\Result;
use App\Models\User;
use App\Services\ClubStanding;
use App\Services\MedalTally;
use App\Services\RankingCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('ranks a club with one gold above a club with five silvers', function () {
    $meet = openRegistrationMeet();
    $competition = $meet['competition'];
    $group = $meet['group'];
    $goldClub = $meet['club'];
    $silverClub = Club::factory()->create(['status' => ClubStatus::Verified, 'name' => 'Silver Factory']);
    $anchorClub = Club::factory()->create(['status' => ClubStatus::Verified, 'name' => 'Anchor Club']);
    $recorder = User::factory()->panitia()->create();
    $lane = 0;

    $addResult = function (Event $event, Club $club, string $name, int $timeMs) use (&$lane, $meet, $group, $recorder) {
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

    // One gold for goldClub.
    $eventGold = $meet['event'];
    $addResult($eventGold, $goldClub, 'Champion', 30_000);
    $addResult($eventGold, $anchorClub, 'Second A', 31_000);
    $addResult($eventGold, $silverClub, 'Third A', 32_000);

    // Five silvers for silverClub (anchor takes gold each time).
    for ($i = 0; $i < 5; $i++) {
        $event = Event::factory()->create([
            'competition_id' => $competition->id,
            'event_number' => 40 + $i,
            'gender' => EventGender::Male,
            'distance' => 50,
            'stroke' => Stroke::Freestyle,
            'equipment' => Equipment::None,
        ]);
        $event->ageGroups()->attach($group->id);
        $addResult($event, $anchorClub, 'Anchor '.$i, 29_000);
        $addResult($event, $silverClub, 'Silver '.$i, 30_000);
        $addResult($event, $goldClub, 'Also ran '.$i, 40_000);
    }

    $rows = app(ClubStanding::class)->forCompetition(
        $competition->fresh(),
        app(MedalTally::class),
        app(RankingCalculator::class),
    );

    $goldRow = $rows->firstWhere('club_id', $goldClub->id);
    $silverRow = $rows->firstWhere('club_id', $silverClub->id);
    $goldIndex = $rows->search(fn (array $row): bool => $row['club_id'] === $goldClub->id);
    $silverIndex = $rows->search(fn (array $row): bool => $row['club_id'] === $silverClub->id);

    expect($goldRow['gold'])->toBe(1)
        ->and($silverRow['silver'])->toBe(5)
        ->and($goldIndex)->toBeLessThan($silverIndex);
});
