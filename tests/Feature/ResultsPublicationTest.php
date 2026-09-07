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

it('bumps the public page cache version when results are published', function () {
    $meet = finishedMeetWithResults();
    $before = \App\Support\PublicPageCache::version();

    app(PublishResults::class)->handle($meet['competition']->fresh(), $meet['admin']);

    expect(\App\Support\PublicPageCache::version())->toBe($before + 1);
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

it('flags all-dns heats and out-of-bounds times as anomalies', function () {
    $meet = finishedMeetWithResults(verify: false, lock: false);
    $heat = $meet['heat']->fresh()->load(['event', 'lanes.result', 'lanes.registration']);

    foreach ($heat->lanes->whereNotNull('registration_id') as $lane) {
        $lane->result?->update([
            'status' => ResultStatus::Dns,
            'time_ms' => null,
            'dsq_code' => null,
        ]);
    }

    $dnsAnomalies = app(ResultAnomalyDetector::class)->forHeat($heat->fresh()->load(['event', 'lanes.result', 'lanes.registration']));
    expect($dnsAnomalies->contains(fn (array $item): bool => $item['type'] === 'all_dns'))->toBeTrue();

    $lane = HeatLane::query()->where('heat_id', $heat->id)->whereNotNull('registration_id')->firstOrFail();
    $lane->result->update(['status' => ResultStatus::Ok, 'time_ms' => 1_000]);

    $boundAnomalies = app(ResultAnomalyDetector::class)->forHeat($heat->fresh()->load(['event', 'lanes.result', 'lanes.registration']));
    expect($boundAnomalies->contains(fn (array $item): bool => $item['type'] === 'out_of_bounds'))->toBeTrue();
});

it('shows cross-competition history on the athlete result page', function () {
    $meet = finishedMeetWithResults();
    app(PublishResults::class)->handle($meet['competition']->fresh(), $meet['admin']);
    $athlete = $meet['registrations'][0]->athlete;

    $other = openRegistrationMeet();
    $other['competition']->update(['name' => 'Kejuaraan Sebelumnya']);
    $registration = verifiedRegistration([
        'competition' => $other['competition'],
        'event' => $other['event'],
        'athlete' => $athlete,
        'group' => $other['group'],
        'coach' => $other['coach'],
    ], ['seed_time_ms' => 40_000]);
    $heat = Heat::factory()->create([
        'event_id' => $other['event']->id,
        'age_group_id' => $other['group']->id,
        'heat_number' => 1,
        'results_locked_at' => now(),
    ]);
    $lane = HeatLane::factory()->create([
        'heat_id' => $heat->id,
        'lane_number' => 4,
        'registration_id' => $registration->id,
    ]);
    \App\Models\Result::factory()->create([
        'heat_lane_id' => $lane->id,
        'time_ms' => 38_000,
        'status' => ResultStatus::Ok,
        'recorded_by' => $meet['admin']->id,
        'recorded_at' => now()->subDay(),
        'verified_at' => now(),
        'verified_by' => $meet['admin']->id,
    ]);

    $this->get(route('results.athlete', [$meet['competition'], $athlete]))
        ->assertOk()
        ->assertSee('Riwayat kejuaraan lain')
        ->assertSee('Kejuaraan Sebelumnya');
});

it('shows medal rollups by club on the public medals page', function () {
    $meet = finishedMeetWithResults();
    app(PublishResults::class)->handle($meet['competition']->fresh(), $meet['admin']);

    $this->get(route('results.medals', $meet['competition']))
        ->assertOk()
        ->assertSee('Per klub')
        ->assertSee('Per kelompok umur')
        ->assertSee($meet['registrations'][0]->athlete->club->name);
});
