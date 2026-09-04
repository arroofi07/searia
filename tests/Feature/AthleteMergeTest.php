<?php

use App\Models\ActivityLog;
use App\Models\Athlete;
use App\Models\Club;
use App\Models\Registration;
use App\Models\User;

it('moves all registrations when merging athletes', function () {
    $club = Club::factory()->create();
    $keep = Athlete::factory()->create(['club_id' => $club->id, 'full_name' => 'AHZA DANISH RAHMAN', 'birth_year' => 2016]);
    $discard = Athlete::factory()->create(['club_id' => $club->id, 'full_name' => 'AHZA D RAHMAN', 'birth_year' => 2016]);
    $panitia = User::factory()->panitia()->create();

    $first = Registration::factory()->create([
        'athlete_id' => $discard->id,
        'registered_by' => $panitia->id,
        'event_id' => 13,
    ]);
    $second = Registration::factory()->create([
        'athlete_id' => $discard->id,
        'registered_by' => $panitia->id,
        'event_id' => 15,
    ]);

    $this->actingAs($panitia)
        ->post(route('admin.athletes.merge.store', $keep), [
            'discard_athlete_id' => $discard->id,
        ])
        ->assertRedirect(route('athletes.show', $keep));

    expect($first->fresh()->athlete_id)->toBe($keep->id)
        ->and($second->fresh()->athlete_id)->toBe($keep->id)
        ->and(Athlete::query()->find($discard->id))->toBeNull()
        ->and(ActivityLog::query()->where('action', 'athlete.merge')->count())->toBe(1)
        ->and(ActivityLog::query()->first()->old_values['discarded_athlete']['id'])->toBe($discard->id)
        ->and(ActivityLog::query()->first()->old_values['discarded_athlete']['full_name'])->toBe('AHZA D RAHMAN');
});

it('rejects a merge when both athletes are registered on the same event', function () {
    $club = Club::factory()->create();
    $keep = Athlete::factory()->create(['club_id' => $club->id]);
    $discard = Athlete::factory()->create(['club_id' => $club->id]);
    $panitia = User::factory()->panitia()->create();

    Registration::factory()->create([
        'athlete_id' => $keep->id,
        'registered_by' => $panitia->id,
        'event_id' => 13,
    ]);
    Registration::factory()->create([
        'athlete_id' => $discard->id,
        'registered_by' => $panitia->id,
        'event_id' => 13,
    ]);

    $this->actingAs($panitia)
        ->from(route('admin.athletes.merge.create', $keep))
        ->post(route('admin.athletes.merge.store', $keep), [
            'discard_athlete_id' => $discard->id,
        ])
        ->assertRedirect(route('admin.athletes.merge.create', $keep))
        ->assertSessionHasErrors('discard_athlete_id');

    expect(Athlete::query()->find($discard->id))->not->toBeNull()
        ->and($discard->registrations()->count())->toBe(1);
});
