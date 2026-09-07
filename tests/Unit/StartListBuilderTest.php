<?php

use App\Actions\RunSeeding;
use App\Enums\Equipment;
use App\Enums\EventGender;
use App\Enums\Stroke;
use App\Models\AgeGroup;
use App\Models\Event;
use App\Services\StartListBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('keeps empty lanes so lane numbers stay contiguous', function () {
    [$competition, $event, $group] = seedMeetWithEntrants(3, lanes: 6);
    app(RunSeeding::class)->handle($competition, $event, $group);

    $document = app(StartListBuilder::class)->build($competition->fresh());
    $heat = $document->sessions[0]->events[0]->ageGroups[0]->heats[0];

    expect($heat->lanes)->toHaveCount(6)
        ->and(collect($heat->lanes)->pluck('laneNumber')->all())->toBe([1, 2, 3, 4, 5, 6])
        ->and(collect($heat->lanes)->filter->isEmpty()->count())->toBe(3);
});

it('orders age groups by sort_order youngest first', function () {
    [$competition, $event, $young] = seedMeetWithEntrants(2, lanes: 6);

    $older = AgeGroup::factory()->create([
        'competition_id' => $competition->id,
        'code' => '5',
        'name' => 'Group 5',
        'birth_year_start' => 2010,
        'birth_year_end' => 2011,
        'sort_order' => 5,
    ]);
    $event->ageGroups()->attach($older->id);

    $sample = $competition->registrations()->with(['athlete', 'registrar'])->first();
    $meet = [
        'competition' => $competition,
        'event' => $event,
        'athlete' => $sample->athlete,
        'group' => $older,
        'coach' => $sample->registrar,
    ];

    $olderAthlete = \App\Models\Athlete::factory()->create([
        'club_id' => $meet['athlete']->club_id,
        'gender' => $meet['athlete']->gender,
        'birth_year' => 2011,
        'full_name' => 'OLDER ATHLETE',
    ]);
    verifiedRegistration($meet, [
        'athlete_id' => $olderAthlete->id,
        'age_group_id' => $older->id,
        'seed_time_ms' => 35_000,
    ]);

    app(RunSeeding::class)->handle($competition->fresh(), $event);

    $document = app(StartListBuilder::class)->build($competition->fresh());
    $groupNames = collect($document->sessions[0]->events[0]->ageGroups)->pluck('name')->all();

    expect($groupNames)->toBe(['Group 3', 'Group 5']);
});

it('does not increase query count as event count grows', function () {
    [$competition, $event, $group] = seedMeetWithEntrants(4, lanes: 6);
    app(RunSeeding::class)->handle($competition, $event, $group);

    $countQueries = function () use ($competition): int {
        DB::flushQueryLog();
        DB::enableQueryLog();
        app(StartListBuilder::class)->build($competition->fresh());
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    };

    $baseline = $countQueries();

    for ($n = 2; $n <= 4; $n++) {
        Event::factory()->create([
            'competition_id' => $competition->id,
            'event_number' => 20 + $n,
            'session' => 1,
            'sort_order' => $n,
            'gender' => EventGender::Male,
            'distance' => 50,
            'stroke' => Stroke::Freestyle,
            'equipment' => Equipment::None,
        ])->ageGroups()->attach($group->id);
    }

    $withMoreEvents = $countQueries();

    expect($withMoreEvents)->toBeLessThanOrEqual($baseline + 1);
});

it('filters by session and event', function () {
    [$competition, $event] = seedMeetWithEntrants(4, lanes: 6);
    $event->update(['session' => 1, 'sort_order' => 1]);

    $other = Event::factory()->create([
        'competition_id' => $competition->id,
        'event_number' => 99,
        'session' => 2,
        'sort_order' => 1,
        'gender' => EventGender::Male,
        'distance' => 100,
        'stroke' => Stroke::Freestyle,
        'equipment' => Equipment::None,
    ]);
    $other->ageGroups()->attach($competition->ageGroups()->first()->id);

    app(RunSeeding::class)->handle($competition->fresh());

    $sessionOne = app(StartListBuilder::class)->build($competition->fresh(), session: 1);
    $singleEvent = app(StartListBuilder::class)->build($competition->fresh(), eventId: $event->id);

    expect($sessionOne->sessions)->toHaveCount(1)
        ->and($sessionOne->sessions[0]->session)->toBe(1)
        ->and($singleEvent->sessions[0]->events)->toHaveCount(1)
        ->and($singleEvent->sessions[0]->events[0]->eventId)->toBe($event->id);
});

it('builds a thousand-entrant start list in under three seconds', function () {
    [$competition, $event, $group] = seedMeetWithEntrants(1000, lanes: 8);
    app(RunSeeding::class)->handle($competition, $event, $group);

    $started = microtime(true);
    $document = app(StartListBuilder::class)->build($competition->fresh());
    $elapsed = microtime(true) - $started;

    expect($elapsed)->toBeLessThan(3.0)
        ->and($document->sessions)->not->toBeEmpty();
});
