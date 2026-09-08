<?php

use App\Actions\RunSeeding;
use App\Enums\CompetitionStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns 404 to guests before competition is seeded', function () {
    [$competition] = seedMeetWithEntrants(4);
    $competition->update(['status' => CompetitionStatus::Closed]);

    $this->get(route('start-list.show', $competition))->assertNotFound();
});

it('allows guests to open start list after seeding', function () {
    [$competition, $event, $group] = seedMeetWithEntrants(4);
    app(RunSeeding::class)->handle($competition, $event, $group);
    $competition->update(['status' => CompetitionStatus::Seeded]);

    $this->get(route('start-list.show', $competition))
        ->assertOk()
        ->assertSee('Buku acara')
        ->assertSee('ATHLETE 01');
});

it('lets admin download start list and result sheet pdfs', function () {
    [$competition, $event, $group] = seedMeetWithEntrants(4);
    app(RunSeeding::class)->handle($competition, $event, $group);
    $competition->update(['status' => CompetitionStatus::Seeded]);
    $admin = User::factory()->panitia()->create();

    $this->actingAs($admin)
        ->get(route('admin.start-list.pdf', $competition))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    $this->actingAs($admin)
        ->get(route('admin.start-list.results', $competition))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('highlights matching athlete names on the public start list', function () {
    [$competition, $event, $group] = seedMeetWithEntrants(4);
    app(RunSeeding::class)->handle($competition, $event, $group);
    $competition->update(['status' => CompetitionStatus::Seeded]);

    $this->get(route('start-list.show', [$competition, 'q' => 'ATHLETE 01']))
        ->assertOk()
        ->assertSee('bg-amber-50', false)
        ->assertSee('ATHLETE 01');
});

it('orders club start list by program order rather than athlete name', function () {
    [$competition, $event, $group, $registrations] = seedMeetWithEntrants(4);
    $registrations[0]->athlete->update(['full_name' => 'ZZZ LAST']);
    $registrations[1]->athlete->update(['full_name' => 'AAA FIRST']);
    app(RunSeeding::class)->handle($competition, $event, $group);
    $competition->update(['status' => CompetitionStatus::Seeded]);

    $document = app(\App\Services\StartListBuilder::class)->build($competition->fresh(), clubId: $registrations[0]->athlete->club_id);
    $names = [];
    foreach ($document->sessions as $session) {
        foreach ($session->events as $eventBlock) {
            foreach ($eventBlock->ageGroups as $ageGroup) {
                foreach ($ageGroup->heats as $heat) {
                    foreach ($heat->lanes as $lane) {
                        if (! $lane->isEmpty()) {
                            $names[] = $lane->athleteName;
                        }
                    }
                }
            }
        }
    }

    expect($names)->not->toBe(collect($names)->sort()->values()->all());
});
