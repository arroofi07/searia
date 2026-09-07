<?php

use App\Actions\RunSeeding;
use App\Models\ActivityLog;
use App\Models\Heat;
use App\Models\HeatLane;
use App\Models\User;

it('locks every heat for a competition', function () {
    [$competition, $event, $group] = seedMeetWithEntrants(6);
    app(RunSeeding::class)->handle($competition, $event, $group);

    $this->actingAs(User::factory()->panitia()->create())
        ->post(route('admin.seeding.lock', $competition))
        ->assertRedirect();

    expect(Heat::query()->where('event_id', $event->id)->whereNull('locked_at')->count())->toBe(0);
});

it('rejects locking when an event has no heats', function () {
    $meet = openRegistrationMeet();

    $this->actingAs(User::factory()->panitia()->create())
        ->from(route('admin.seeding.index', $meet['competition']))
        ->post(route('admin.seeding.lock', $meet['competition']))
        ->assertRedirect(route('admin.seeding.index', $meet['competition']))
        ->assertSessionHasErrors('seeding');
});

it('swaps two lanes and writes complementary audit entries', function () {
    [$competition, $event, $group] = seedMeetWithEntrants(6);
    app(RunSeeding::class)->handle($competition, $event, $group);
    $lanes = HeatLane::query()->orderBy('lane_number')->get();
    $left = $lanes->firstWhere('lane_number', 3);
    $right = $lanes->firstWhere('lane_number', 4);
    $leftRegistration = $left->registration_id;
    $rightRegistration = $right->registration_id;

    $this->actingAs(User::factory()->panitia()->create())
        ->post(route('admin.heat-lanes.swap'), [
            'left_lane_id' => $left->id,
            'right_lane_id' => $right->id,
        ])
        ->assertRedirect();

    expect($left->fresh()->registration_id)->toBe($rightRegistration)
        ->and($right->fresh()->registration_id)->toBe($leftRegistration)
        ->and(ActivityLog::query()->where('action', 'heat_lane.swap')->count())->toBe(2);
});

it('rejects moving an entrant onto an occupied lane', function () {
    [$competition, $event, $group] = seedMeetWithEntrants(6);
    app(RunSeeding::class)->handle($competition, $event, $group);
    $heat = Heat::query()->where('event_id', $event->id)->first();
    $source = $heat->lanes()->where('lane_number', 3)->first();

    $this->actingAs(User::factory()->panitia()->create())
        ->from(route('admin.seeding.show', [$competition, $event, $group]))
        ->post(route('admin.heat-lanes.move', $source), [
            'target_heat_id' => $heat->id,
            'target_lane_number' => 4,
        ])
        ->assertRedirect(route('admin.seeding.show', [$competition, $event, $group]))
        ->assertSessionHasErrors('heat_lane');
});

it('withdraws an entrant without shifting other lanes', function () {
    [$competition, $event, $group] = seedMeetWithEntrants(6);
    app(RunSeeding::class)->handle($competition, $event, $group);
    $heat = Heat::query()->where('event_id', $event->id)->firstOrFail();
    $lane3 = $heat->lanes()->where('lane_number', 3)->firstOrFail();
    $lane4 = $heat->lanes()->where('lane_number', 4)->firstOrFail();
    $keepRegistration = $lane4->registration_id;

    $this->actingAs(User::factory()->panitia()->create())
        ->delete(route('admin.heat-lanes.withdraw', $lane3))
        ->assertRedirect();

    expect($lane3->fresh()->registration_id)->toBeNull()
        ->and($lane4->fresh()->registration_id)->toBe($keepRegistration)
        ->and($heat->lanes()->count())->toBe(6)
        ->and(ActivityLog::query()->where('action', 'heat_lane.withdraw')->count())->toBe(1);
});

it('rejects moving across a different age group', function () {
    [$competition, $event, $group] = seedMeetWithEntrants(6);
    app(RunSeeding::class)->handle($competition, $event, $group);
    $heat = Heat::query()->where('event_id', $event->id)->firstOrFail();
    $source = $heat->lanes()->whereNotNull('registration_id')->firstOrFail();

    $otherGroup = $competition->ageGroups()->where('id', '!=', $group->id)->first()
        ?? $competition->ageGroups()->create([
            'code' => 'X',
            'name' => 'Grup X',
            'display_code' => 'X',
            'birth_year_start' => 2000,
            'birth_year_end' => 2001,
            'sort_order' => 99,
        ]);

    $foreignHeat = Heat::query()->create([
        'event_id' => $event->id,
        'age_group_id' => $otherGroup->id,
        'heat_number' => 1,
        'round' => 'final',
    ]);

    $this->actingAs(User::factory()->panitia()->create())
        ->from(route('admin.seeding.show', [$competition, $event, $group]))
        ->post(route('admin.heat-lanes.move', $source), [
            'target_heat_id' => $foreignHeat->id,
            'target_lane_number' => 3,
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('heat_lane');
});
