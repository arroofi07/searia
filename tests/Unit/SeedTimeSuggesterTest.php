<?php

use App\Enums\CompetitionType;
use App\Enums\Equipment;
use App\Enums\EventGender;
use App\Enums\Stroke;
use App\Models\AgeGroup;
use App\Models\Athlete;
use App\Models\Competition;
use App\Models\Event;
use App\Models\Heat;
use App\Models\HeatLane;
use App\Models\Registration;
use App\Models\Result;
use App\Models\User;
use App\Services\SeedTimeSuggester;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function officialResult(Athlete $athlete, int $poolLength, CompetitionType $type, int $timeMs): void
{
    $previous = Competition::factory()->create([
        'type' => $type,
        'pool_length' => $poolLength,
        'status' => \App\Enums\CompetitionStatus::Published,
    ]);
    $group = AgeGroup::factory()->create(['competition_id' => $previous->id]);
    $event = Event::factory()->create([
        'competition_id' => $previous->id,
        'distance' => 50,
        'stroke' => Stroke::Breaststroke,
        'equipment' => Equipment::None,
        'gender' => EventGender::Male,
    ]);
    $registration = Registration::factory()->create([
        'competition_id' => $previous->id,
        'event_id' => $event->id,
        'athlete_id' => $athlete->id,
        'age_group_id' => $group->id,
    ]);
    $heat = Heat::factory()->create([
        'event_id' => $event->id,
        'age_group_id' => $group->id,
    ]);
    $lane = HeatLane::factory()->create([
        'heat_id' => $heat->id,
        'registration_id' => $registration->id,
        'lane_number' => 4,
    ]);
    Result::factory()->create([
        'heat_lane_id' => $lane->id,
        'time_ms' => $timeMs,
        'status' => 'ok',
        'recorded_by' => User::factory(),
    ]);
}

it('does not suggest a result from a fun meet', function () {
    $meet = openRegistrationMeet();
    officialResult($meet['athlete'], 25, CompetitionType::Fun, 40_000);

    expect((new SeedTimeSuggester)->suggest($meet['athlete'], $meet['event'], $meet['competition']))->toBeNull();
});

it('does not suggest a 25m result for a 50m meet', function () {
    $meet = openRegistrationMeet();
    $meet['competition']->update(['pool_length' => 50]);
    officialResult($meet['athlete'], 25, CompetitionType::Official, 40_000);

    expect((new SeedTimeSuggester)->suggest($meet['athlete'], $meet['event'], $meet['competition']))->toBeNull();
});
