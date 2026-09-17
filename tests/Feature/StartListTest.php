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
        ->assertSee('/brand/event-logo', false)
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

it('keeps the ranked results book form separate from blank result sheets', function () {
    [$competition, $event, $group] = seedMeetWithEntrants(4);
    app(RunSeeding::class)->handle($competition, $event, $group);
    $admin = User::factory()->panitia()->create();

    $html = $this->actingAs($admin)
        ->get(route('admin.start-list.index', $competition))
        ->assertOk()
        ->assertSee('Unduh buku hasil', false)
        ->assertSee('Unduh lembar kosong', false)
        ->assertSee(route('admin.results.book-pdf', $competition), false)
        ->assertSee(route('admin.start-list.results', $competition), false)
        ->getContent();

    $depth = 0;
    $maxDepth = 0;
    $offset = 0;
    while (preg_match('/<\/?form\b[^>]*>/i', $html, $match, PREG_OFFSET_CAPTURE, $offset) === 1) {
        $tag = $match[0][0];
        $offset = $match[0][1] + strlen($tag);
        $depth += str_starts_with(strtolower($tag), '</') ? -1 : 1;
        $maxDepth = max($maxDepth, $depth);
        expect($depth)->toBeGreaterThanOrEqual(0);
    }

    expect($depth)->toBe(0)
        ->and($maxDepth)->toBe(1);
});

it('downloads the ranked results book instead of a start-list style sheet', function () {
    [$competition, $event, $group] = seedMeetWithEntrants(4);
    app(RunSeeding::class)->handle($competition, $event, $group);
    $admin = User::factory()->panitia()->create();

    $response = $this->actingAs($admin)
        ->get(route('admin.results.book-pdf', $competition))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    expect($response->headers->get('content-disposition'))->toContain('buku-hasil-'.$competition->slug.'.pdf');

    $document = app(\App\Services\ResultsBookBuilder::class)->build($competition->fresh());
    $html = view('pdf.results-book', [
        'document' => $document,
        'competitionName' => $document->competitionName,
        'venue' => $document->venue,
        'city' => $document->city,
        'dateLabel' => $document->dateLabel,
        'printedAt' => $document->printedAt,
        'includeCover' => true,
    ])->render();

    expect($html)->toContain('TEMPAT')
        ->and($html)->toContain('HASIL')
        ->and($html)->toContain('Umur')
        ->and($html)->toContain('Group')
        ->and($html)->toContain('Emas')
        ->and($html)->toContain('Perak')
        ->and($html)->toContain('Perunggu')
        ->and($html)->not->toContain('>YOB<')
        ->and($html)->not->toContain('>AGE<')
        ->and($html)->not->toContain('●')
        ->and($html)->not->toContain('✓');
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
