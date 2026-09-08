<?php

use App\Actions\RecordLaneResult;
use App\Actions\RunSeeding;
use App\Enums\CompetitionStatus;
use App\Enums\ResultStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lets guests download results pdf after publish and hides it before', function () {
    [$competition, $event, $group] = seedMeetWithEntrants(4);
    app(RunSeeding::class)->handle($competition, $event, $group);
    $competition->update(['status' => CompetitionStatus::Running]);

    $judge = User::factory()->panitia()->create();
    $heat = $event->heats()->where('age_group_id', $group->id)->firstOrFail();
    $lane = $heat->lanes()->whereNotNull('registration_id')->firstOrFail();
    app(RecordLaneResult::class)->handle($lane, ['status' => ResultStatus::Ok, 'time' => '3470'], $judge);

    $this->get(route('results.pdf', $competition))->assertNotFound();

    $competition->update(['status' => CompetitionStatus::Published, 'published_at' => now()]);

    $this->get(route('results.pdf', $competition))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('lets panitia and juri download results pdf after seeding', function () {
    [$competition, $event, $group] = seedMeetWithEntrants(4);
    app(RunSeeding::class)->handle($competition, $event, $group);
    $competition->update(['status' => CompetitionStatus::Seeded]);

    $this->actingAs(User::factory()->panitia()->create())
        ->get(route('results.pdf', $competition))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    $this->actingAs(User::factory()->juri()->create())
        ->get(route('results.pdf', $competition))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('lets guests download start list pdf after seeding', function () {
    [$competition, $event, $group] = seedMeetWithEntrants(4);
    app(RunSeeding::class)->handle($competition, $event, $group);
    $competition->update(['status' => CompetitionStatus::Seeded]);

    $this->get(route('start-list.pdf', $competition))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});
