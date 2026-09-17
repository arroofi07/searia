<?php

use App\Actions\RecordLaneResult;
use App\Actions\RunSeeding;
use App\Enums\CompetitionStatus;
use App\Enums\ResultStatus;
use App\Models\User;
use App\Services\BestSwimmerStanding;
use App\Services\ClubStanding;
use App\Services\MedalTally;
use App\Services\RankingCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function awardsMeetWithResults(): array
{
    [$competition, $event, $group] = seedMeetWithEntrants(4);
    app(RunSeeding::class)->handle($competition, $event, $group);
    $competition->update(['status' => CompetitionStatus::Running]);

    $judge = User::factory()->panitia()->create();
    $heat = $event->heats()->where('age_group_id', $group->id)->firstOrFail();
    $timeMs = 34_000;
    foreach ($heat->lanes()->whereNotNull('registration_id')->orderBy('lane_number')->get() as $lane) {
        app(RecordLaneResult::class)->handle($lane, ['status' => ResultStatus::Ok, 'time_ms' => $timeMs], $judge);
        $timeMs += 500;
    }

    return compact('competition', 'event', 'group', 'judge');
}

it('lets guests download awards pdfs after publish and hides them before', function () {
    $meet = awardsMeetWithResults();
    $competition = $meet['competition'];

    $this->get(route('results.best-club.pdf', $competition))->assertNotFound();
    $this->get(route('results.best-swimmers.pdf', $competition))->assertNotFound();

    $competition->update(['status' => CompetitionStatus::Published, 'published_at' => now()]);

    $this->get(route('results.best-club.pdf', $competition))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    $this->get(route('results.best-swimmers.pdf', $competition))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('lets panitia download awards pdfs from the admin print page', function () {
    $meet = awardsMeetWithResults();
    $competition = $meet['competition'];

    $this->actingAs($meet['judge'])
        ->get(route('admin.start-list.index', $competition))
        ->assertOk()
        ->assertSee('PDF penghargaan')
        ->assertSee('Unduh club terbaik')
        ->assertSee('Unduh atlet terbaik');

    $this->actingAs($meet['judge'])
        ->get(route('admin.results.best-club-pdf', $competition))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    $this->actingAs($meet['judge'])
        ->get(route('admin.results.best-swimmers-pdf', $competition))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('renders club terbaik and atlet terbaik tables with medal winners', function () {
    $meet = awardsMeetWithResults();
    $competition = $meet['competition']->fresh();
    $header = [
        'competitionName' => $competition->name,
        'venue' => (string) $competition->venue,
        'city' => (string) $competition->city,
        'dateLabel' => $competition->start_date->translatedFormat('d M Y'),
        'printedAt' => now(),
        'includeCover' => false,
    ];

    $clubHtml = view('pdf.best-club', [
        ...$header,
        'rows' => app(ClubStanding::class)->forCompetition(
            $competition,
            app(MedalTally::class),
            app(RankingCalculator::class),
        ),
    ])->render();

    $groups = app(BestSwimmerStanding::class)->forCompetition(
        $competition,
        app(MedalTally::class),
        app(RankingCalculator::class),
    );
    $swimmerHtml = view('pdf.best-swimmers', [
        ...$header,
        'groups' => $groups,
    ])->render();

    $winner = $groups->first()['winners'][0];

    expect($clubHtml)->toContain('CLUB TERBAIK')
        ->and($clubHtml)->toContain('KOTA')
        ->and($clubHtml)->toContain('EMAS')
        ->and($swimmerHtml)->toContain('DAFTAR ATLET TERBAIK')
        ->and($swimmerHtml)->toContain($winner['athlete_name'])
        ->and($swimmerHtml)->toContain('PUTRA');
});
