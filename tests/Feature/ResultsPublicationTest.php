<?php

use App\Actions\LockHeat;
use App\Actions\PublishResults;
use App\Actions\RecordLaneResult;
use App\Actions\RunSeeding;
use App\Actions\VerifyHeatResults;
use App\Enums\CompetitionStatus;
use App\Enums\ResultStatus;
use App\Models\Heat;
use App\Models\HeatLane;
use App\Models\User;
use App\Notifications\ResultsPublished;
use App\Services\ResultAnomalyDetector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

function finishedMeetWithResults(bool $verify = true, bool $lock = true): array
{
    [$competition, $event, $group, $registrations] = seedMeetWithEntrants(3, lanes: 6);
    app(RunSeeding::class)->handle($competition, $event, $group);
    $heat = Heat::query()->where('event_id', $event->id)->firstOrFail();
    $judge = User::factory()->juri()->create();
    $event->judges()->attach($judge->id);
    $admin = User::factory()->panitia()->create();

    foreach (HeatLane::query()->where('heat_id', $heat->id)->whereNotNull('registration_id')->get() as $index => $lane) {
        $seed = $lane->registration->seed_time_ms ?? 40_000;
        app(RecordLaneResult::class)->handle($lane, [
            'status' => ResultStatus::Ok,
            'time_ms' => $seed,
        ], $judge);
    }

    if ($lock) {
        app(LockHeat::class)->handle($heat->fresh(), $judge);
    }

    if ($verify) {
        app(VerifyHeatResults::class)->handleHeat($heat->fresh(), $admin);
    }

    $competition->update(['status' => CompetitionStatus::Finished]);

    return compact('competition', 'event', 'group', 'heat', 'judge', 'admin', 'registrations');
}

it('returns 404 to guests when competition is only finished', function () {
    $meet = finishedMeetWithResults();

    $this->get(route('results.index', $meet['competition']))->assertNotFound();
});

it('allows panitia to preview results while finished', function () {
    $meet = finishedMeetWithResults();

    $this->actingAs($meet['admin'])
        ->get(route('results.index', $meet['competition']))
        ->assertOk()
        ->assertSee('Pratinjau');
});

it('flags a result ten seconds faster than seed as an anomaly', function () {
    $meet = finishedMeetWithResults(verify: false, lock: false);
    $lane = HeatLane::query()->where('heat_id', $meet['heat']->id)->whereNotNull('registration_id')->firstOrFail();
    $lane->registration->update(['seed_time_ms' => 40_000]);
    $lane->result->update(['time_ms' => 29_000, 'status' => ResultStatus::Ok]);

    $anomalies = app(ResultAnomalyDetector::class)->forHeat($meet['heat']->fresh()->load(['event', 'lanes.result', 'lanes.registration']));

    expect($anomalies->contains(fn (array $item): bool => $item['type'] === 'fast_vs_seed'))->toBeTrue();
});

it('rejects publishing while results are unverified', function () {
    $meet = finishedMeetWithResults(verify: false, lock: true);

    $this->actingAs($meet['admin'])
        ->patch(route('admin.competitions.status', $meet['competition']), [
            'status' => CompetitionStatus::Published->value,
        ])
        ->assertSessionHasErrors('status');
});

it('rejects publishing while a heat is unlocked', function () {
    $meet = finishedMeetWithResults(verify: true, lock: false);

    expect(fn () => app(PublishResults::class)->handle($meet['competition']->fresh(), $meet['admin']))
        ->toThrow(\App\Exceptions\CannotTransitionCompetitionException::class);
});

it('publishes results and opens the public page', function () {
    Notification::fake();
    $meet = finishedMeetWithResults();

    $this->actingAs($meet['admin'])
        ->patch(route('admin.competitions.status', $meet['competition']), [
            'status' => CompetitionStatus::Published->value,
        ])
        ->assertRedirect();

    $competition = $meet['competition']->fresh();
    expect($competition->status)->toBe(CompetitionStatus::Published)
        ->and($competition->published_at)->not->toBeNull();

    $this->get(route('results.index', $competition))
        ->assertOk()
        ->assertSee('Hasil lomba');

    Notification::assertSentTo($meet['registrations'][0]->registrar, ResultsPublished::class);
});

it('shows every event the athlete swam on their result page', function () {
    $meet = finishedMeetWithResults();
    app(PublishResults::class)->handle($meet['competition']->fresh(), $meet['admin']);

    $athlete = $meet['registrations'][0]->athlete;

    $this->get(route('results.athlete', [$meet['competition'], $athlete]))
        ->assertOk()
        ->assertSee($athlete->full_name)
        ->assertSee((string) $meet['event']->event_number);
});
