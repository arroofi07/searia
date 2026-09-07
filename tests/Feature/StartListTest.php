<?php

use App\Actions\RunSeeding;
use App\Enums\ClubStatus;
use App\Enums\CompetitionStatus;
use App\Models\Club;
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

it('forbids a coach from downloading another club start list', function () {
    [$competition, $event, $group, $registrations] = seedMeetWithEntrants(4);
    app(RunSeeding::class)->handle($competition, $event, $group);
    $competition->update(['status' => CompetitionStatus::Seeded]);

    $clubA = $registrations[0]->athlete->club;
    $coachA = User::factory()->pelatih($clubA)->create();
    $clubB = Club::factory()->create(['status' => ClubStatus::Verified]);

    $this->actingAs($coachA)
        ->get(route('coach.start-list.download', [$competition, $clubB]))
        ->assertForbidden();
});

it('allows a coach to download their own club start list pdf', function () {
    [$competition, $event, $group, $registrations] = seedMeetWithEntrants(4);
    app(RunSeeding::class)->handle($competition, $event, $group);
    $competition->update(['status' => CompetitionStatus::Seeded]);

    $club = $registrations[0]->athlete->club;
    $coach = User::factory()->pelatih($club)->create();

    $this->actingAs($coach)
        ->get(route('coach.start-list.download', [$competition, $club]))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});
