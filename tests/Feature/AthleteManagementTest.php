<?php

use App\Enums\Gender;
use App\Models\Athlete;
use App\Models\Club;
use App\Models\Registration;
use App\Models\User;

it('assigns a coach athlete to their own club even if another club_id is posted', function () {
    $clubA = Club::factory()->create();
    $clubB = Club::factory()->create();
    $coach = User::factory()->pelatih($clubA)->create();

    $this->actingAs($coach)
        ->post(route('athletes.store'), [
            'club_id' => $clubB->id,
            'full_name' => 'AHZA DANISH RAHMAN',
            'gender' => Gender::Male->value,
            'birth_year' => 2016,
        ])
        ->assertRedirect();

    $athlete = Athlete::query()->first();

    expect($athlete)->not->toBeNull()
        ->and($athlete->club_id)->toBe($clubA->id)
        ->and($athlete->full_name)->toBe('AHZA DANISH RAHMAN');
});

it('deactivates an athlete who already has registrations instead of deleting them', function () {
    $club = Club::factory()->create();
    $athlete = Athlete::factory()->create(['club_id' => $club->id]);
    $coach = User::factory()->pelatih($club)->create();

    Registration::factory()->create([
        'athlete_id' => $athlete->id,
        'registered_by' => $coach->id,
        'event_id' => 13,
    ]);

    $this->actingAs($coach)
        ->from(route('athletes.show', $athlete))
        ->delete(route('athletes.destroy', $athlete))
        ->assertRedirect(route('athletes.show', $athlete));

    expect(Athlete::query()->find($athlete->id))->not->toBeNull()
        ->and($athlete->fresh()->is_active)->toBeFalse();
});

it('filters the athlete list by birth year and gender', function () {
    $club = Club::factory()->create();
    Athlete::factory()->create([
        'club_id' => $club->id,
        'full_name' => 'AHZA DANISH RAHMAN',
        'gender' => Gender::Male,
        'birth_year' => 2016,
    ]);
    Athlete::factory()->create([
        'club_id' => $club->id,
        'full_name' => 'MUTYA ZAHIRA TANJUNG',
        'gender' => Gender::Female,
        'birth_year' => 2017,
    ]);

    $panitia = User::factory()->panitia()->create();

    $this->actingAs($panitia)
        ->get(route('athletes.index', [
            'birth_year' => 2016,
            'gender' => Gender::Male->value,
        ]))
        ->assertOk()
        ->assertSee('AHZA DANISH RAHMAN')
        ->assertDontSee('MUTYA ZAHIRA TANJUNG');
});

it('shows similar athletes after saving a new athlete in the same club and birth year', function () {
    $club = Club::factory()->create();
    Athlete::factory()->create([
        'club_id' => $club->id,
        'full_name' => 'AHZA DANISH RAHMAN',
        'birth_year' => 2016,
        'gender' => Gender::Male,
    ]);
    $panitia = User::factory()->panitia()->create();

    $this->actingAs($panitia)
        ->post(route('athletes.store'), [
            'club_id' => $club->id,
            'full_name' => 'AHZA D RAHMAN',
            'gender' => Gender::Male->value,
            'birth_year' => 2016,
        ])
        ->assertRedirect()
        ->assertSessionHas('similar_athletes');

    $athlete = Athlete::query()->where('full_name', 'AHZA D RAHMAN')->first();

    $this->actingAs($panitia)
        ->get(route('athletes.show', $athlete))
        ->assertOk()
        ->assertSee('Ditemukan atlet mirip')
        ->assertSee('AHZA DANISH RAHMAN');
});
