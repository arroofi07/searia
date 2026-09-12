<?php

use App\Enums\CompetitionStatus;
use App\Enums\CompetitionType;
use App\Enums\SeedingMode;
use App\Models\AgeGroup;
use App\Models\Athlete;
use App\Models\Competition;
use App\Models\Event;
use App\Models\Registration;
use App\Models\User;

function competitionForm(Competition $competition, array $overrides = []): array
{
    return [
        'name' => $competition->name,
        'venue' => $competition->venue,
        'city' => $competition->city,
        'start_date' => $competition->start_date->toDateString(),
        'end_date' => $competition->end_date->toDateString(),
        'registration_opens_at' => $competition->registration_opens_at->format('Y-m-d H:i:s'),
        'registration_closes_at' => $competition->registration_closes_at->format('Y-m-d H:i:s'),
        'technical_meeting_at' => $competition->technical_meeting_at?->format('Y-m-d H:i:s'),
        'type' => $competition->type->value,
        'pool_lanes' => $competition->pool_lanes,
        'pool_length' => $competition->pool_length,
        'max_events_per_athlete' => $competition->max_events_per_athlete,
        'seeding_mode' => $competition->seeding_mode->value,
        'fee_per_event' => $competition->fee_per_event,
        'late_fee_per_event' => $competition->late_fee_per_event,
        'fast_time_input' => $competition->fast_time_input ?? true,
        'description' => $competition->description,
        ...$overrides,
    ];
}

it('duplicates a competition with the same events and no registrations', function () {
    $source = Competition::factory()->create();
    $group = AgeGroup::factory()->create(['competition_id' => $source->id, 'code' => '3']);
    $event = Event::factory()->create(['competition_id' => $source->id, 'event_number' => 13]);
    $event->ageGroups()->attach($group->id);
    $athlete = Athlete::factory()->create();
    Registration::factory()->create([
        'competition_id' => $source->id,
        'event_id' => $event->id,
        'age_group_id' => $group->id,
        'athlete_id' => $athlete->id,
    ]);

    $panitia = User::factory()->panitia()->create();

    $this->actingAs($panitia)
        ->post(route('admin.competitions.duplicate', $source))
        ->assertRedirect();

    $copy = Competition::query()->where('id', '!=', $source->id)->first();

    expect($copy)->not->toBeNull()
        ->and($copy->events()->count())->toBe($source->events()->count())
        ->and($copy->ageGroups()->count())->toBe(1)
        ->and($copy->registrations()->count())->toBe(0)
        ->and($source->registrations()->count())->toBe(1)
        ->and($copy->events()->first()->ageGroups()->count())->toBe(1)
        ->and($copy->status)->toBe(CompetitionStatus::Draft);
});

it('explains the max events per athlete field on the create form', function () {
    $this->actingAs(User::factory()->panitia()->create())
        ->get(route('admin.competitions.create'))
        ->assertOk()
        ->assertSee('Batas nomor per atlet')
        ->assertSee('Jumlah nomor lomba maksimal yang boleh diikuti satu atlet')
        ->assertSee('form daftar, import Excel, dan input manual');
});

it('rejects changing pool lanes after the competition leaves draft', function () {
    $competition = Competition::factory()->status(CompetitionStatus::Seeded)->create([
        'pool_lanes' => 8,
        'type' => CompetitionType::Official,
        'seeding_mode' => SeedingMode::Balanced,
    ]);
    $panitia = User::factory()->panitia()->create();

    $this->actingAs($panitia)
        ->from(route('admin.competitions.edit', $competition))
        ->put(route('admin.competitions.update', $competition), competitionForm($competition, [
            'pool_lanes' => 6,
        ]))
        ->assertRedirect(route('admin.competitions.edit', $competition))
        ->assertSessionHasErrors('pool_lanes');

    expect($competition->fresh()->pool_lanes)->toBe(8);
});
