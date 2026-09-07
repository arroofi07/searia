<?php

use App\Actions\LockHeat;
use App\Actions\RecordLaneResult;
use App\Actions\RunSeeding;
use App\Enums\CompetitionStatus;
use App\Enums\DisqualificationCode;
use App\Enums\ResultStatus;
use App\Models\ActivityLog;
use App\Models\Heat;
use App\Models\HeatLane;
use App\Models\Result;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function seededHeatMeet(int $count = 4): array
{
    [$competition, $event, $group] = seedMeetWithEntrants($count, lanes: 6);
    app(RunSeeding::class)->handle($competition, $event, $group);
    $competition->update(['status' => CompetitionStatus::Running]);

    $heat = Heat::query()->where('event_id', $event->id)->firstOrFail();
    $judge = User::factory()->juri()->create();
    $event->judges()->attach($judge->id);

    return compact('competition', 'event', 'group', 'heat', 'judge');
}

it('returns 403 when a judge opens an unassigned event heat', function () {
    $meet = seededHeatMeet();
    $other = User::factory()->juri()->create();

    $this->actingAs($other)
        ->get(route('judge.heats.show', $meet['heat']))
        ->assertForbidden();
});

it('shows task progress based on locked heats', function () {
    $meet = seededHeatMeet(6);
    $lanes = HeatLane::query()->where('heat_id', $meet['heat']->id)->whereNotNull('registration_id')->get();
    $recorder = app(RecordLaneResult::class);

    foreach ($lanes as $lane) {
        $recorder->handle($lane, ['status' => ResultStatus::Ok, 'time' => '3470'], $meet['judge']);
    }

    app(LockHeat::class)->handle($meet['heat']->fresh(), $meet['judge']);

    $this->actingAs($meet['judge'])
        ->get(route('judge.tasks'))
        ->assertOk()
        ->assertSee('1 / 1 seri dikunci');
});

it('saves a complete heat of lane results', function () {
    $meet = seededHeatMeet(3);
    $lanes = HeatLane::query()->where('heat_id', $meet['heat']->id)->whereNotNull('registration_id')->get();

    foreach ($lanes as $lane) {
        $this->actingAs($meet['judge'])
            ->putJson(route('judge.lanes.results.upsert', $lane), [
                'status' => 'ok',
                'time' => '3470',
            ])
            ->assertOk()
            ->assertJsonPath('result.time_ms', 34_700);
    }

    expect(Result::query()->count())->toBe(3);
});

it('rejects dsq without a code', function () {
    $meet = seededHeatMeet(2);
    $lane = HeatLane::query()->where('heat_id', $meet['heat']->id)->whereNotNull('registration_id')->firstOrFail();

    $this->actingAs($meet['judge'])
        ->putJson(route('judge.lanes.results.upsert', $lane), [
            'status' => 'dsq',
        ])
        ->assertStatus(422);
});

it('is idempotent when saving the same lane twice', function () {
    $meet = seededHeatMeet(2);
    $lane = HeatLane::query()->where('heat_id', $meet['heat']->id)->whereNotNull('registration_id')->firstOrFail();

    $this->actingAs($meet['judge'])
        ->putJson(route('judge.lanes.results.upsert', $lane), ['status' => 'ok', 'time' => '4000'])
        ->assertOk();

    $this->actingAs($meet['judge'])
        ->putJson(route('judge.lanes.results.upsert', $lane), ['status' => 'ok', 'time' => '4010'])
        ->assertOk();

    expect(Result::query()->where('heat_lane_id', $lane->id)->count())->toBe(1)
        ->and(Result::query()->where('heat_lane_id', $lane->id)->value('time_ms'))->toBe(40_100);
});

it('rejects locking an incomplete heat', function () {
    $meet = seededHeatMeet(3);
    $lane = HeatLane::query()->where('heat_id', $meet['heat']->id)->whereNotNull('registration_id')->firstOrFail();
    app(RecordLaneResult::class)->handle($lane, ['status' => ResultStatus::Ok, 'time' => '3000'], $meet['judge']);

    $this->actingAs($meet['judge'])
        ->from(route('judge.heats.show', $meet['heat']))
        ->post(route('judge.heats.lock', $meet['heat']))
        ->assertRedirect()
        ->assertSessionHasErrors('lock');
});

it('forbids a judge from changing a locked heat', function () {
    $meet = seededHeatMeet(2);
    $lanes = HeatLane::query()->where('heat_id', $meet['heat']->id)->whereNotNull('registration_id')->get();
    foreach ($lanes as $lane) {
        app(RecordLaneResult::class)->handle($lane, ['status' => ResultStatus::Ok, 'time' => '3200'], $meet['judge']);
    }
    app(LockHeat::class)->handle($meet['heat']->fresh(), $meet['judge']);

    $lane = $lanes->first();
    $this->actingAs($meet['judge'])
        ->putJson(route('judge.lanes.results.upsert', $lane), ['status' => 'ok', 'time' => '3300'])
        ->assertForbidden();
});

it('rejects a correction without a reason', function () {
    $meet = seededHeatMeet(2);
    $lane = HeatLane::query()->where('heat_id', $meet['heat']->id)->whereNotNull('registration_id')->firstOrFail();
    $result = app(RecordLaneResult::class)->handle($lane, ['status' => ResultStatus::Ok, 'time' => '3200'], $meet['judge']);
    $admin = User::factory()->panitia()->create();

    $this->actingAs($admin)
        ->put(route('admin.results.correct', $result), [
            'status' => 'ok',
            'time' => '3100',
        ])
        ->assertSessionHasErrors('reason');
});

it('writes an audit entry when correcting a result', function () {
    $meet = seededHeatMeet(2);
    $lane = HeatLane::query()->where('heat_id', $meet['heat']->id)->whereNotNull('registration_id')->firstOrFail();
    $result = app(RecordLaneResult::class)->handle($lane, ['status' => ResultStatus::Ok, 'time' => '3200'], $meet['judge']);
    $admin = User::factory()->panitia()->create();

    $this->actingAs($admin)
        ->put(route('admin.results.correct', $result), [
            'status' => 'dsq',
            'dsq_code' => DisqualificationCode::Tn->value,
            'reason' => 'Kesalahan pencatatan juri',
        ])
        ->assertRedirect();

    $log = ActivityLog::query()->where('action', 'result.correct')->first();
    expect($log)->not->toBeNull()
        ->and($log->old_values['status'])->toBe('ok')
        ->and($log->new_values['status'])->toBe('dsq')
        ->and($log->reason)->toBe('Kesalahan pencatatan juri');
});
