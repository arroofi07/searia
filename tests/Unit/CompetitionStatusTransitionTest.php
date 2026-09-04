<?php

use App\Enums\CompetitionStatus;
use App\Exceptions\CannotTransitionCompetitionException;
use App\Models\ActivityLog;
use App\Models\AgeGroup;
use App\Models\Athlete;
use App\Models\Competition;
use App\Models\Event;
use App\Models\Heat;
use App\Models\HeatLane;
use App\Models\Registration;
use App\Models\Result;
use App\Models\User;
use App\Services\CompetitionStatusTransition;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows only the adjacent transitions drawn in the status diagram', function () {
    $service = new CompetitionStatusTransition;
    $superAdmin = User::factory()->superAdmin()->create();
    $panitia = User::factory()->panitia()->create();

    foreach (CompetitionStatus::cases() as $from) {
        foreach (CompetitionStatus::cases() as $to) {
            $allowed = $from->canMoveTo($to);
            $competition = Competition::factory()->status($from)->create();

            if ($from === $to) {
                expect($service->canTransition($from, $to))->toBeFalse();

                continue;
            }

            if ($allowed && $from->isBackward($to)) {
                $service->transition($competition, $to, $superAdmin, 'Koreksi status');
                expect($competition->fresh()->status)->toBe($to);

                continue;
            }

            if ($allowed) {
                $service->transition($competition, $to, $panitia);
                expect($competition->fresh()->status)->toBe($to);

                continue;
            }

            expect(fn () => $service->transition($competition, $to, $superAdmin, 'tidak sah'))
                ->toThrow(CannotTransitionCompetitionException::class);
        }
    }
});

it('rejects moving to seeded when an event has no heats', function () {
    $competition = Competition::factory()->status(CompetitionStatus::Closed)->create();
    Event::factory()->create(['competition_id' => $competition->id, 'event_number' => 13]);
    $panitia = User::factory()->panitia()->create();

    expect(fn () => (new CompetitionStatusTransition)->transition(
        $competition,
        CompetitionStatus::Seeded,
        $panitia,
    ))->toThrow(CannotTransitionCompetitionException::class);
});

it('rejects moving to published when a result is still unverified', function () {
    $competition = Competition::factory()->status(CompetitionStatus::Finished)->create();
    $event = Event::factory()->create(['competition_id' => $competition->id, 'event_number' => 13]);
    $group = AgeGroup::factory()->create(['competition_id' => $competition->id, 'code' => '3']);
    $heat = Heat::factory()->create([
        'event_id' => $event->id,
        'age_group_id' => $group->id,
    ]);
    $athlete = Athlete::factory()->create();
    $registration = Registration::factory()->create([
        'competition_id' => $competition->id,
        'event_id' => $event->id,
        'age_group_id' => $group->id,
        'athlete_id' => $athlete->id,
    ]);
    $lane = HeatLane::factory()->create([
        'heat_id' => $heat->id,
        'lane_number' => 4,
        'registration_id' => $registration->id,
    ]);
    Result::factory()->create([
        'heat_lane_id' => $lane->id,
        'verified_at' => null,
    ]);
    $panitia = User::factory()->panitia()->create();

    expect(fn () => (new CompetitionStatusTransition)->transition(
        $competition,
        CompetitionStatus::Published,
        $panitia,
    ))->toThrow(CannotTransitionCompetitionException::class);
});

it('writes an audit log for every status change', function () {
    $competition = Competition::factory()->status(CompetitionStatus::Draft)->create();
    $panitia = User::factory()->panitia()->create();

    (new CompetitionStatusTransition)->transition(
        $competition,
        CompetitionStatus::Registration,
        $panitia,
    );

    expect(ActivityLog::query()->where('action', 'competition.status_change')->count())->toBe(1)
        ->and(ActivityLog::query()->first()->old_values['status'])->toBe('draft')
        ->and(ActivityLog::query()->first()->new_values['status'])->toBe('registration');
});
