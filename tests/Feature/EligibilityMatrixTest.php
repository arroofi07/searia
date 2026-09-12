<?php

use App\Models\AgeGroup;
use App\Models\Athlete;
use App\Models\Competition;
use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Support\Facades\DB;

it('explains the eligibility matrix on the page', function () {
    $competition = Competition::factory()->create();
    AgeGroup::factory()->create(['competition_id' => $competition->id, 'code' => '3', 'name' => 'Group 3']);
    Event::factory()->create(['competition_id' => $competition->id, 'event_number' => 13]);
    $panitia = User::factory()->panitia()->create();

    $this->actingAs($panitia)
        ->get(route('admin.competitions.eligibility', $competition))
        ->assertOk()
        ->assertSee('Apa fungsi matriks ini?')
        ->assertSee('kelompok umur mana yang boleh ikut nomor lomba mana')
        ->assertSee('hanya menampilkan kombinasi yang dicentang')
        ->assertSee('Izinkan semua grup ikut nomor')
        ->assertDontSee('>kolom</button>', false);
});

it('saves the eligibility matrix as matching event_age_group rows', function () {
    $competition = Competition::factory()->create();
    $groupA = AgeGroup::factory()->create(['competition_id' => $competition->id, 'code' => '3']);
    $groupB = AgeGroup::factory()->create(['competition_id' => $competition->id, 'code' => '4']);
    $eventA = Event::factory()->create(['competition_id' => $competition->id, 'event_number' => 13]);
    $eventB = Event::factory()->create(['competition_id' => $competition->id, 'event_number' => 14]);
    $panitia = User::factory()->panitia()->create();

    $this->actingAs($panitia)
        ->putJson(route('admin.competitions.eligibility.update', $competition), [
            'pairs' => [
                ['event_id' => $eventA->id, 'age_group_id' => $groupA->id],
                ['event_id' => $eventA->id, 'age_group_id' => $groupB->id],
                ['event_id' => $eventB->id, 'age_group_id' => $groupA->id],
            ],
        ])
        ->assertOk()
        ->assertJson(['ok' => true, 'count' => 3]);

    expect(DB::table('event_age_group')->count())->toBe(3);
});

it('requires confirmation when removing eligibility that still has registrations', function () {
    $competition = Competition::factory()->create();
    $group = AgeGroup::factory()->create(['competition_id' => $competition->id, 'code' => '3']);
    $event = Event::factory()->create(['competition_id' => $competition->id, 'event_number' => 13]);
    $event->ageGroups()->attach($group->id);
    $athlete = Athlete::factory()->create();
    Registration::factory()->create([
        'competition_id' => $competition->id,
        'event_id' => $event->id,
        'age_group_id' => $group->id,
        'athlete_id' => $athlete->id,
    ]);
    $panitia = User::factory()->panitia()->create();

    $this->actingAs($panitia)
        ->putJson(route('admin.competitions.eligibility.update', $competition), [
            'pairs' => [],
        ])
        ->assertStatus(409)
        ->assertJson([
            'requires_confirmation' => true,
            'affected_registrations' => 1,
        ]);

    expect($event->ageGroups()->count())->toBe(1);

    $this->actingAs($panitia)
        ->putJson(route('admin.competitions.eligibility.update', $competition), [
            'pairs' => [],
            'confirm_affected' => true,
        ])
        ->assertOk();

    expect($event->ageGroups()->count())->toBe(0);
});
